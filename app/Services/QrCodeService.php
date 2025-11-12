<?php

namespace App\Services;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    /**
     * Générer un QR code pour un compte
     *
     * @param string $numeroCompte
     * @param string $userId
     * @return string
     */
    public static function generateForCompte(string $numeroCompte, string $userId): string
    {
        // Créer le dossier qrcodes s'il n'existe pas
        $directory = 'qrcodes';
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }

        // Générer un nom de fichier unique
        $filename = 'qr_' . $numeroCompte . '_' . Str::random(8) . '.png';
        $path = $directory . '/' . $filename;

        // Données à encoder dans le QR code
        $qrData = json_encode([
            'type' => 'compte_om_pay',
            'numero_compte' => $numeroCompte,
            'user_id' => $userId,
            'timestamp' => now()->toISOString()
        ]);

        // Générer le QR code avec Endroid\QrCode (utilise GD par défaut)
        $qrCode = QrCode::create($qrData)
            ->setSize(300)
            ->setMargin(10);

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $qrCode = $result->getString();

        // Sauvegarder le fichier
        Storage::disk('public')->put($path, $qrCode);

        // Retourner l'URL publique du QR code
        return asset('storage/' . $path);
    }

    /**
     * Supprimer un QR code
     *
     * @param string $qrCodeUrl
     * @return bool
     */
    public static function deleteQrCode(string $qrCodeUrl): bool
    {
        try {
            // Extraire le chemin relatif depuis l'URL
            $path = str_replace(asset('storage/'), '', $qrCodeUrl);

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Régénérer un QR code pour un compte
     *
     * @param string $numeroCompte
     * @param string $userId
     * @param string|null $oldQrCodeUrl
     * @return string
     */
    public static function regenerateForCompte(string $numeroCompte, string $userId, ?string $oldQrCodeUrl = null): string
    {
        // Supprimer l'ancien QR code s'il existe
        if ($oldQrCodeUrl) {
            self::deleteQrCode($oldQrCodeUrl);
        }

        // Générer un nouveau QR code
        return self::generateForCompte($numeroCompte, $userId);
    }
}