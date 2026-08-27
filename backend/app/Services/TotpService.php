<?php

namespace App\Services;

class TotpService
{
    private static string $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a random 16-character Base32 secret key.
     */
    public function generateSecret(): string
    {
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= self::$base32Chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Get QR Code URL for Google Authenticator or other TOTP apps.
     */
    public function getQrCodeUrl(string $username, string $secret, string $issuer = 'U-Map'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($username) .
               '?secret=' . $secret .
               '&issuer=' . rawurlencode($issuer);
    }

    /**
     * Verify a TOTP code against a secret key (allows 30s window drift).
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = str_replace(' ', '', $code);
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        $decodedSecret = $this->base32Decode($secret);
        if (!$decodedSecret) {
            return false;
        }

        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $timeSlice = $currentTimeSlice + $i;
            $calculatedCode = $this->calculateOtp($decodedSecret, $timeSlice);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate OTP code for a given time slice.
     */
    private function calculateOtp(string $decodedSecret, int $timeSlice): string
    {
        // Pack time slice into 8-byte binary string
        $timeBinary = pack('N*', 0) . pack('N*', $timeSlice);

        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $timeBinary, $decodedSecret, true);

        // Dynamic truncation
        $offset = ord($hmac[19]) & 0xf;
        $value = (((ord($hmac[$offset]) & 0x7f) << 24) |
                  ((ord($hmac[$offset + 1]) & 0xff) << 16) |
                  ((ord($hmac[$offset + 2]) & 0xff) << 8) |
                  (ord($hmac[$offset + 3]) & 0xff));

        $otp = $value % 1000000;
        return str_pad((string)$otp, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Decode a Base32 string.
     */
    private function base32Decode(string $base32): ?string
    {
        $base32 = strtoupper($base32);
        $base32 = str_replace('=', '', $base32);
        
        if (preg_match('/[^' . self::$base32Chars . ']/', $base32)) {
            return null;
        }

        $buffer = 0;
        $bufferSize = 0;
        $decoded = '';

        for ($i = 0, $len = strlen($base32); $i < $len; $i++) {
            $charValue = strpos(self::$base32Chars, $base32[$i]);
            $buffer = ($buffer << 5) | $charValue;
            $bufferSize += 5;

            if ($bufferSize >= 8) {
                $bufferSize -= 8;
                $decoded .= chr(($buffer >> $bufferSize) & 0xff);
            }
        }

        return $decoded;
    }
}
