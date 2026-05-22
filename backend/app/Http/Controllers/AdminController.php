<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\User;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Authentification administrateur.
     * Utilise un mot de passe d'admin défini dans le .env
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $adminPassword = env('ADMIN_PASSWORD', 'umap@admin2026');

        if ($request->password !== $adminPassword) {
            return response()->json(['message' => 'Mot de passe administrateur invalide.'], 401);
        }

        // Générer un token admin simple
        $token = bin2hex(random_bytes(32));
        cache()->put('admin_token_' . $token, true, now()->addHours(12));

        return response()->json([
            'token' => $token,
            'message' => 'Connexion admin réussie.'
        ]);
    }

    /**
     * Vérifier la validité du token admin.
     */
    public function verify(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token || !cache()->get('admin_token_' . $token)) {
            return response()->json(['valid' => false], 401);
        }
        return response()->json(['valid' => true]);
    }

    /**
     * Statistiques globales pour le dashboard.
     */
    public function stats()
    {
        $totalUsers = User::count();
        $totalPlaces = Place::count();
        $pendingPlaces = Place::where('status', 'pending')->count();
        $approvedPlaces = Place::where('status', 'approved')->count();
        $totalMessages = Message::count();

        // Nouveaux utilisateurs des 7 derniers jours
        $recentUsers = User::where('created_at', '>=', now()->subDays(7))->count();

        // Messages des 7 derniers jours
        $recentMessages = Message::where('created_at', '>=', now()->subDays(7))->count();

        // Lieux par catégorie
        $placesByCategory = Place::selectRaw('category, count(*) as count')
            ->groupBy('category')
            ->orderByDesc('count')
            ->get();

        // Activité quotidienne (7 derniers jours) - Inscriptions
        $dailySignups = User::selectRaw("DATE(created_at) as date, count(*) as count")
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'totalUsers' => $totalUsers,
            'totalPlaces' => $totalPlaces,
            'pendingPlaces' => $pendingPlaces,
            'approvedPlaces' => $approvedPlaces,
            'totalMessages' => $totalMessages,
            'recentUsers' => $recentUsers,
            'recentMessages' => $recentMessages,
            'placesByCategory' => $placesByCategory,
            'dailySignups' => $dailySignups,
        ]);
    }

    /**
     * Liste tous les utilisateurs.
     */
    public function users()
    {
        return response()->json(
            User::orderByDesc('created_at')->get(['id', 'name', 'email', 'created_at'])
        );
    }

    /**
     * Supprimer un utilisateur.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    /**
     * Liste tous les lieux (incluant les en attente).
     */
    public function places()
    {
        return response()->json(
            Place::orderByDesc('created_at')->get()
        );
    }

    /**
     * Approuver un lieu en attente.
     */
    public function approvePlace($id)
    {
        $place = Place::findOrFail($id);
        $place->status = 'approved';
        $place->save();
        return response()->json(['message' => 'Lieu approuvé avec succès.']);
    }

    /**
     * Rejeter / Supprimer un lieu.
     */
    public function deletePlace($id)
    {
        $place = Place::findOrFail($id);
        $place->delete();
        return response()->json(['message' => 'Lieu supprimé.']);
    }

    /**
     * Messages récents (les 50 derniers).
     */
    public function messages()
    {
        $messages = Message::with(['sender:id,name,email', 'receiver:id,name,email'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();
        return response()->json($messages);
    }
}
