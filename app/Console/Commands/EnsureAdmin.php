<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureAdmin extends Command
{
    protected $signature = 'maracuja:ensure-admin {email : Adresse email de l’administrateur} {--name=Ivo : Nom affiché}';

    protected $description = 'Crée ou réinitialise un administrateur Maracuja.';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $password = $this->secret('Mot de passe (8 caractères minimum)');

        if (mb_strlen($password) < 8) {
            $this->error('Le mot de passe doit contenir au moins 8 caractères.');

            return self::FAILURE;
        }

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => (string) $this->option('name'),
                'password' => Hash::make($password),
                'role' => User::ROLE_ADMIN,
                'is_admin' => true,
            ],
        );

        $this->info("Administrateur actif : {$user->email}");

        return self::SUCCESS;
    }
}
