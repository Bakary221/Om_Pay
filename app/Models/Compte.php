<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Compte extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'numero_compte',
        'qr_code_data',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionsEmises()
    {
        return $this->hasMany(Transaction::class, 'compte_emetteur_id');
    }

    public function transactionsRecues()
    {
        return $this->hasMany(Transaction::class, 'compte_destinataire_id');
    }

    /**
     * Get current balance (calculated from transactions)
     */
    public function getSoldeAttribute(): float
    {
        $depots = $this->transactionsRecues()->where('statut', 'reussi')->sum('montant');
        $retraits = $this->transactionsEmises()->where('statut', 'reussi')->sum('montant') +
                   $this->transactionsEmises()->where('statut', 'reussi')->sum('frais');

        return $depots - $retraits;
    }

    /**
     * Check if account has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->solde >= $amount;
    }

    /**
     * Debit account (no longer used - balance is calculated)
     */
    public function debit(float $amount): bool
    {
        if (!$this->hasSufficientBalance($amount)) {
            return false;
        }

        // Balance is calculated from transactions, no direct database update needed
        return true;
    }

    /**
     * Credit account (no longer used - balance is calculated)
     */
    public function credit(float $amount): void
    {
        // Balance is calculated from transactions, no direct database update needed
    }

    /**
     * Generate account number
     */
    public static function generateNumeroCompte(): string
    {
        return 'OM-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
    }

    /**
     * Generate QR code data
     */
    public function generateQrCodeData(): string
    {
        return json_encode([
            'type' => 'compte',
            'numero_compte' => $this->numero_compte,
            'user_id' => $this->user_id,
        ]);
    }

    /**
     * Generate and save QR code file
     */
    public function generateQrCodeFile(): string
    {
        try {
            $qrCodeData = $this->generateQrCodeData();
            $filename = 'qrcode_' . $this->numero_compte . '.png';
            $path = storage_path('app/qrcodes/' . $filename);

            // Ensure directory exists
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Generate QR code using GD backend (no imagick needed)
            $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(300)
                ->margin(4)
                ->generate($qrCodeData);

            // Save to file
            file_put_contents($path, $qrCode);

            return $filename;
        } catch (\Exception $e) {
            // Log error but don't fail the registration
            \Illuminate\Support\Facades\Log::error('Failed to generate QR code: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Get QR code file path
     */
    public function getQrCodePath(): string
    {
        return storage_path('app/qrcodes/qrcode_' . $this->numero_compte . '.png');
    }

    /**
     * Check if QR code file exists
     */
    public function hasQrCodeFile(): bool
    {
        return file_exists($this->getQrCodePath());
    }
}
