<?php

namespace App\Observers;

use App\Models\Compte;

class CompteObserver
{
    /**
     * Handle the Compte "creating" event.
     */
    public function creating(Compte $compte): void
    {
        // Generate unique account number
        $compte->numero_compte = Compte::generateNumeroCompte();

        // QR code will be generated after OTP verification
        $compte->qr_code_data = null;
    }
}