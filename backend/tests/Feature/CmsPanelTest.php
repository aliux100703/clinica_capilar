<?php

namespace Tests\Feature;

use App\Filament\Cms\Pages\ManageFaq;
use App\Filament\Cms\Pages\ManageHomePage;
use App\Filament\Cms\Pages\ManageSiteSetting;
use App\Filament\Cms\Pages\ManageTreatment;
use App\Models\AdminUser;
use App\Models\Faq;
use App\Models\HomePage;
use App\Models\MediaAsset;
use App\Models\SiteSetting;
use App\Models\Treatment;
use App\Models\User;
use App\Services\StoreCmsImage;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CmsPanelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        Artisan::call('migrate', ['--force' => true]);
        Storage::fake('cms_media');
        Storage::fake('cms_private');
        config(['cms.revalidate_url' => null, 'cms.revalidate_secret' => null]);
        $user = User::factory()->create();
        $admin = AdminUser::findOrFail($user->id);
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    public function test_all_eight_modules_render_and_open_their_content_forms(): void
    {
        foreach (['HomePage', 'Treatment', 'Specialist', 'Result', 'Post', 'Testimonial', 'Faq', 'SiteSetting'] as $name) {
            $resource = 'App\\Filament\\Cms\\'.$name.'Resource';
            $page = 'App\\Filament\\Cms\\Pages\\Manage'.$name;
            $this->get($resource::getUrl('index'))->assertOk();
            Livewire::test($page)->mountAction('create')->assertActionMounted('create');
        }
    }

    public function test_admin_can_create_edit_publish_and_unpublish_a_faq_through_filament(): void
    {
        Livewire::test(ManageFaq::class)
            ->callAction('create', data: ['question' => 'Pregunta técnica ficticia', 'answer' => 'Respuesta de prueba.', 'sort_order' => 0, 'status' => 'draft'])
            ->assertHasNoActionErrors();
        $faq = Faq::firstOrFail();
        $this->getJson('/api/v1/faqs')->assertJsonCount(0, 'data');
        Livewire::test(ManageFaq::class)
            ->callAction(TestAction::make('edit')->table($faq), data: ['question' => 'Pregunta editada', 'answer' => 'Respuesta actualizada.', 'sort_order' => 1, 'status' => 'published'])
            ->assertHasNoActionErrors();
        $this->getJson('/api/v1/faqs')->assertJsonPath('data.0.question', 'Pregunta editada');
        Livewire::test(ManageFaq::class)
            ->callAction(TestAction::make('edit')->table($faq), data: ['status' => 'draft'])
            ->assertHasNoActionErrors();
        $this->getJson('/api/v1/faqs')->assertJsonCount(0, 'data');
    }

    public function test_uploaded_treatment_image_survives_editing_and_publication(): void
    {
        Livewire::test(ManageTreatment::class)
            ->callAction('create', data: [
                'name' => 'Contenido técnico de prueba',
                'slug' => 'contenido-tecnico-de-prueba',
                'description' => 'Registro sintético sin información médica.',
                'image' => UploadedFile::fake()->image('prueba.png', 100, 100),
                'sort_order' => 0,
                'status' => 'draft',
            ])
            ->assertHasNoActionErrors();

        $treatment = Treatment::firstOrFail();
        $asset = MediaAsset::findOrFail($treatment->image);
        $this->assertSame('image/webp', $asset->mime);
        Storage::disk('cms_media')->assertExists($asset->path);
        $this->get('/admin/media/'.$asset->id)->assertOk();
        $this->get('/api/v1/media/'.$asset->id)->assertNotFound();

        Livewire::test(ManageTreatment::class)
            ->callAction(TestAction::make('edit')->table($treatment), data: ['status' => 'published'])
            ->assertHasNoActionErrors();

        $this->assertSame($asset->id, $treatment->fresh()->image);
        $this->get('/api/v1/media/'.$asset->id)->assertOk();
        $this->getJson('/api/v1/treatments/'.$treatment->slug)->assertOk();
    }

    public function test_home_and_settings_edits_stay_private_until_published(): void
    {
        foreach ([
            [HomePage::class, ManageHomePage::class, 'title', 'home'],
            [SiteSetting::class, ManageSiteSetting::class, 'trade_name', 'site-settings'],
        ] as [$model, $page, $field, $endpoint]) {
            $record = $model::create([$field => 'Contenido sintético inicial', 'status' => 'draft']);
            Livewire::test($page)
                ->callAction(TestAction::make('edit')->table($record), data: [$field => 'Contenido sintético editado'])
                ->assertHasNoActionErrors()
                ->assertNotified('Borrador guardado');
            $this->assertSame('Contenido sintético editado', $record->fresh()->getAttribute($field));
            $this->getJson('/api/v1/'.$endpoint)->assertJsonPath('data', null);

            Livewire::test($page)
                ->callAction(TestAction::make('edit')->table($record), data: ['status' => 'published'])
                ->assertHasNoActionErrors()
                ->assertNotified('Contenido publicado');
            $this->getJson('/api/v1/'.$endpoint)->assertOk()->assertJsonPath('data.'.$field, 'Contenido sintético editado');
        }
    }

    public function test_guests_and_non_admin_users_cannot_access_cms_or_private_images(): void
    {
        $assetId = app(StoreCmsImage::class)->store(UploadedFile::fake()->image('privada.png'), 'treatments');
        auth()->logout();
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/media/'.$assetId)->assertForbidden();

        $user = User::factory()->create();
        $this->actingAs(AdminUser::findOrFail($user->id));
        $this->get('/admin')->assertForbidden();
        $this->get('/admin/media/'.$assetId)->assertForbidden();
        $this->get('/admin/treatments')->assertForbidden();
    }
}
