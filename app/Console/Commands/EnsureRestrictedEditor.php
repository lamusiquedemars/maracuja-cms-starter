<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ContentPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureRestrictedEditor extends Command
{
    protected $signature = 'maracuja:ensure-restricted-editor
        {email : Adresse email de l’utilisateur}
        {--name= : Nom affiché}
        {--permission=* : Capacité autorisée, à répéter}';

    protected $description = 'Crée ou met à jour un éditeur limité à des rubriques précises.';

    public function handle(): int
    {
        $permissions = array_values(array_unique($this->option('permission')));
        $invalidPermissions = array_diff($permissions, array_keys(ContentPermissions::labels()));

        if ($invalidPermissions !== []) {
            $this->error('Capacité invalide : '.implode(', ', $invalidPermissions).'.');
            $this->line('Capacités disponibles : '.implode(', ', array_keys(ContentPermissions::labels())).'.');

            return self::FAILURE;
        }

        $password = $this->secret('Mot de passe (8 caractères minimum)');

        if (mb_strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) ($this->option('name') ?: $email),
                'password' => Hash::make($password),
                'role' => User::ROLE_EDITOR,
                'is_admin' => false,
                'content_permissions' => $permissions,
            ],
        );

        $this->info("Éditeur limité actif : {$user->email} (".implode(', ', $permissions).')');

        return self::SUCCESS;
    }
}
