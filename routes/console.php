<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:make-admin {email : Email del usuario admin} {--name= : Nombre del usuario si no existe} {--password= : Password del usuario si no existe}', function () {
    $email = Str::lower(trim((string) $this->argument('email')));
    $name = trim((string) ($this->option('name') ?: 'Administrador'));
    $passwordOption = (string) ($this->option('password') ?: '');

    $user = User::where('email', $email)->first();
    $generatedPassword = null;

    if ($user) {
        $user->role = 'admin';

        if ($name !== '') {
            $user->name = $name;
        }

        if ($passwordOption !== '') {
            $user->password = Hash::make($passwordOption);
        }

        $user->save();

        $this->info("Usuario actualizado como admin: {$user->email}");

        if ($passwordOption !== '') {
            $this->comment('La contraseña fue actualizada.');
        }

        return self::SUCCESS;
    }

    if ($passwordOption === '') {
        $generatedPassword = Str::password(16);
    }

    $user = User::create([
        'name' => $name !== '' ? $name : 'Administrador',
        'email' => $email,
        'password' => $passwordOption !== '' ? Hash::make($passwordOption) : Hash::make($generatedPassword),
        'role' => 'admin',
    ]);

    $this->info("Admin creado: {$user->email}");
    $this->line("Nombre: {$user->name}");

    if ($generatedPassword !== null) {
        $this->warn("Password generado: {$generatedPassword}");
        $this->comment('Guardalo en un lugar seguro. Solo se muestra una vez.');
    }

    return self::SUCCESS;
})->purpose('Crea o asciende un usuario admin sin sembrarlo automaticamente');
