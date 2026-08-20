<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class AssignUserRole extends Command
{
    protected $signature = 'maracuja:assign-role {email : Adresse email de l’utilisateur} {role : admin, editor ou viewer}';

    protected $description = 'Modifie le rôle d’un utilisateur sans changer son mot de passe.';

    public function handle(): int
    {
        $role = (string) $this->argument('role');

        if (! in_array($role, [User::ROLE_ADMIN, User::ROLE_EDITOR, User::ROLE_VIEWER], true)) {
            $this->error('Rôle invalide : admin, editor ou viewer.');

            return self::FAILURE;
        }

        $user = User::query()
            ->where('email', (string) $this->argument('email'))
            ->first();

        if (! $user) {
            $this->error('Utilisateur introuvable.');

            return self::FAILURE;
        }

        $user->update([
            'role' => $role,
            'is_admin' => $role === User::ROLE_ADMIN,
        ]);

        $this->info("Rôle mis à jour : {$user->email} ({$role})");

        return self::SUCCESS;
    }
}
