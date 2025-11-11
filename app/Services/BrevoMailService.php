<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use SendGrid;
use SendGrid\Mail\Mail;

class BrevoMailService
{
    /**
     * Send OTP email using SendGrid API
     *
     * @param string $toEmail
     * @param string $toName
     * @param string $otp
     * @return bool
     */
    public static function sendOtp($toEmail, $toName, $otp)
    {
        try {
            $apiKey = config('services.sendgrid.api_key');
            $senderEmail = config('services.sendgrid.sender_email');
            $senderName = config('services.sendgrid.sender_name');

            if (!$apiKey || !$senderEmail || !$senderName) {
                Log::error('SendGrid configuration missing');
                return false;
            }

            $email = new Mail();
            $email->setFrom($senderEmail, $senderName);
            $email->setSubject('Votre code de vérification - OM PAY');
            $email->addTo($toEmail, $toName);
            $email->addContent("text/html", self::getOtpEmailTemplate($toName, $otp));

            $sendgrid = new SendGrid($apiKey);
            $response = $sendgrid->send($email);

            if ($response->statusCode() >= 200 && $response->statusCode() < 300) {
                Log::info("OTP email sent successfully to {$toEmail} via SendGrid");
                return true;
            } else {
                Log::error("Failed to send OTP email to {$toEmail} via SendGrid: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending OTP email to {$toEmail} via SendGrid: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the HTML template for OTP email
     *
     * @param string $toName
     * @param string $otp
     * @return string
     */
    private static function getOtpEmailTemplate($toName, $otp)
    {
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Votre code de vérification - OM PAY</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    background-color: #f4f4f4;
                }
                .container {
                    background-color: #ffffff;
                    padding: 30px;
                    border-radius: 10px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                }
                .logo {
                    font-size: 24px;
                    font-weight: bold;
                    color: #007bff;
                    margin-bottom: 10px;
                }
                .otp-code {
                    background-color: #f8f9fa;
                    border: 2px solid #007bff;
                    border-radius: 5px;
                    padding: 20px;
                    text-align: center;
                    font-size: 32px;
                    font-weight: bold;
                    color: #007bff;
                    letter-spacing: 5px;
                    margin: 20px 0;
                }
                .warning {
                    background-color: #fff3cd;
                    border: 1px solid #ffeaa7;
                    border-radius: 5px;
                    padding: 15px;
                    margin: 20px 0;
                    color: #856404;
                }
                .footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #dee2e6;
                    color: #6c757d;
                    font-size: 14px;
                }
                .security-note {
                    background-color: #e7f3ff;
                    border-left: 4px solid #007bff;
                    padding: 15px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <div class="logo">OM PAY</div>
                    <h2>Code de vérification</h2>
                </div>

                <p>Bonjour <strong>' . htmlspecialchars($toName) . '</strong>,</p>

                <p>Pour finaliser votre inscription et sécuriser votre compte OM PAY, veuillez utiliser le code de vérification suivant :</p>

                <div class="otp-code">' . htmlspecialchars($otp) . '</div>

                <div class="warning">
                    <strong>⚠️ Important :</strong> Ce code expire dans <strong>10 minutes</strong>. Ne partagez jamais ce code avec qui que ce soit.
                </div>

                <div class="security-note">
                    <strong>Conseils de sécurité :</strong>
                    <ul>
                        <li>Ce code est personnel et confidentiel</li>
                        <li>Ne cliquez pas sur des liens suspects</li>
                        <li>OM PAY ne vous demandera jamais votre code par téléphone</li>
                    </ul>
                </div>

                <p>Si vous n\'avez pas demandé ce code, veuillez ignorer cet email.</p>

                <p>Cordialement,<br>
                L\'équipe OM PAY</p>

                <div class="footer">
                    <p>Cette adresse email ne peut pas recevoir de réponses.<br>
                    Pour toute assistance, contactez notre support.</p>
                    <p>&copy; 2024 OM PAY. Tous droits réservés.</p>
                </div>
            </div>
        </body>
        </html>';
    }
}