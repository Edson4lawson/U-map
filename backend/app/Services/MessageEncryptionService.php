<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;

class MessageEncryptionService
{
    /**
     * Encrypt message content.
     */
    public function encrypt(string $content): string
    {
        try {
            // Laravel's Crypt uses AES-256-CBC by default
            return Crypt::encryptString($content);
        } catch (\Exception $e) {
            Log::error('Failed to encrypt message', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to encrypt message content');
        }
    }

    /**
     * Decrypt message content.
     */
    public function decrypt(string $encryptedContent): string
    {
        try {
            return Crypt::decryptString($encryptedContent);
        } catch (DecryptException $e) {
            Log::error('Failed to decrypt message', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to decrypt message content');
        } catch (\Exception $e) {
            Log::error('Unexpected error during decryption', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Unexpected decryption error');
        }
    }

    /**
     * Check if content is encrypted.
     */
    public function isEncrypted(string $content): bool
    {
        try {
            Crypt::decryptString($content);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
