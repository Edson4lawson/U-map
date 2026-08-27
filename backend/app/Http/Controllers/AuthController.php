<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Models\User;
use App\Services\TotpService;
use App\Services\ResendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    protected TotpService $totpService;
    protected ResendService $resendService;

    public function __construct(TotpService $totpService, ResendService $resendService)
    {
        $this->totpService = $totpService;
        $this->resendService = $resendService;
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $this->logDevice($user, $request);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('auth_token')->plainTextToken,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $ip = $request->ip();
        $attemptsKey = "login_attempts:{$ip}";
        $attempts = Cache::get($attemptsKey, 0);

        // Check if CAPTCHA is required
        if ($attempts >= 3) {
            if (!$request->captcha_answer || !$request->captcha_token) {
                return response()->json([
                    'message' => 'Validation du CAPTCHA requise.',
                    'captcha_required' => true,
                ], 422);
            }

            $expected = Cache::get("captcha:{$request->captcha_token}");
            if ($expected === null || (int)$request->captcha_answer !== (int)$expected) {
                return response()->json([
                    'message' => 'Le code de sécurité CAPTCHA est incorrect.',
                    'captcha_required' => true,
                ], 422);
            }
        }

        // Allow login with email or username
        $user = User::where('email', $request->identifier)
            ->orWhere('name', $request->identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Cache::put($attemptsKey, ++$attempts, now()->addHour());
            return response()->json([
                'message' => 'Identifiants incorrects.',
                'captcha_required' => $attempts >= 3,
            ], 401);
        }

        if ($user->is_restricted) {
            return response()->json([
                'message' => 'Votre compte a été restreint suite à des signalements.',
            ], 403);
        }

        // Reset failed login attempts on success
        Cache::forget($attemptsKey);

        // Check if 2FA is active
        if ($user->two_factor_confirmed_at) {
            $tempToken = Str::random(60);
            Cache::put("temp_login:{$tempToken}", $user->id, now()->addMinutes(10));
            return response()->json([
                'two_factor_required' => true,
                'temp_token' => $tempToken,
            ]);
        }

        $this->logDevice($user, $request);

        // Handle "remember me" option
        $expiresAt = $request->remember ? now()->addMonths(1) : null;
        $token = $user->createToken('auth_token');
        if ($expiresAt) {
            $token->accessToken->expires_at = $expiresAt;
            $token->accessToken->save();
        }

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnecté']);
    }

    /* ── CAPTCHA Endpoints ────────────────────────────────────────── */

    public function getCaptcha()
    {
        $num1 = random_int(1, 10);
        $num2 = random_int(1, 10);
        $token = Str::random(32);
        
        Cache::put("captcha:{$token}", $num1 + $num2, now()->addMinutes(5));

        return response()->json([
            'question' => "Combien font {$num1} + {$num2} ?",
            'captcha_token' => $token,
        ]);
    }

    /* ── 2FA Endpoints ────────────────────────────────────────────── */

    public function enable2fa(Request $request)
    {
        $user = $request->user();
        $secret = $this->totpService->generateSecret();
        
        // Save temporarily in cache to verify before confirmation
        Cache::put("2fa_secret:{$user->id}", $secret, now()->addMinutes(15));
        
        $qrCodeUrl = $this->totpService->getQrCodeUrl($user->name, $secret);

        return response()->json([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    public function confirm2fa(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();
        $secret = Cache::get("2fa_secret:{$user->id}");

        if (!$secret) {
            return response()->json(['message' => 'Le délai d\'activation a expiré. Veuillez réessayer.'], 400);
        }

        if (!$this->totpService->verifyCode($secret, $request->code)) {
            return response()->json(['message' => 'Code OTP incorrect.'], 400);
        }

        // Generate recovery codes
        $recoveryCodes = [];
        for ($i = 0; $i < 8; $i++) {
            $recoveryCodes[] = Str::random(10);
        }

        $user->two_factor_secret = encrypt($secret);
        $user->two_factor_recovery_codes = encrypt(json_encode($recoveryCodes));
        $user->two_factor_confirmed_at = now();
        $user->save();

        Cache::forget("2fa_secret:{$user->id}");

        return response()->json([
            'message' => 'Double authentification activée avec succès.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disable2fa(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Mot de passe incorrect.'], 422);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        return response()->json(['message' => 'Double authentification désactivée.']);
    }

    public function verify2fa(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code' => 'required|string',
        ]);

        $userId = Cache::get("temp_login:{$request->temp_token}");
        if (!$userId) {
            return response()->json(['message' => 'Session expirée ou invalide.'], 400);
        }

        $user = User::findOrFail($userId);
        $secret = decrypt($user->two_factor_secret);

        // Rate limiting OTP attempts
        $otpAttemptsKey = "otp_attempts:{$request->temp_token}";
        $otpAttempts = Cache::get($otpAttemptsKey, 0);
        if ($otpAttempts >= 5) {
            return response()->json(['message' => 'Trop de tentatives. Réessayez dans 15 minutes.'], 429);
        }

        // Verify either TOTP code or recovery code
        $verified = false;
        
        if (strlen($request->code) === 6 && $this->totpService->verifyCode($secret, $request->code)) {
            $verified = true;
        } else {
            // Check recovery codes
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
            $key = array_search($request->code, $recoveryCodes);
            if ($key !== false) {
                $verified = true;
                // Revoke used recovery code
                unset($recoveryCodes[$key]);
                $user->two_factor_recovery_codes = encrypt(json_encode(array_values($recoveryCodes)));
                $user->save();
            }
        }

        if (!$verified) {
            Cache::put($otpAttemptsKey, $otpAttempts + 1, now()->addMinutes(15));
            return response()->json(['message' => 'Code de sécurité ou de récupération incorrect.'], 400);
        }

        Cache::forget("temp_login:{$request->temp_token}");

        $this->logDevice($user, $request);

        $token = $user->createToken('auth_token');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    /* ── Magic Link Endpoints ─────────────────────────────────────── */

    public function sendMagicLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Si cet e-mail est enregistré, un lien de connexion vous a été envoyé.'
            ]);
        }

        $magicToken = Str::random(60);
        Cache::put("magic_token:{$magicToken}", $user->id, now()->addMinutes(15));

        // Send email using Resend
        $magicLinkUrl = app()->environment('local') 
            ? "http://localhost:5173/magic-link-login?token={$magicToken}"
            : "https://umap-ten.vercel.app/magic-link-login?token={$magicToken}";
        
        $this->resendService->sendMagicLink($user->email, $magicLinkUrl);

        // For simulation and demonstration, return the magic link in API response if environment is local
        return response()->json([
            'message' => 'Si cet e-mail est enregistré, un lien de connexion vous a été envoyé.',
            'magic_link' => app()->environment('local') ? $magicLinkUrl : null,
        ]);
    }

    public function loginWithMagicLink(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        $userId = Cache::get("magic_token:{$request->token}");
        if (!$userId) {
            return response()->json(['message' => 'Le lien magique est invalide ou a expiré.'], 400);
        }

        $user = User::findOrFail($userId);
        Cache::forget("magic_token:{$request->token}");

        $this->logDevice($user, $request);
        $token = $user->createToken('auth_token');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    /* ── Social Login Endpoint ─────────────────────────────────────── */

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:google,github,apple',
            'token' => 'required|string',
            'email' => 'required|email',
            'name' => 'required|string',
        ]);

        // Validate Google token with Google API
        if ($request->provider === 'google') {
            $googleClientId = env('GOOGLE_CLIENT_ID');
            $response = \Illuminate\Support\Facades\Http::get("https://oauth2.googleapis.com/tokeninfo?id_token={$request->token}");

            if (!$response->successful()) {
                return response()->json(['message' => 'Token Google invalide.'], 401);
            }

            $tokenInfo = $response->json();

            // Verify the token is issued for our app (if GOOGLE_CLIENT_ID is configured)
            if ($googleClientId && $tokenInfo['aud'] !== $googleClientId) {
                return response()->json(['message' => 'Token non autorisé pour cette application.'], 401);
            }

            // Verify email matches
            if (isset($tokenInfo['email']) && $tokenInfo['email'] !== $request->email) {
                return response()->json(['message' => 'Email ne correspond pas.'], 401);
            }
        }

        // Match or create user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'social_provider' => $request->provider,
                'social_id' => $request->token,
                'password' => Hash::make(Str::random(16)), // Dummy password
            ]);
        } else {
            $user->update([
                'social_provider' => $request->provider,
                'social_id' => $request->token,
            ]);
        }

        $this->logDevice($user, $request);
        $token = $user->createToken('auth_token');

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ]);
    }

    /* ── Helper methods ────────────────────────────────────────────── */

    private function logDevice(User $user, Request $request)
    {
        $userAgent = $request->userAgent();
        $ip = $request->ip();

        $exists = $user->devices()->where('ip_address', $ip)->where('user_agent', $userAgent)->exists();

        if (!$exists) {
            $user->devices()->create([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device_name' => $this->parseDeviceName($userAgent),
                'last_active_at' => now(),
            ]);
        } else {
            $user->devices()->where('ip_address', $ip)->where('user_agent', $userAgent)->update([
                'last_active_at' => now(),
            ]);
        }
    }

    private function parseDeviceName($userAgent): string
    {
        if (preg_match('/(android|iphone|ipad|windows|macintosh|linux)/i', $userAgent, $matches)) {
            return ucfirst(strtolower($matches[1]));
        }
        return 'Appareil inconnu';
    }

    /* ── Existing endpoints ────────────────────────────────────────── */

    public function checkUsernameAvailability(Request $request)
    {
        $request->validate([
            'username' => 'required|string|min:3|max:30|regex:/^[a-zA-Z0-9_-]+$/'
        ], [
            'username.regex' => 'Le nom d\'utilisateur ne doit contenir que des lettres, chiffres, tirets et underscores.'
        ]);

        $exists = User::where('name', $request->username)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Ce nom d\'utilisateur est déjà pris.' : 'Ce nom d\'utilisateur est disponible.'
        ]);
    }

    public function checkEmailAvailability(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255'
        ]);

        $exists = User::where('email', $request->email)->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Cette adresse e-mail est déjà utilisée.' : 'Cette adresse e-mail est disponible.'
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Si cette adresse e-mail existe, un lien de réinitialisation a été envoyé.'
            ]);
        }

        // Utiliser la table password_reset_tokens native de Laravel avec TTL 60 minutes
        $token = Str::random(60);
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // Send email using Resend
        $resetUrl = app()->environment('local') 
            ? "http://localhost:5173/reset-password?token={$token}&email=" . urlencode($user->email)
            : "https://umap-ten.vercel.app/reset-password?token={$token}&email=" . urlencode($user->email);
        
        $this->resendService->sendPasswordReset($user->email, $resetUrl);

        return response()->json([
            'message' => 'Si cette adresse e-mail existe, un lien de réinitialisation a été envoyé.',
            'reset_link' => app()->environment('local') ? $resetUrl : null
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        $resetToken = \DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$resetToken || !Hash::check($request->token, $resetToken->token)) {
            return response()->json(['message' => 'Token invalide ou expiré.'], 400);
        }

        // Vérifier expiration (60 minutes)
        if ($resetToken->created_at < now()->subMinutes(60)) {
            \DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Token expiré. Veuillez demander un nouveau lien.'], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Supprimer le token utilisé
        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
        ]);
    }
}
