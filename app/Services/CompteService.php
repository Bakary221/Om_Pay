<?php

namespace App\Services;

use App\Models\Compte;
use App\Models\User;
use App\Repositories\Interfaces\CompteRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompteService
{
    public function __construct(
        private CompteRepositoryInterface $compteRepository,
        private UserRepositoryInterface $userRepository
    ) {}

    /**
     * Créer un nouveau compte bancaire pour un utilisateur
     */
    public function createCompteForUser(User $user): Compte
    {
        DB::beginTransaction();

        try {
            // Créer le compte (l'observer gère le numéro et la transaction initiale)
            $compte = $this->compteRepository->create([
                'id' => Str::uuid(),
                'user_id' => $user->id,
                'numero_compte' => Compte::generateNumeroCompte(),
                'qr_code_data' => null, // Sera généré après
            ]);

            DB::commit();
            return $compte;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour les informations de l'utilisateur associé à un compte
     */
    public function updateUserInfo(Compte $compte, array $data): void
    {
        $user = $compte->user;

        // Mettre à jour les champs du User
        if (isset($data['nom'])) {
            $user->nom = $data['nom'];
        }
        if (isset($data['prenom'])) {
            $user->prenom = $data['prenom'];
        }
        if (isset($data['telephone'])) {
            $user->telephone = $data['telephone'];
        }
        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        $this->userRepository->update($user, $user->toArray());
    }
}