<?php

namespace App\Console\Commands;

use App\Models\HomePage;
use App\Models\SiteSetting;
use Illuminate\Console\Command;

class InitializeCms extends Command
{
    protected $signature = 'clinic:initialize-cms';

    protected $description = 'Create missing CMS draft singletons from the existing provisional site, without overwriting content';

    public function handle(): int
    {
        $data = json_decode(file_get_contents(base_path('../frontend/lib/cms-defaults.json')), true, 512, JSON_THROW_ON_ERROR);
        HomePage::firstOrCreate(['key' => 'main'], $data['home']);
        SiteSetting::firstOrCreate(['key' => 'main'], $data['settings']);
        $this->info('Borradores CMS disponibles. No se sobrescribió ni publicó contenido existente.');

        return self::SUCCESS;
    }
}
