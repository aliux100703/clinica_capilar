<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\HomePage;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\Result;
use App\Models\SiteSetting;
use App\Models\Specialist;
use App\Models\Testimonial;
use App\Models\Treatment;
use App\Models\User;
use App\Services\RevalidateContent;
use App\Services\StoreCmsImage;
use App\Support\SafeHtml;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CmsContentTest extends TestCase
{
    public function test_bootstrap_keeps_existing_admin_credentials(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $originalPassword = $admin->password;
        $this->artisan('clinic:create-admin', ['--if-missing' => true])
            ->expectsOutput('El administrador existente se conserva. No se modificaron credenciales.')
            ->assertSuccessful();
        $this->assertSame(1, User::count());
        $this->assertSame($originalPassword, $admin->fresh()->password);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Artisan::call('migrate', ['--force' => true]);
        Storage::fake('cms_media');
        Storage::fake('cms_private');
        config(['cms.revalidate_url' => null, 'cms.revalidate_secret' => null]);
    }

    private function treatment(): Treatment
    {
        return Treatment::create(['name' => 'Fixture técnica', 'slug' => 'fixture-tecnica', 'description' => 'Datos sintéticos', 'status' => 'published']);
    }

    private function image(): string
    {
        $id = (string) Str::uuid();
        Storage::disk('cms_media')->put('results/'.$id.'.webp', 'fixture');
        $asset = new MediaAsset;
        $asset->id = $id;
        $asset->fill(['path' => 'results/'.$id.'.webp', 'mime' => 'image/webp', 'width' => 10, 'height' => 10, 'size' => 7])->save();

        return $id;
    }

    private function consent(): array
    {
        $document = 'consents/'.Str::uuid().'.pdf';
        Storage::disk('cms_private')->put($document, 'Synthetic consent fixture');

        return ['consent_granted' => true, 'consent_document' => $document, 'consent_recorded_at' => today()->toDateString()];
    }

    public function test_singletons_and_faq_publication_editing_and_unpublishing(): void
    {
        $this->getJson('/api/v1/home')->assertOk()->assertJsonPath('data', null);
        $home = HomePage::create(['title' => 'Título provisional', 'status' => 'draft']);
        $this->getJson('/api/v1/home')->assertJsonPath('data', null);
        $home->update(['title' => 'Título publicado', 'status' => 'published']);
        $this->getJson('/api/v1/home')->assertJsonPath('data.title', 'Título publicado')->assertJsonMissingPath('data.status');
        $settings = SiteSetting::create(['trade_name' => 'Nombre técnico', 'status' => 'published']);
        $this->getJson('/api/v1/site-settings')->assertJsonPath('data.trade_name', 'Nombre técnico');
        $faq = Faq::create(['question' => 'Pregunta sintética', 'answer' => 'Respuesta sintética', 'status' => 'published', 'sort_order' => 2]);
        Faq::create(['question' => 'Borrador oculto', 'answer' => 'Oculto']);
        $this->getJson('/api/v1/faqs')->assertJsonCount(1, 'data');
        $faq->update(['answer' => 'Respuesta editada']);
        $this->getJson('/api/v1/faqs')->assertJsonPath('data.0.answer', 'Respuesta editada');
        $home->update(['status' => 'draft']);
        $faq->update(['status' => 'draft']);
        $this->getJson('/api/v1/home')->assertJsonPath('data', null);
        $this->getJson('/api/v1/faqs')->assertJsonCount(0, 'data');
    }

    public function test_rich_html_is_sanitized_at_save_and_at_the_public_boundary(): void
    {
        $t = $this->treatment();
        $t->update(['content' => '<h2 onclick="alert(1)">Título</h2><script>secret</script><a href="javascript:alert(1)">Enlace</a><img src=x onerror=alert(1)><svg><script>bad</script></svg><p>Texto</p>']);
        $this->assertStringNotContainsString('onclick', $t->content);
        $this->assertStringNotContainsString('<script', $t->content);
        $this->assertStringNotContainsString('javascript:', $t->content);
        $this->getJson('/api/v1/treatments/'.$t->slug)->assertOk()->assertDontSee('<img', false)->assertDontSee('secret');
        $this->assertStringContainsString('<strong>Seguro</strong>', SafeHtml::clean('<strong>Seguro</strong>'));
        $this->assertNull(SafeHtml::url('https://user:secret@example.test'));
        $this->assertNull(SafeHtml::url('java&#x73;cript:alert(1)'));
        $this->assertSame('#valoracion', SafeHtml::url('#valoracion'));
    }

    public function test_results_require_documented_consent_and_media_revokes_immediately(): void
    {
        $t = $this->treatment();
        $before = $this->image();
        $after = $this->image();
        $result = Result::create(['treatment_id' => $t->id, 'before_image' => $before, 'after_image' => $after, 'description' => 'Fixture', 'evolution_time' => 'Contexto de prueba']);
        try {
            $result->update(['status' => 'published']);
            $this->fail('Publication without consent accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('consent_document', $e->errors());
        }
        $result->update($this->consent() + ['status' => 'published']);
        $t->update(['image' => $before]); // Reuse cannot bypass the patient-image consent check.
        $this->getJson('/api/v1/results')->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.consent_document')->assertJsonMissingPath('data.0.consent_recorded_at');
        $this->get('/api/v1/media/'.$before)->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/admin/consents/results/'.$result->id)->assertForbidden();
        $result->update(['consent_granted' => false]);
        $this->assertSame('draft', $result->fresh()->status);
        $this->getJson('/api/v1/results')->assertJsonCount(0, 'data');
        $this->get('/api/v1/media/'.$before)->assertNotFound();
    }

    public function test_expired_or_missing_consent_and_unpublished_treatment_hide_results(): void
    {
        $t = $this->treatment();
        $result = Result::create(['treatment_id' => $t->id, 'before_image' => $this->image(), 'after_image' => $this->image(), 'description' => 'Fixture', 'evolution_time' => 'Prueba', 'status' => 'published'] + $this->consent());
        Storage::disk('cms_private')->delete($result->consent_document);
        $this->getJson('/api/v1/results')->assertJsonCount(0, 'data');
        Storage::disk('cms_private')->put($result->consent_document, 'Fixture');
        $t->update(['status' => 'draft']);
        $this->getJson('/api/v1/results')->assertJsonCount(0, 'data');
        $testimonial = Testimonial::create(['public_name' => 'Alias ficticio', 'quote' => 'Texto técnico', 'status' => 'published'] + $this->consent());
        $this->travel(2)->days();
        $testimonial->update(['consent_expires_at' => today()->subDay()->toDateString(), 'status' => 'draft']);
        $this->getJson('/api/v1/testimonials')->assertJsonCount(0, 'data');
        $this->travelBack();
    }

    public function test_blog_scheduling_reviews_and_drafts(): void
    {
        $post = Post::create(['title' => 'Artículo técnico', 'slug' => 'articulo-tecnico', 'excerpt' => 'Fixture', 'content' => '<p>Texto no médico</p>', 'category' => 'Prueba', 'author' => 'Autor ficticio', 'requires_medical_review' => false, 'published_at' => now()->addDay(), 'status' => 'published']);
        $this->getJson('/api/v1/posts')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/posts/'.$post->slug)->assertNotFound();
        $post->update(['published_at' => now()->subMinute()]);
        $this->getJson('/api/v1/posts/'.$post->slug)->assertOk()->assertJsonPath('data.title', 'Artículo técnico');
        $post->update(['status' => 'draft']);
        $this->getJson('/api/v1/posts/'.$post->slug)->assertNotFound();
        $post->requires_medical_review = true;
        $post->status = 'published';
        try {
            $post->save();
            $this->fail('Medical publication without reviewer accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('medical_reviewer_id', $e->errors());
        }
    }

    public function test_images_are_optimized_private_and_shared_files_are_retained(): void
    {
        if (! function_exists('imagecreatefromstring')) {
            $this->markTestSkipped('Run with php -d extension=gd');
        }
        $id = app(StoreCmsImage::class)->store(UploadedFile::fake()->image('unsafe-name.jpg', 2200, 1200), 'treatments');
        $asset = MediaAsset::findOrFail($id);
        $this->assertSame('image/webp', $asset->mime);
        $this->assertSame(1920, $asset->width);
        $this->assertStringNotContainsString('unsafe-name', $asset->path);
        Storage::disk('cms_media')->assertExists($asset->path);
        $this->get('/api/v1/media/'.$id)->assertNotFound();
        $this->get('/admin/media/'.$id)->assertForbidden();
        $t = $this->treatment();
        $t->update(['image' => $id]);
        $this->get('/api/v1/media/'.$id)->assertOk();
        $t->update(['image' => null]);
        Storage::disk('cms_media')->assertExists($asset->path);
        $this->get('/api/v1/media/'.$id)->assertNotFound();
    }

    public function test_invalid_image_format_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(StoreCmsImage::class)->store(UploadedFile::fake()->createWithContent('attack.svg', '<svg onload="alert(1)"></svg>'), 'home');
    }

    public function test_changing_reviewed_medical_content_requires_a_new_review(): void
    {
        $reviewer = Specialist::create(['name' => 'Perfil técnico ficticio', 'specialty' => 'Prueba', 'description' => 'Fixture', 'status' => 'published']);
        $post = Post::create(['title' => 'Artículo técnico revisado', 'slug' => 'articulo-revisado', 'excerpt' => 'Fixture', 'content' => '<p>Texto técnico inicial.</p>', 'category' => 'Prueba', 'author' => 'Autor ficticio', 'medical_reviewer_id' => $reviewer->id, 'reviewed_at' => now()->subMinute(), 'published_at' => now()->subMinute(), 'status' => 'published']);
        $this->getJson('/api/v1/posts/'.$post->slug)->assertOk()->assertJsonPath('data.medical_reviewer.name', $reviewer->name);
        $post->update(['content' => '<p>Texto técnico editado.</p>']);
        $this->assertSame('draft', $post->fresh()->status);
        $this->assertNull($post->fresh()->reviewed_at);
        $this->getJson('/api/v1/posts/'.$post->slug)->assertNotFound();
    }

    public function test_revalidation_webhook_runs_after_changes_and_failure_is_retryable(): void
    {
        config(['cms.revalidate_url' => 'http://localhost:3000/api/revalidate', 'cms.revalidate_secret' => str_repeat('a', 64)]);
        Http::fake(['*' => Http::response(['revalidated' => true])]);
        Faq::create(['question' => 'Prueba', 'answer' => 'Fixture']);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer '.str_repeat('a', 64)) && $request['tag'] === 'cms');
        $this->assertFalse(Cache::has('cms.revalidation_pending'));
        Http::swap(new Factory);
        Http::fake(['*' => Http::response([], 503)]);
        $this->assertFalse(app(RevalidateContent::class)->send());
        $this->assertTrue(Cache::has('cms.revalidation_pending'));
    }

    public function test_all_cms_models_require_admin_and_crm_routes_are_not_registered(): void
    {
        $user = new User;
        $user->role = 'user';
        foreach ([HomePage::class, SiteSetting::class, Treatment::class, Specialist::class, Result::class, Post::class, Testimonial::class, Faq::class] as $model) {
            $this->assertFalse(Gate::forUser($user)->allows('create', $model));
            $user->role = 'admin';
            $this->assertTrue(Gate::forUser($user)->allows('create', $model));
            $user->role = 'user';
        }
        foreach (['/admin/appointments', '/admin/valuation-requests', '/admin/contact-messages'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
