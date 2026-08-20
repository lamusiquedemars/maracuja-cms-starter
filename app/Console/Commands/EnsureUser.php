<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureUser extends Command
{
    protected $signature = 'maracuja:ensure-user {email} {role : admin, editor ou viewer} {--name= : Nom affiché}';

    protected $description = 'Crée ou réinitialise un utilisateur avec son rôle Maracuja.';

    public function handle(): int
    {
        $role = (string) $this->argument('role');

        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_EDITOR, User::ROLE_VIEWER], true)) {
            $this->error('Rôle invalide : admin, editor ou viewer.');

            return self::FAILURE;
        }

        $password = $this->secret('Mot de passe (8 caractères minimum)');

        if (mb_strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => (string) $this->argument('email')],
            [
                'name' => (string) ($this->option('name') ?: 'Client'),
                'password' => Hash::make($password),
                'role' => $role,
                'is_admin' => $role === User::ROLE_ADMIN,
            ],
        );

        $this->info("Utilisateur actif : {$user->email} ({$role})");

        return self::SUCCESS;
    }
}
