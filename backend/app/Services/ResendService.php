<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendService
{
    protected $apiKey;
    protected $fromEmail;
    protected $fromName;

    public function __construct()
    {
        $this->apiKey = env('RESEND_API_KEY');
        $this->fromEmail = env('MAIL_FROM_ADDRESS', 'contact@umap-uac.bj');
        $this->fromName = env('MAIL_FROM_NAME', 'U-Map');
    }

    /**
     * Send an email using Resend API
     */
    public function sendEmail($to, $subject, $htmlContent, $textContent = null)
    {
        if (empty($this->apiKey)) {
            Log::warning('Resend API key not configured. Email not sent.', [
                'to' => $to,
                'subject' => $subject
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from' => "{$this->fromName} <{$this->fromEmail}>",
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlContent,
                'text' => $textContent ?? strip_tags($htmlContent),
            ]);

            if ($response->successful()) {
                Log::info('Email sent successfully via Resend', [
                    'to' => $to,
                    'subject' => $subject,
                    'resend_id' => $response->json('id')
                ]);
                return true;
            } else {
                Log::error('Failed to send email via Resend', [
                    'to' => $to,
                    'subject' => $subject,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending email via Resend', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset($to, $resetUrl)
    {
        $subject = "Réinitialisation de votre mot de passe - U-Map";
        
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>U-Map</h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0;'>Votre guide du campus UAC</p>
                </div>
                <div style='background: #f9fafb; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #1f2937; margin-top: 0;'>Réinitialisation de mot de passe</h2>
                    <p style='color: #4b5563; line-height: 1.6;'>Bonjour,</p>
                    <p style='color: #4b5563; line-height: 1.6;'>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte U-Map.</p>
                    <p style='color: #4b5563; line-height: 1.6;'>Cliquez sur le bouton ci-dessous pour définir votre nouveau mot de passe :</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetUrl}' style='background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Réinitialiser mon mot de passe</a>
                    </div>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Ce lien expire dans 60 minutes.</p>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                </div>
                <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                    <p>&copy; 2025 U-Map. Tous droits réservés.</p>
                    <p>Contact: contact@umap-uac.bj</p>
                </div>
            </div>
        ";

        $textContent = "Bonjour,\n\nVous avez demandé la réinitialisation de votre mot de passe pour votre compte U-Map.\n\nCliquez sur ce lien pour définir votre nouveau mot de passe: {$resetUrl}\n\nCe lien expire dans 60 minutes.\n\nSi vous n'avez pas demandé cette réinitialisation, ignorez cet email.\n\n© 2025 U-Map. Tous droits réservés.";

        return $this->sendEmail($to, $subject, $htmlContent, $textContent);
    }

    /**
     * Send magic link email
     */
    public function sendMagicLink($to, $magicLinkUrl)
    {
        $subject = "Lien de connexion magique - U-Map";
        
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>U-Map</h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0;'>Votre guide du campus UAC</p>
                </div>
                <div style='background: #f9fafb; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #1f2937; margin-top: 0;'>Connexion sans mot de passe</h2>
                    <p style='color: #4b5563; line-height: 1.6;'>Bonjour,</p>
                    <p style='color: #4b5563; line-height: 1.6;'>Cliquez sur le bouton ci-dessous pour vous connecter à votre compte U-Map sans mot de passe :</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$magicLinkUrl}' style='background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>Me connecter maintenant</a>
                    </div>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Ce lien expire dans 15 minutes.</p>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Si vous n'avez pas demandé cette connexion, ignorez cet email.</p>
                </div>
                <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                    <p>&copy; 2025 U-Map. Tous droits réservés.</p>
                    <p>Contact: contact@umap-uac.bj</p>
                </div>
            </div>
        ";

        $textContent = "Bonjour,\n\nCliquez sur ce lien pour vous connecter à votre compte U-Map sans mot de passe: {$magicLinkUrl}\n\nCe lien expire dans 15 minutes.\n\nSi vous n'avez pas demandé cette connexion, ignorez cet email.\n\n© 2025 U-Map. Tous droits réservés.";

        return $this->sendEmail($to, $subject, $htmlContent, $textContent);
    }

    /**
     * Send 2FA code email
     */
    public function send2FACode($to, $code)
    {
        $subject = "Code de vérification 2FA - U-Map";
        
        $htmlContent = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>U-Map</h1>
                    <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0;'>Votre guide du campus UAC</p>
                </div>
                <div style='background: #f9fafb; padding: 30px; border-radius: 10px;'>
                    <h2 style='color: #1f2937; margin-top: 0;'>Code de vérification</h2>
                    <p style='color: #4b5563; line-height: 1.6;'>Bonjour,</p>
                    <p style='color: #4b5563; line-height: 1.6;'>Voici votre code de vérification à deux facteurs :</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <div style='background: #e0e7ff; color: #4338ca; font-size: 32px; font-weight: bold; padding: 20px; border-radius: 10px; letter-spacing: 5px;'>{$code}</div>
                    </div>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Ce code expire dans 5 minutes.</p>
                    <p style='color: #4b5563; line-height: 1.6; font-size: 14px;'>Ne partagez jamais ce code avec personne.</p>
                </div>
                <div style='text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px;'>
                    <p>&copy; 2025 U-Map. Tous droits réservés.</p>
                    <p>Contact: contact@umap-uac.bj</p>
                </div>
            </div>
        ";

        $textContent = "Bonjour,\n\nVoici votre code de vérification à deux facteurs: {$code}\n\nCe code expire dans 5 minutes.\n\nNe partagez jamais ce code avec personne.\n\n© 2025 U-Map. Tous droits réservés.";

        return $this->sendEmail($to, $subject, $htmlContent, $textContent);
    }
}
