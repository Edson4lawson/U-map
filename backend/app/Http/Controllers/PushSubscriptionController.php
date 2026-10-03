<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Get the public VAPID key for frontend push subscription.
     */
    public function getPublicKey(): JsonResponse
    {
        $publicKey = config('webpush.vapid.public_key') ?? env('VAPID_PUBLIC_KEY');
        
        return response()->json([
            'publicKey' => $publicKey,
        ]);
    }

    /**
     * Store or update a user's push subscription.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }

        $endpoint = $request->input('endpoint');
        $key = $request->input('keys.p256dh');
        $token = $request->input('keys.auth');
        $contentEncoding = $request->input('contentEncoding', 'aesgcm');

        try {
            $user->updatePushSubscription($endpoint, $key, $token, $contentEncoding);

            return response()->json([
                'success' => true,
                'message' => 'Souscription WebPush enregistrée avec succès.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to save push subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de l\'enregistrement de la souscription push.',
            ], 500);
        }
    }

    /**
     * Delete a push subscription.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'endpoint' => 'required|url',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Non authentifié.'], 401);
        }

        $endpoint = $request->input('endpoint');

        try {
            $user->deletePushSubscription($endpoint);

            return response()->json([
                'success' => true,
                'message' => 'Souscription WebPush supprimée avec succès.',
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete push subscription: ' . $e->getMessage());
            return response()->json([
                'error' => 'Erreur lors de la suppression de la souscription push.',
            ], 500);
        }
    }
}
