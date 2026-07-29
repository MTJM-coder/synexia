<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Identity\Models\User;

class MakeSuperAdminCommand extends Command
{
    protected $signature = 'identity:make-super-admin {email : Email d\'un compte existant}';

    protected $description = 'Promeut un utilisateur existant au rang de Super Admin (jamais accessible via l\'API)';

    public function handle(): int
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->components->error("Aucun utilisateur trouvé avec l'email « {$email} ». Le compte doit déjà exister (via /auth/register) — cette commande ne crée pas de compte, elle promeut un compte existant.");

            return self::FAILURE;
        }

        if ($user->is_super_admin) {
            $this->components->warn("{$user->first_name} {$user->last_name} ({$email}) est déjà Super Admin.");

            return self::SUCCESS;
        }

        if (! $this->components->confirm(
            "Confirmez : promouvoir {$user->first_name} {$user->last_name} ({$email}) au rang de Super Admin ?"
        )) {
            $this->components->warn('Annulé.');

            return self::SUCCESS;
        }

        $user->forceFill(['is_super_admin' => true])->save();

        // Révoque les tokens existants par précaution : si ce compte a été
        // compromis avant la promotion, on ne veut pas qu'un token déjà émis
        // hérite silencieusement des droits Super Admin.
        $user->tokens()->delete();

        $this->components->info("{$user->first_name} {$user->last_name} est maintenant Super Admin. Ses sessions existantes ont été révoquées.");

        return self::SUCCESS;
    }
}
