<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\PanelProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateClinicAdmin extends Command
{
    protected $signature = 'clinic:create-admin {--if-missing : Preserve an existing administrator without prompting}';

    protected $description = 'Create a new admin interactively without modifying existing users';

    public function handle(): int
    {
        if ($this->option('if-missing') && User::where('role', 'admin')->exists()) {
            $this->info('El administrador existente se conserva. No se modificaron credenciales.');

            return self::SUCCESS;
        }
        if (! class_exists(PanelProvider::class)) {
            $this->error('Install Filament first using scripts/install-admin.ps1.');

            return self::FAILURE;
        }
        $data = [
            'name' => $this->ask('Nombre del administrador'),
            'email' => $this->ask('Correo del administrador'),
            'password' => $this->secret('Contraseña (mínimo 12 caracteres)'),
            'password_confirmation' => $this->secret('Confirma la contraseña'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:254', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
        ]);
        if ($validator->fails()) {
            $this->error('Datos no válidos o correo ya registrado. No se modificó ningún usuario.');

            return self::FAILURE;
        }
        $user = new User;
        $user->fill(collect($validator->validated())->except('password_confirmation')->all());
        $user->role = 'admin';
        $user->save();
        $this->info('Administrador creado. No se enviaron correos ni notificaciones.');

        return self::SUCCESS;
    }
}
