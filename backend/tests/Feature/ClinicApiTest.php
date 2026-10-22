<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Appointment;
use App\Models\ContactMessage;
use App\Models\Specialist;
use App\Models\Treatment;
use App\Models\User;
use App\Models\ValuationRequest;
use Filament\PanelProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClinicApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // No fresh/reset command: migrate only inside a new in-memory database.
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Artisan::call('migrate', ['--force' => true]);
    }

    private function treatment(string $status = 'published'): Treatment
    {
        return Treatment::create(['name' => 'Tratamiento de prueba', 'slug' => 'prueba-'.$status, 'description' => 'Contenido ficticio.', 'status' => $status]);
    }

    private function payload(): array
    {
        return ['name' => 'Persona ficticia', 'email' => 'test@example.test', 'phone' => '+52 555 000 0000', 'consent' => true, 'idempotency_key' => (string) Str::uuid()];
    }

    public function test_only_published_catalog_records_are_public(): void
    {
        $this->treatment();
        $this->treatment('draft');
        foreach (['published', 'draft'] as $status) {
            Specialist::create(['name' => 'Especialista ficticio', 'specialty' => 'Prueba', 'description' => 'Ficticio', 'status' => $status, 'credentials' => [['title' => 'Ejemplo', 'issuer' => 'Ejemplo', 'registration' => 'TEST', 'verification_url' => 'https://example.test/verify']]]);
        }
        $this->getJson('/api/v1/treatments')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.status');
        $this->getJson('/api/v1/treatments/prueba-published')->assertOk();
        $this->getJson('/api/v1/treatments/prueba-draft')->assertNotFound()->assertJsonPath('data', null);
        $this->getJson('/api/v1/specialists')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_valuation_is_pending_and_does_not_create_appointment_or_expose_personal_data(): void
    {
        $payload = $this->payload() + ['treatment_id' => $this->treatment()->id, 'preferred_date' => now()->addDay()->format('Y-m-d'), 'preferred_time' => '10:30', 'status' => 'confirmed', 'internal_notes' => 'Ignored'];
        $this->postJson('/api/v1/valuation-requests', $payload)->assertCreated()->assertJsonPath('data', null)->assertDontSee('test@example.test');
        $request = ValuationRequest::firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertTrue($request->consent);
        $this->assertNotNull($request->consented_at);
        $this->assertSame(0, Appointment::count());
        $this->getJson('/api/v1/valuation-requests')->assertStatus(405);
        $this->getJson('/api/v1/valuation-requests/'.$request->id)->assertNotFound();
    }

    public function test_validation_rejects_missing_consent_drafts_and_invalid_preferences(): void
    {
        $payload = $this->payload();
        $payload['consent'] = false;
        $payload['treatment_id'] = $this->treatment('draft')->id;
        $payload['preferred_date'] = '2020-01-01';
        $payload['preferred_time'] = '99:99';
        $this->postJson('/api/v1/valuation-requests', $payload)->assertUnprocessable()->assertJsonValidationErrors(['consent', 'treatment_id', 'preferred_date', 'preferred_time']);
        $this->assertSame(0, ValuationRequest::count());
        $this->postJson('/api/v1/contact-messages', ['message' => 'Ejemplo'])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'consent']);
    }

    public function test_contact_accepts_optional_phone_and_ignores_status_override(): void
    {
        $payload = $this->payload();
        unset($payload['phone']);
        $this->postJson('/api/v1/contact-messages', $payload + ['message' => 'Mensaje ficticio.', 'status' => 'closed'])->assertCreated()->assertExactJson(['data' => null, 'message' => 'Mensaje recibido.']);
        $this->assertDatabaseHas('contact_messages', ['status' => 'pending', 'phone' => null, 'consent' => true]);
        $this->getJson('/api/v1/contact-messages')->assertStatus(405);
        $this->assertSame(1, ContactMessage::count());
    }

    public function test_submissions_are_rate_limited_across_both_forms(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact-messages', $this->payload() + ['message' => 'Ficticio'])->assertCreated();
        }
        $this->postJson('/api/v1/valuation-requests', [])->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('data', null);
    }

    public function test_cors_allows_only_local_frontend(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:3000', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'content-type'])->options('/api/v1/contact-messages')->assertSuccessful()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response = $this->withHeaders(['Origin' => 'https://other.example.test'])->getJson('/api/v1/treatments');
        // The CORS library emits the single configured origin as a constant.
        // Browsers reject it when the requesting origin is different.
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3000');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_only_admin_role_can_manage_records(): void
    {
        $user = new User;
        $user->role = 'user';
        $this->assertFalse(Gate::forUser($user)->allows('viewAny', Treatment::class));
        $user->role = 'admin';
        foreach ([Treatment::class, Specialist::class, ValuationRequest::class, ContactMessage::class, Appointment::class] as $model) {
            $this->assertTrue(Gate::forUser($user)->allows('viewAny', $model));
            $this->assertFalse(Gate::forUser($user)->allows('deleteAny', $model));
        }
        $user->role = 'user';
        $user->fill(['role' => 'admin']);
        $this->assertSame('user', $user->role);
        $this->assertNotContains('role', $user->getFillable());
    }

    public function test_panel_is_closed_until_filament_is_installed(): void
    {
        if (! class_exists(PanelProvider::class)) {
            $this->get('/admin')->assertNotFound();
        } else {
            $this->get('/admin')->assertRedirect();
            $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
            $admin = AdminUser::findOrFail(User::factory()->create()->id);
            $admin->forceFill(['role' => 'admin'])->save();
            $this->actingAs($admin)->get('/admin/treatments')->assertOk();
        }
    }

    public function test_retries_do_not_duplicate_inquiries(): void
    {
        $contact = $this->payload() + ['message' => 'Mensaje ficticio'];
        $valuation = $this->payload() + ['treatment_id' => $this->treatment()->id];
        foreach ([$contact, $contact] as $payload) {
            $this->postJson('/api/v1/contact-messages', $payload)->assertCreated();
        }
        foreach ([$valuation, $valuation] as $payload) {
            $this->postJson('/api/v1/valuation-requests', $payload)->assertCreated();
        }
        $this->assertSame(1, ContactMessage::count());
        $this->assertSame(1, ValuationRequest::count());
        $this->assertSame(0, Appointment::count());
    }

    public function test_honeypot_rejects_spam_without_storing_it(): void
    {
        $this->postJson('/api/v1/contact-messages', $this->payload() + ['message' => 'Ficticio', 'website' => 'https://spam.example.test'])->assertUnprocessable()->assertJsonValidationErrors(['website']);
        $this->assertSame(0, ContactMessage::count());
    }

    public function test_forms_are_blocked_in_production_until_policy_approval(): void
    {
        $this->app->instance('env', 'production');
        config(['clinic.privacy_policy_approved' => false]);
        $payload = $this->payload() + ['message' => 'Ficticio'];
        $this->postJson('/api/v1/contact-messages', $payload)->assertStatus(503);
        $this->postJson('/api/v1/valuation-requests', [])->assertStatus(503);
        $this->assertSame(0, ContactMessage::count());
        config(['clinic.privacy_policy_approved' => true]);
        $this->postJson('/api/v1/contact-messages', $payload)->assertCreated();
    }

    public function test_only_verified_professional_credentials_are_public(): void
    {
        Specialist::withoutEvents(fn () => Specialist::create(['name' => 'Perfil ficticio', 'specialty' => 'Prueba', 'description' => 'Ficticio', 'status' => 'published', 'credentials' => [
            ['title' => 'Sin verificar', 'verified' => false],
            ['title' => 'Sin indicador'],
            ['title' => 'Ejemplo verificado', 'verified' => true, 'internal_document' => 'Never public'],
        ]]));
        $this->getJson('/api/v1/specialists')->assertOk()->assertJsonCount(1, 'data.0.credentials')->assertJsonPath('data.0.credentials.0.title', 'Ejemplo verificado')->assertDontSee('Never public')->assertDontSee('Sin verificar');
    }
}
