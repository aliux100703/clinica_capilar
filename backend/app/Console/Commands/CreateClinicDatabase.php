<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateClinicDatabase extends Command
{
    protected $signature = 'clinic:create-database';

    protected $description = 'Create the local clinica_capilar database if absent; never drop data';

    public function handle(): int
    {
        $config = config('database.connections.mysql');
        if (config('database.default') !== 'mysql'
            || $config['host'] !== '127.0.0.1'
            || $config['database'] !== 'clinica_capilar'
            || ! empty($config['url'])) {
            $this->error('Configure MySQL local 127.0.0.1 / clinica_capilar sin DB_URL antes de continuar.');

            return self::FAILURE;
        }
        $config['database'] = null;
        $connection = DB::build($config);
        try {
            $connection->statement('CREATE DATABASE IF NOT EXISTS `clinica_capilar` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $this->info('Base clinica_capilar disponible. Ejecute php artisan migrate.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('No se pudo crear/verificar la base. Compruebe credenciales, puerto y permisos CREATE DATABASE.');

            return self::FAILURE;
        } finally {
            $connection->disconnect();
        }
    }
}
