<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\AdminAction;
use App\Models\Message;
use App\Models\Place;
use App\Models\Report;
use App\Models\User;
use App\Models\UserSanction;
use App\Notifications\UserWarningNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Obtenir l'ID de l'admin authentifié depuis le token.
     */
    protected function getAdminId(Request $request): ?int
    {
        $token = $request->bearerToken();
        return $token ? Cache::get('admin_token_' . $token) : null;
    }

    /**
     * Authentification administrateur.
     * Utilise un hash bcrypt stocké en base de données
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $admin = Admin::first();

        // Si aucun admin n'existe encore en base (ex: premier déploiement prod sans seed), l'initialiser
        if (!$admin) {
            $defaultPassword = config('app.admin_password', 'umapAdmin2026!');
            $admin = Admin::create([
                'username' => 'admin',
                'password' => $defaultPassword,
            ]);
        }

        if (!$admin->verifyPassword($request->password)) {
            return response()->json(['message' => 'Mot de passe administrateur invalide.'], 401);
        }

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = bin2hex(random_bytes(32));
        Cache::put('admin_token_' . $token, $admin->id, now()->addHours(12));

        AdminAction::log('login', 'admin', $admin->id, ['ip' => $request->ip()], $admin->id);

        return response()->json([
            'token' => $token,
            'message' => 'Connexion admin réussie.',
        ]);
    }

    /**
     * Vérifier la validité du token admin.
     */
    public function verify(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token || !Cache::get('admin_token_' . $token)) {
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

        $pendingReports = Report::where('status', 'pending')->count();
        $inProgressReports = Report::where('status', 'in_progress')->count();
        $resolvedReports = Report::where('status', 'resolved')->count();
        $activeSanctions = UserSanction::where('is_active', true)->count();

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
            'pendingReports' => $pendingReports,
            'inProgressReports' => $inProgressReports,
            'resolvedReports' => $resolvedReports,
            'activeSanctions' => $activeSanctions,
            'placesByCategory' => $placesByCategory,
            'dailySignups' => $dailySignups,
        ]);
    }

    /**
     * Métriques avancées et données analytics détaillées (Phase 4).
     */
    public function analytics(Request $request)
    {
        @set_time_limit(120);

        $period = $request->input('period', '30d');
        $days = match($period) {
            '7d' => 7,
            '90d' => 90,
            default => 30,
        };

        $cacheKey = "admin_analytics_{$period}";
        if ($request->boolean('refresh')) {
            Cache::forget($cacheKey);
        }

        $data = Cache::remember($cacheKey, 20, function () use ($days, $period) {
            $startDate = now()->subDays($days);

            // 1. Inscriptions quotidiennes
            $dailyUsers = User::selectRaw("DATE(created_at) as date, count(*) as count")
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // 2. Messages quotidiens
            $dailyMessages = Message::selectRaw("DATE(created_at) as date, count(*) as count")
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // 3. Signalements quotidiens
            $dailyReports = Report::selectRaw("DATE(created_at) as date, count(*) as count")
                ->where('created_at', '>=', $startDate)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // 4. Métriques de modération
            $totalReports = Report::count();
            $resolvedReports = Report::where('status', 'resolved')->count();
            $dismissedReports = Report::where('status', 'dismissed')->count();
            $pendingReports = Report::where('status', 'pending')->count();
            $inProgressReports = Report::where('status', 'in_progress')->count();

            $resolutionRate = $totalReports > 0 ? round(($resolvedReports / $totalReports) * 100, 1) : 100;

            // Temps moyen de traitement (en minutes)
            $resolvedReportsList = Report::whereNotNull('resolved_at')->whereNotNull('created_at')->get(['created_at', 'resolved_at']);
            $avgResolutionMinutes = 0;
            if ($resolvedReportsList->count() > 0) {
                $totalMins = $resolvedReportsList->sum(fn($r) => $r->created_at->diffInMinutes($r->resolved_at));
                $avgResolutionMinutes = round($totalMins / $resolvedReportsList->count());
            }

            // Répartition des signalements par priorité
            $reportsByPriority = Report::selectRaw("priority, count(*) as count")
                ->groupBy('priority')
                ->get();

            // Répartition des signalements par type
            $reportsByType = Report::selectRaw("reportable_type, count(*) as count")
                ->groupBy('reportable_type')
                ->get();

            // Répartition des sanctions par type
            $sanctionsByType = UserSanction::selectRaw("type, count(*) as count")
                ->groupBy('type')
                ->get();

            // 5. Démographie des utilisateurs
            $usersByRole = User::selectRaw("COALESCE(role, 'user') as role, count(*) as count")
                ->groupBy(DB::raw("COALESCE(role, 'user')"))
                ->get();

            $usersByFaculty = User::whereNotNull('faculty')
                ->where('faculty', '!=', '')
                ->selectRaw("faculty, count(*) as count")
                ->groupBy('faculty')
                ->orderByDesc('count')
                ->take(8)
                ->get();

            // 6. Lieux et cartographie
            $placesByStatus = Place::selectRaw("status, count(*) as count")
                ->groupBy('status')
                ->get();

            $placesByCategory = Place::selectRaw("category, count(*) as count")
                ->groupBy('category')
                ->orderByDesc('count')
                ->get();

            return [
                'period' => $period,
                'kpis' => [
                    'total_users' => User::count(),
                    'recent_users' => User::where('created_at', '>=', $startDate)->count(),
                    'total_messages' => Message::count(),
                    'recent_messages' => Message::where('created_at', '>=', $startDate)->count(),
                    'total_places' => Place::count(),
                    'total_reports' => $totalReports,
                    'resolved_reports' => $resolvedReports,
                    'pending_reports' => $pendingReports,
                    'resolution_rate' => $resolutionRate,
                    'avg_resolution_minutes' => $avgResolutionMinutes,
                ],
                'daily_users' => $dailyUsers,
                'daily_messages' => $dailyMessages,
                'daily_reports' => $dailyReports,
                'reports_by_priority' => $reportsByPriority,
                'reports_by_type' => $reportsByType,
                'sanctions_by_type' => $sanctionsByType,
                'users_by_role' => $usersByRole,
                'users_by_faculty' => $usersByFaculty,
                'places_by_status' => $placesByStatus,
                'places_by_category' => $placesByCategory,
            ];
        });

        return response()->json($data);
    }

    /**
     * Liste tous les utilisateurs avec leur statut de modération complet.
     */
    public function users(Request $request)
    {
        $query = User::withCount(['reportsReceived', 'reportsSent', 'sanctions' => fn($q) => $q->where('is_active', true)])
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $s = '%' . strtolower($request->search) . '%';
            $query->where(function($q) use ($s) {
                $q->whereRaw('LOWER(name) LIKE ?', [$s])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$s]);
            });
        }

        if ($request->filled('role') && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        $users = $query->get([
            'id', 'name', 'email', 'avatar', 'role', 'faculty', 'study_level', 'student_id',
            'is_restricted', 'is_banned', 'muted_until', 'suspended_until', 'admin_note', 'created_at'
        ]);

        return response()->json($users);
    }

    /**
     * Restreindre / Dérestreindre un utilisateur (switch rapide).
     */
    public function toggleRestrictUser(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);
        $user->is_restricted = !$user->is_restricted;
        $user->save();

        AdminAction::log(
            $user->is_restricted ? 'restrict_user' : 'unrestrict_user',
            'user',
            $user->id,
            ['name' => $user->name],
            $adminId
        );

        return response()->json([
            'message' => $user->is_restricted ? 'Utilisateur restreint avec succès.' : 'Restrictions levées pour cet utilisateur.',
            'is_restricted' => $user->is_restricted,
        ]);
    }

    /**
     * Supprimer un utilisateur.
     */
    public function deleteUser(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);
        $userName = $user->name;
        $user->delete();

        AdminAction::log('delete_user', 'user', $id, ['name' => $userName], $adminId);

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    /**
     * =========================================================================
     * MODÉRATION & SIGNALEMENTS
     * =========================================================================
     */

    /**
     * Liste des signalements filtrables et triés par urgence/date.
     */
    public function reports(Request $request)
    {
        $query = Report::with([
            'reporter:id,name,email,avatar',
            'reportedUser:id,name,email,avatar,is_banned,muted_until,suspended_until,is_restricted',
            'message'
        ]);

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('reportable_type', $request->type);
        }

        // Tri : Urgence (urgent > high > medium > low), puis plus récent
        $reports = $query->orderByRaw("
            CASE priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
                ELSE 5
            END ASC
        ")->orderByDesc('created_at')->get();

        return response()->json($reports);
    }

    /**
     * Contexte du signalement (message signalé + messages avant/après).
     */
    public function getReportContext(int $id)
    {
        $report = Report::with([
            'reporter:id,name,email',
            'reportedUser:id,name,email',
            'message'
        ])->findOrFail($id);

        $contextMessages = collect();
        $targetMessage = null;

        if ($report->reportable_type === 'message' && $report->reportable_id) {
            $targetMessage = Message::with(['sender:id,name', 'receiver:id,name'])
                ->find($report->reportable_id);

            if ($targetMessage) {
                // 5 messages avant
                $before = Message::where(function ($q) use ($targetMessage) {
                    $q->where(function ($sq) use ($targetMessage) {
                        $sq->where('sender_id', $targetMessage->sender_id)
                           ->where('receiver_id', $targetMessage->receiver_id);
                    })->orWhere(function ($sq) use ($targetMessage) {
                        $sq->where('sender_id', $targetMessage->receiver_id)
                           ->where('receiver_id', $targetMessage->sender_id);
                    });
                })
                ->where('created_at', '<', $targetMessage->created_at)
                ->with(['sender:id,name', 'receiver:id,name'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->reverse();

                // 5 messages après
                $after = Message::where(function ($q) use ($targetMessage) {
                    $q->where(function ($sq) use ($targetMessage) {
                        $sq->where('sender_id', $targetMessage->sender_id)
                           ->where('receiver_id', $targetMessage->receiver_id);
                    })->orWhere(function ($sq) use ($targetMessage) {
                        $sq->where('sender_id', $targetMessage->receiver_id)
                           ->where('receiver_id', $targetMessage->sender_id);
                    });
                })
                ->where('created_at', '>', $targetMessage->created_at)
                ->with(['sender:id,name', 'receiver:id,name'])
                ->orderBy('created_at', 'asc')
                ->limit(5)
                ->get();

                $contextMessages = $before->concat([$targetMessage])->concat($after)->values();
            }
        }

        // Si aucun message cible explicite, charger les 10 derniers messages entre les 2 utilisateurs
        if ($contextMessages->isEmpty() && $report->reporter_id && $report->reported_user_id) {
            $contextMessages = Message::where(function ($q) use ($report) {
                $q->where(function ($sq) use ($report) {
                    $sq->where('sender_id', $report->reporter_id)
                       ->where('receiver_id', $report->reported_user_id);
                })->orWhere(function ($sq) use ($report) {
                    $sq->where('sender_id', $report->reported_user_id)
                       ->where('receiver_id', $report->reporter_id);
                });
            })
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->reverse()
            ->values();
        }

        return response()->json([
            'report' => $report,
            'target_message' => $targetMessage,
            'context_messages' => $contextMessages,
        ]);
    }

    /**
     * Mettre à jour le statut d'un signalement.
     */
    public function updateReportStatus(Request $request, int $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,dismissed',
            'admin_notes' => 'nullable|string',
            'priority' => 'nullable|in:low,medium,high,urgent',
        ]);

        $adminId = $this->getAdminId($request);
        $report = Report::findOrFail($id);

        $report->status = $request->status;
        if ($request->has('admin_notes')) {
            $report->admin_notes = $request->admin_notes;
        }
        if ($request->has('priority')) {
            $report->priority = $request->priority;
        }

        if (in_array($request->status, ['resolved', 'dismissed'])) {
            $report->resolved_by = $adminId;
            $report->resolved_at = now();
        }

        $report->save();

        AdminAction::log('update_report_status', 'report', $report->id, [
            'status' => $report->status,
            'notes' => $report->admin_notes,
        ], $adminId);

        return response()->json([
            'message' => 'Statut du signalement mis à jour.',
            'report' => $report,
        ]);
    }

    /**
     * Action graduée : Avertissement
     */
    public function warnUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
            'report_id' => 'nullable|exists:reports,id',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($request->user_id);

        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'type' => 'warning',
            'reason' => $request->reason,
            'is_active' => true,
        ]);

        // Optionnel : envoyer notification push si l'utilisateur a des souscriptions WebPush
        try {
            $user->notify(new UserWarningNotification($request->reason));
        } catch (\Throwable $e) {
            // Silencieux si la notification n'est pas configurée
        }

        if ($request->filled('report_id')) {
            $report = Report::find($request->report_id);
            if ($report) {
                $report->update([
                    'status' => 'resolved',
                    'admin_notes' => ($report->admin_notes ? $report->admin_notes . "\n" : '') . "Sanction appliquée : Avertissement - " . $request->reason,
                    'resolved_by' => $adminId,
                    'resolved_at' => now(),
                ]);
            }
        }

        AdminAction::log('warn_user', 'user', $user->id, ['reason' => $request->reason], $adminId);

        return response()->json([
            'message' => "Avertissement envoyé à {$user->name}.",
            'sanction' => $sanction,
        ]);
    }

    /**
     * Action graduée : Mute temporaire (ne peut plus envoyer de messages)
     */
    public function muteUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
            'duration_hours' => 'required|integer|min:1|max:8760',
            'report_id' => 'nullable|exists:reports,id',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($request->user_id);
        $expiresAt = now()->addHours($request->duration_hours);

        $user->update(['muted_until' => $expiresAt]);

        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'type' => 'mute',
            'reason' => $request->reason,
            'duration_hours' => $request->duration_hours,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        if ($request->filled('report_id')) {
            $report = Report::find($request->report_id);
            if ($report) {
                $report->update([
                    'status' => 'resolved',
                    'admin_notes' => ($report->admin_notes ? $report->admin_notes . "\n" : '') . "Sanction appliquée : Mute ({$request->duration_hours}h) - " . $request->reason,
                    'resolved_by' => $adminId,
                    'resolved_at' => now(),
                ]);
            }
        }

        AdminAction::log('mute_user', 'user', $user->id, [
            'duration_hours' => $request->duration_hours,
            'reason' => $request->reason,
            'expires_at' => $expiresAt->toIso8601String(),
        ], $adminId);

        return response()->json([
            'message' => "{$user->name} a été rendu muet pour {$request->duration_hours} heure(s).",
            'muted_until' => $expiresAt->toIso8601String(),
            'sanction' => $sanction,
        ]);
    }

    /**
     * Action graduée : Suspension temporaire de compte (connexion bloquée)
     */
    public function suspendUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
            'duration_hours' => 'required|integer|min:1|max:8760',
            'report_id' => 'nullable|exists:reports,id',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($request->user_id);
        $expiresAt = now()->addHours($request->duration_hours);

        $user->update(['suspended_until' => $expiresAt]);
        $user->tokens()->delete(); // Déconnecter toutes les sessions actives

        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'type' => 'suspension',
            'reason' => $request->reason,
            'duration_hours' => $request->duration_hours,
            'expires_at' => $expiresAt,
            'is_active' => true,
        ]);

        if ($request->filled('report_id')) {
            $report = Report::find($request->report_id);
            if ($report) {
                $report->update([
                    'status' => 'resolved',
                    'admin_notes' => ($report->admin_notes ? $report->admin_notes . "\n" : '') . "Sanction appliquée : Suspension ({$request->duration_hours}h) - " . $request->reason,
                    'resolved_by' => $adminId,
                    'resolved_at' => now(),
                ]);
            }
        }

        AdminAction::log('suspend_user', 'user', $user->id, [
            'duration_hours' => $request->duration_hours,
            'reason' => $request->reason,
            'expires_at' => $expiresAt->toIso8601String(),
        ], $adminId);

        return response()->json([
            'message' => "{$user->name} a été suspendu pour {$request->duration_hours} heure(s).",
            'suspended_until' => $expiresAt->toIso8601String(),
            'sanction' => $sanction,
        ]);
    }

    /**
     * Action graduée : Bannissement définitif
     */
    public function banUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:1000',
            'report_id' => 'nullable|exists:reports,id',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($request->user_id);

        $user->update([
            'is_banned' => true,
            'is_restricted' => true,
        ]);
        $user->tokens()->delete();

        $sanction = UserSanction::create([
            'user_id' => $user->id,
            'admin_id' => $adminId,
            'type' => 'ban',
            'reason' => $request->reason,
            'is_active' => true,
        ]);

        if ($request->filled('report_id')) {
            $report = Report::find($request->report_id);
            if ($report) {
                $report->update([
                    'status' => 'resolved',
                    'admin_notes' => ($report->admin_notes ? $report->admin_notes . "\n" : '') . "Sanction appliquée : Bannissement définitif - " . $request->reason,
                    'resolved_by' => $adminId,
                    'resolved_at' => now(),
                ]);
            }
        }

        AdminAction::log('ban_user', 'user', $user->id, ['reason' => $request->reason], $adminId);

        return response()->json([
            'message' => "{$user->name} a été définitivement banni.",
            'sanction' => $sanction,
        ]);
    }

    /**
     * Lever les sanctions d'un utilisateur (débannir / réactiver)
     */
    public function unbanUser(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);

        $user->update([
            'is_banned' => false,
            'is_restricted' => false,
            'muted_until' => null,
            'suspended_until' => null,
        ]);

        UserSanction::where('user_id', $user->id)->update(['is_active' => false]);

        AdminAction::log('unban_user', 'user', $user->id, ['name' => $user->name], $adminId);

        return response()->json([
            'message' => "Toutes les sanctions ont été levées pour {$user->name}.",
        ]);
    }

    /**
     * Action graduée : Supprimer un message litigieux seul
     */
    public function deleteMessage(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $message = Message::findOrFail($id);
        $senderId = $message->sender_id;
        $contentPreview = mb_substr($message->content, 0, 50);

        $message->delete();

        AdminAction::log('delete_message', 'message', $id, [
            'sender_id' => $senderId,
            'preview' => $contentPreview,
        ], $adminId);

        return response()->json(['message' => 'Message supprimé avec succès.']);
    }

    /**
     * Obtenir le dossier utilisateur complet (sanctions, signalements, activité, notes).
     */
    public function getUserSanctions(int $id)
    {
        $user = User::with([
            'sanctions' => fn($q) => $q->with('admin:id,username')->orderByDesc('created_at'),
            'reportsReceived' => fn($q) => $q->with('reporter:id,name')->orderByDesc('created_at'),
            'reportsSent' => fn($q) => $q->with('reportedUser:id,name')->orderByDesc('created_at'),
        ])->findOrFail($id);

        // Lieux ajoutés par cet utilisateur
        $places = Place::where('added_by', $user->name)
            ->orWhere('added_by', $user->email)
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'name', 'category', 'status', 'created_at']);

        // Messages récents envoyés par cet utilisateur
        $messages = Message::where('sender_id', $user->id)
            ->orderByDesc('created_at')
            ->take(10)
            ->get(['id', 'sender_id', 'receiver_id', 'conversation_id', 'created_at']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role ? (is_object($user->role) ? $user->role->value : $user->role) : 'user',
                'faculty' => $user->faculty,
                'study_level' => $user->study_level,
                'student_id' => $user->student_id,
                'study_status' => $user->study_status,
                'is_restricted' => $user->is_restricted,
                'is_banned' => $user->is_banned,
                'muted_until' => $user->muted_until,
                'suspended_until' => $user->suspended_until,
                'admin_note' => $user->admin_note,
                'created_at' => $user->created_at,
            ],
            'stats' => [
                'places_count' => Place::where('added_by', $user->name)->orWhere('added_by', $user->email)->count(),
                'messages_count' => Message::where('sender_id', $user->id)->count(),
                'reports_received_count' => $user->reportsReceived->count(),
                'reports_sent_count' => $user->reportsSent->count(),
                'sanctions_count' => $user->sanctions->count(),
            ],
            'places' => $places,
            'recent_messages' => $messages,
            'sanctions' => $user->sanctions,
            'reports_received' => $user->reportsReceived,
            'reports_sent' => $user->reportsSent,
        ]);
    }

    /**
     * Modifier le rôle d'un utilisateur (user / moderator / admin / super_admin).
     */
    public function updateUserRole(Request $request, int $id)
    {
        $request->validate([
            'role' => 'required|string|in:user,moderator,admin,super_admin',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);
        $oldRole = $user->role ? (is_object($user->role) ? $user->role->value : $user->role) : 'user';
        $user->role = $request->role;
        $user->save();

        AdminAction::log('update_user_role', 'user', $user->id, [
            'name' => $user->name,
            'old_role' => $oldRole,
            'new_role' => $request->role,
        ], $adminId);

        return response()->json([
            'message' => "Le rôle de {$user->name} a été mis à jour en '{$request->role}'.",
            'role' => $request->role,
        ]);
    }

    /**
     * Forcer la déconnexion d'un utilisateur (invalider tous les tokens).
     */
    public function forceLogoutUser(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);
        $user->tokens()->delete();

        AdminAction::log('force_logout_user', 'user', $user->id, ['name' => $user->name], $adminId);

        return response()->json([
            'message' => "Toutes les sessions de {$user->name} ont été révoquées.",
        ]);
    }

    /**
     * Réinitialiser le mot de passe d'un utilisateur par l'administrateur.
     */
    public function resetUserPassword(Request $request, int $id)
    {
        $request->validate([
            'new_password' => 'nullable|string|min:6',
        ]);

        $adminId = $this->getAdminId($request);
        $user = User::findOrFail($id);

        $tempPassword = $request->filled('new_password') ? $request->new_password : Str::random(10);
        $user->password = Hash::make($tempPassword);
        $user->save();
        $user->tokens()->delete();

        AdminAction::log('reset_user_password', 'user', $user->id, ['name' => $user->name], $adminId);

        return response()->json([
            'message' => "Mot de passe réinitialisé pour {$user->name}.",
            'temp_password' => $tempPassword,
        ]);
    }

    /**
     * Mettre à jour la note interne sur un utilisateur.
     */
    public function updateUserNote(Request $request, int $id)
    {
        $request->validate(['admin_note' => 'nullable|string|max:5000']);
        $adminId = $this->getAdminId($request);

        $user = User::findOrFail($id);
        $user->admin_note = $request->admin_note;
        $user->save();

        AdminAction::log('update_user_note', 'user', $user->id, ['note_preview' => mb_substr((string)$request->admin_note, 0, 100)], $adminId);

        return response()->json([
            'message' => 'Note interne enregistrée.',
            'admin_note' => $user->admin_note,
        ]);
    }

    /**
     * Journal d'audit des actions administrateur.
     */
    public function auditLogs(Request $request)
    {
        $logs = AdminAction::with('admin:id,username')
            ->orderByDesc('created_at')
            ->paginate(min($request->input('per_page', 50), 100));

        return response()->json($logs);
    }

    /**
     * =========================================================================
     * LIEUX & CARTOGRAPHIE (PHASE 2)
     * =========================================================================
     */

    /**
     * Liste tous les lieux (avec recherche, filtre de statut et pagination/tri).
     */
    public function places(Request $request)
    {
        $query = Place::query();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $s = '%' . strtolower($request->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->whereRaw('LOWER(name) LIKE ?', [$s])
                  ->orWhereRaw('LOWER(description) LIKE ?', [$s])
                  ->orWhereRaw('LOWER(category) LIKE ?', [$s]);
            });
        }

        $places = $query->orderByDesc('created_at')->get();
        return response()->json($places);
    }

    /**
     * Éditer un lieu existant (nom, catégorie, description, photo, coordonnées, visibilité).
     */
    public function updatePlace(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'type' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'image_url' => 'nullable|string|url|max:1000',
            'status' => 'required|in:approved,pending,hidden',
            'opening_hours' => 'nullable|string|max:255',
        ]);

        $adminId = $this->getAdminId($request);
        $place = Place::findOrFail($id);

        $images = $place->images ?? [];
        if ($request->filled('image_url')) {
            $newImg = $request->image_url;
            if (!in_array($newImg, $images)) {
                array_unshift($images, $newImg);
            }
        }

        $place->update([
            'name' => $request->name,
            'category' => $request->category,
            'type' => $request->type ?? $place->type ?? 'amenity',
            'description' => $request->description,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'image_url' => $request->image_url ?? $place->image_url,
            'images' => $images,
            'status' => $request->status,
            'opening_hours' => $request->opening_hours,
        ]);

        AdminAction::log('update_place', 'place', $place->id, [
            'name' => $place->name,
            'category' => $place->category,
            'status' => $place->status,
        ], $adminId);

        return response()->json([
            'message' => 'Lieu mis à jour avec succès.',
            'place' => $place,
        ]);
    }

    /**
     * Changer rapidement la visibilité d'un lieu (approved, hidden, pending).
     */
    public function updatePlaceVisibility(Request $request, int $id)
    {
        $request->validate(['status' => 'required|in:approved,pending,hidden']);
        $adminId = $this->getAdminId($request);

        $place = Place::findOrFail($id);
        $place->status = $request->status;
        $place->save();

        AdminAction::log('update_place_visibility', 'place', $place->id, [
            'name' => $place->name,
            'status' => $place->status,
        ], $adminId);

        return response()->json([
            'message' => 'Statut de visibilité mis à jour.',
            'status' => $place->status,
        ]);
    }

    /**
     * Détecter les doublons potentiels (proximité géographique < 80m ou similarité de nom).
     */
    public function getDuplicatePlaces()
    {
        $places = Place::select(['id', 'name', 'category', 'latitude', 'longitude', 'status', 'description'])->get();
        $duplicates = [];
        $checkedPairs = [];

        $count = $places->count();
        for ($i = 0; $i < $count; $i++) {
            $p1 = $places[$i];
            if (!$p1->latitude || !$p1->longitude) continue;

            for ($j = $i + 1; $j < $count; $j++) {
                $p2 = $places[$j];
                if (!$p2->latitude || !$p2->longitude) continue;

                $pairKey = min($p1->id, $p2->id) . '-' . max($p1->id, $p2->id);
                if (isset($checkedPairs[$pairKey])) continue;

                // Calcul distance Haversine
                $distMeters = $this->haversineDistance($p1->latitude, $p1->longitude, $p2->latitude, $p2->longitude);

                // Similarité de nom
                similar_text(mb_strtolower($p1->name), mb_strtolower($p2->name), $simPercent);

                // Critères de doublon potentiel :
                // 1. Même position exacte (< 5 mètres)
                // 2. Proximité (< 60m) ET similarité de nom > 50%
                // 3. Similarité de nom > 85% ET distance < 200m
                $isDuplicate = false;
                $reason = '';

                if ($distMeters <= 5) {
                    $isDuplicate = true;
                    $reason = "Positions GPS quasi identiques ({$distMeters}m d'écart)";
                } elseif ($distMeters <= 60 && $simPercent >= 50) {
                    $isDuplicate = true;
                    $reason = "Très proches ({$distMeters}m) et noms similaires (" . round($simPercent) . "%)";
                } elseif ($distMeters <= 200 && $simPercent >= 80) {
                    $isDuplicate = true;
                    $reason = "Noms très proches (" . round($simPercent) . "%) et zone proche ({$distMeters}m)";
                }

                if ($isDuplicate) {
                    $checkedPairs[$pairKey] = true;
                    $duplicates[] = [
                        'place_a' => $p1,
                        'place_b' => $p2,
                        'distance_meters' => $distMeters,
                        'similarity_percent' => round($simPercent, 1),
                        'reason' => $reason,
                    ];
                }
            }
        }

        return response()->json([
            'total_duplicates' => count($duplicates),
            'duplicates' => $duplicates,
        ]);
    }

    /**
     * Régénérer la description d'un lieu via l'IA (Groq / Gemini).
     */
    public function regeneratePlaceDescription(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $place = Place::findOrFail($id);

        $newDescription = $this->generatePlaceDescriptionWithAi($place);

        if ($request->boolean('auto_save')) {
            $place->description = $newDescription;
            $place->save();

            AdminAction::log('regenerate_place_ai', 'place', $place->id, [
                'name' => $place->name,
                'description' => $newDescription,
            ], $adminId);
        }

        return response()->json([
            'message' => 'Description générée avec succès.',
            'description' => $newDescription,
            'place' => $place,
        ]);
    }

    /**
     * Helper pour générer la description via Groq/Gemini.
     */
    protected function generatePlaceDescriptionWithAi(Place $place): string
    {
        $groqKey = config('services.groq.key');
        $geminiKey = config('services.gemini.key');

        $prompt = "Rédige une description concise, claire et attractive (2 à 3 phrases en français) pour le lieu suivant sur le campus de l'Université d'Abomey-Calavi (UAC) au Bénin :\n"
            . "- Nom du lieu : {$place->name}\n"
            . "- Catégorie : {$place->category}\n"
            . "- Type : {$place->type}\n"
            . "- Objectif : Informer les étudiants sur l'utilité, la localisation académique et les activités de ce lieu.\n"
            . "Réponds STRICTEMENT avec la description rédigée, sans guillemets, sans titre et sans formule de politesse.";

        if (!empty($groqKey) && !str_contains($groqKey, 'your_groq_api_key')) {
            try {
                $res = Http::withToken($groqKey)->timeout(15)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es l\'assistant cartographe et rédacteur officiel du campus universitaire d\'Abomey-Calavi (UAC).'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.6,
                    'max_tokens' => 250
                ]);

                if ($res->successful()) {
                    $desc = trim($res->json('choices.0.message.content'));
                    if (!empty($desc)) return $desc;
                }
            } catch (\Throwable $e) {
                Log::warning('Groq description gen failed: ' . $e->getMessage());
            }
        }

        if (!empty($geminiKey) && !str_contains($geminiKey, 'your_gemini_api_key')) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}";
                $res = Http::timeout(15)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]]
                ]);
                if ($res->successful()) {
                    $desc = trim($res->json('candidates.0.content.parts.0.text'));
                    if (!empty($desc)) return $desc;
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini description gen failed: ' . $e->getMessage());
            }
        }

        return "{$place->name} est un bâtiment clé du campus de l'Université d'Abomey-Calavi dédié aux activités d'apprentissage, d'études et de services pour la communauté étudiante.";
    }

    /**
     * Distance Haversine entre 2 points géographiques en mètres.
     */
    protected function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }

    /**
     * Approuver un lieu en attente.
     */
    public function approvePlace(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $place = Place::findOrFail($id);
        $place->status = 'approved';
        $place->save();

        AdminAction::log('approve_place', 'place', $place->id, ['name' => $place->name], $adminId);

        return response()->json(['message' => 'Lieu approuvé avec succès.', 'place' => $place]);
    }

    /**
     * Rejeter / Supprimer un lieu.
     */
    public function deletePlace(Request $request, int $id)
    {
        $adminId = $this->getAdminId($request);
        $place = Place::findOrFail($id);
        $placeName = $place->name;
        $place->delete();

        AdminAction::log('delete_place', 'place', $id, ['name' => $placeName], $adminId);

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

    // ══════════════════════════════════════════════════════════════
    // PHASE 5: SUPERVISION TECHNIQUE, SANTÉ SYSTÈME & LOGS
    // ══════════════════════════════════════════════════════════════

    /**
     * Diagnostic de santé complet du système (PostgreSQL, Cache, IA, Serveur, Disque).
     */
    public function systemHealth(Request $request)
    {
        $adminId = $this->getAdminId($request);

        // 1. Base de données
        $dbStatus = 'ok';
        $dbLatency = null;
        $dbError = null;
        try {
            $start = microtime(true);
            DB::select('SELECT 1');
            $dbLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $dbStatus = 'error';
            $dbError = $e->getMessage();
        }

        // 2. Cache
        $cacheStatus = 'ok';
        $cacheLatency = null;
        $cacheError = null;
        try {
            $start = microtime(true);
            $probeKey = '_health_probe_' . Str::random(8);
            Cache::put($probeKey, 1, 10);
            Cache::get($probeKey);
            Cache::forget($probeKey);
            $cacheLatency = round((microtime(true) - $start) * 1000, 2);
        } catch (\Throwable $e) {
            $cacheStatus = 'error';
            $cacheError = $e->getMessage();
        }

        // 3. Passerelles IA (Groq & Gemini)
        $groqKey = config('services.groq.key') ?? env('GROQ_API_KEY');
        $geminiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        $groqConfigured = !empty($groqKey);
        $geminiConfigured = !empty($geminiKey);

        // 4. Système & Ressources
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $memLimit = ini_get('memory_limit');

        $basePath = base_path();
        $diskTotal = @disk_total_space($basePath) ?: 0;
        $diskFree = @disk_free_space($basePath) ?: 0;
        $diskUsed = max(0, $diskTotal - $diskFree);
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 1) : 0;

        // 5. Logs & Stockage
        $logPath = storage_path('logs/laravel.log');
        $logExists = File::exists($logPath);
        $logSizeBytes = $logExists ? File::size($logPath) : 0;

        // Statut global
        $isHealthy = ($dbStatus === 'ok' && $cacheStatus === 'ok');

        return response()->json([
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => [
                    'status' => $dbStatus,
                    'latency_ms' => $dbLatency,
                    'driver' => config('database.default'),
                    'name' => config('database.connections.' . config('database.default') . '.database'),
                    'error' => $dbError,
                ],
                'cache' => [
                    'status' => $cacheStatus,
                    'latency_ms' => $cacheLatency,
                    'driver' => config('cache.default'),
                    'error' => $cacheError,
                ],
                'ai_groq' => [
                    'configured' => $groqConfigured,
                    'model' => 'llama-3.3-70b-versatile',
                    'status' => $groqConfigured ? 'ready' : 'missing_key',
                ],
                'ai_gemini' => [
                    'configured' => $geminiConfigured,
                    'model' => 'gemini-1.5-flash',
                    'status' => $geminiConfigured ? 'ready' : 'missing_key',
                ],
            ],
            'server' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'environment' => app()->environment(),
                'debug_mode' => (bool) config('app.debug'),
                'os' => PHP_OS_FAMILY . ' (' . php_uname('s') . ')',
                'server_time' => now()->format('Y-m-d H:i:s T'),
                'uptime_estimate' => 'Active',
            ],
            'resources' => [
                'memory_usage_bytes' => $memUsage,
                'memory_usage_formatted' => $this->formatBytes($memUsage),
                'memory_peak_formatted' => $this->formatBytes($memPeak),
                'memory_limit' => $memLimit,
                'disk_total_formatted' => $this->formatBytes($diskTotal),
                'disk_free_formatted' => $this->formatBytes($diskFree),
                'disk_used_formatted' => $this->formatBytes($diskUsed),
                'disk_usage_percent' => $diskPercent,
                'log_file_size_formatted' => $this->formatBytes($logSizeBytes),
                'log_file_size_bytes' => $logSizeBytes,
            ],
            'metrics' => [
                'total_users' => User::count(),
                'total_places' => Place::count(),
                'total_messages' => Message::count(),
                'total_reports' => Report::count(),
            ],
        ]);
    }

    /**
     * Récupération et filtrage structuré des logs système Laravel.
     */
    public function systemLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        if (!File::exists($logPath)) {
            return response()->json([
                'logs' => [],
                'total' => 0,
                'file_size' => '0 B',
                'message' => 'Aucun fichier journal trouvé.',
            ]);
        }

        $fileSize = File::size($logPath);
        $maxBytes = 3 * 1024 * 1024; // Lire max 3MB de fin de fichier

        if ($fileSize > $maxBytes) {
            $handle = fopen($logPath, 'r');
            fseek($handle, -$maxBytes, SEEK_END);
            $content = fread($handle, $maxBytes);
            fclose($handle);
        } else {
            $content = File::get($logPath);
        }

        // Pattern pour matcher les blocs de logs Laravel standard
        $pattern = '/^\[(?P<date>\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?)\] (?P<env>\w+)\.(?P<level>[A-Z]+): (?P<message>.*?)(?=(?:^\[\d{4}-\d{2}-\d{2})|\z)/ms';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $levelFilter = strtolower($request->query('level', ''));
        $searchFilter = strtolower($request->query('search', ''));

        $logs = [];
        $id = 1;
        foreach (array_reverse($matches) as $match) {
            $lvl = strtolower($match['level']);
            $msg = trim($match['message']);
            $date = $match['date'];
            $env = $match['env'];

            // Filtrage par niveau
            if ($levelFilter && $levelFilter !== 'all' && $lvl !== $levelFilter) {
                continue;
            }

            // Filtrage par recherche
            if ($searchFilter && !str_contains(strtolower($msg), $searchFilter) && !str_contains(strtolower($lvl), $searchFilter)) {
                continue;
            }

            // Découpage message vs stack trace si présent
            $stackTrace = null;
            $firstLine = $msg;
            if (str_contains($msg, "\n")) {
                $parts = explode("\n", $msg, 2);
                $firstLine = trim($parts[0]);
                $stackTrace = trim($parts[1]);
            }

            $logs[] = [
                'id' => $id++,
                'timestamp' => $date,
                'environment' => $env,
                'level' => $lvl,
                'message' => $firstLine,
                'full_message' => $msg,
                'stack_trace' => $stackTrace,
            ];

            if (count($logs) >= 100) {
                break;
            }
        }

        return response()->json([
            'logs' => $logs,
            'total_parsed' => count($logs),
            'total_raw_entries' => count($matches),
            'file_size' => $this->formatBytes($fileSize),
            'file_path' => 'storage/logs/laravel.log',
        ]);
    }

    /**
     * Vider le fichier journal système.
     */
    public function clearSystemLogs(Request $request)
    {
        $adminId = $this->getAdminId($request);
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, '');
        }

        AdminAction::log('clear_logs', 'system', 0, [
            'action' => 'Fichier journal système vidé',
            'timestamp' => now()->toIso8601String(),
        ], $adminId);

        return response()->json([
            'message' => 'Le fichier journal a été vidé avec succès.',
        ]);
    }

    /**
     * Vider les caches applicatifs (cache, config, route, view).
     */
    public function clearSystemCache(Request $request)
    {
        $adminId = $this->getAdminId($request);

        try {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            AdminAction::log('clear_cache', 'system', 0, [
                'commands' => ['cache:clear', 'config:clear', 'route:clear', 'view:clear'],
            ], $adminId);

            return response()->json([
                'message' => 'Tous les caches applicatifs ont été réinitialisés avec succès.',
                'cleared' => ['Application Cache', 'Configuration Cache', 'Route Cache', 'Compiled Views'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erreur lors du nettoyage du cache : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Optimiser l'application (artisan optimize).
     */
    public function optimizeSystem(Request $request)
    {
        $adminId = $this->getAdminId($request);

        try {
            Artisan::call('optimize');

            AdminAction::log('optimize_system', 'system', 0, [
                'output' => Artisan::output(),
            ], $adminId);

            return response()->json([
                'message' => 'Optimisation du système effectuée avec succès.',
                'output' => trim(Artisan::output()),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'optimisation : ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════
    // PHASE 6: CONTENU IA & SUGGESTIONS DE CAMPUS
    // ══════════════════════════════════════════════════════════════

    /**
     * Audit automatisé des lieux du campus par IA (qualité des métadonnées, doublons potentiels, descriptions manquantes).
     */
    public function auditCampusPlaces(Request $request)
    {
        $places = Place::all();
        $issues = [];
        $missingDescCount = 0;
        $missingCategoryCount = 0;
        $missingCoordsCount = 0;
        $shortDescCount = 0;

        foreach ($places as $p) {
            $hasIssues = false;
            $placeIssues = [];

            // 1. Description manquante ou trop courte
            if (empty(trim($p->description ?? ''))) {
                $missingDescCount++;
                $placeIssues[] = [
                    'type' => 'missing_description',
                    'severity' => 'warning',
                    'message' => 'Description manquante',
                    'actionable' => true,
                ];
                $hasIssues = true;
            } elseif (mb_strlen(trim($p->description)) < 25) {
                $shortDescCount++;
                $placeIssues[] = [
                    'type' => 'short_description',
                    'severity' => 'info',
                    'message' => 'Description très courte (< 25 caractères)',
                    'actionable' => true,
                ];
                $hasIssues = true;
            }

            // 2. Catégorie manquante
            if (empty(trim($p->category ?? ''))) {
                $missingCategoryCount++;
                $placeIssues[] = [
                    'type' => 'missing_category',
                    'severity' => 'critical',
                    'message' => 'Catégorie non assignée',
                    'actionable' => false,
                ];
                $hasIssues = true;
            }

            // 3. Coordonnées hors campus UAC (Latitude UAC ~ 6.40 - 6.46, Longitude ~ 2.30 - 2.38)
            $lat = (float) $p->latitude;
            $lng = (float) $p->longitude;
            if ($lat == 0 || $lng == 0) {
                $missingCoordsCount++;
                $placeIssues[] = [
                    'type' => 'missing_coordinates',
                    'severity' => 'critical',
                    'message' => 'Coordonnées GPS nulles ou invalides',
                    'actionable' => false,
                ];
                $hasIssues = true;
            } elseif ($lat < 6.30 || $lat > 6.60 || $lng < 2.20 || $lng > 2.50) {
                $placeIssues[] = [
                    'type' => 'out_of_bounds_coords',
                    'severity' => 'warning',
                    'message' => "Coordonnées hors zone principale du campus ($lat, $lng)",
                    'actionable' => false,
                ];
                $hasIssues = true;
            }

            if ($hasIssues) {
                $issues[] = [
                    'place_id' => $p->id,
                    'place_name' => $p->name,
                    'category' => $p->category ?? 'Non classé',
                    'status' => $p->status,
                    'issues' => $placeIssues,
                ];
            }
        }

        // Score global de qualité sur 100
        $totalPlaces = max(1, $places->count());
        $qualityScore = max(0, min(100, round(100 - ((count($issues) / $totalPlaces) * 60) - ($missingDescCount * 1.5))));

        return response()->json([
            'quality_score' => $qualityScore,
            'total_places' => $totalPlaces,
            'issues_count' => count($issues),
            'summary' => [
                'missing_descriptions' => $missingDescCount,
                'short_descriptions' => $shortDescCount,
                'missing_categories' => $missingCategoryCount,
                'missing_coordinates' => $missingCoordsCount,
            ],
            'issues' => $issues,
        ]);
    }

    /**
     * Générateur IA de synthèse d'actualité / bulletin hebdomadaire du campus UAC.
     */
    public function generateCampusDigest(Request $request)
    {
        $adminId = $this->getAdminId($request);

        $totalUsers = User::count();
        $totalPlaces = Place::count();
        $recentReports = Report::where('created_at', '>=', now()->subDays(7))->count();
        $activePlaces = Place::orderByDesc('created_at')->limit(5)->get(['id', 'name', 'category', 'description']);

        $placesSummary = $activePlaces->map(fn($p) => "• {$p->name} ({$p->category})")->implode("\n");

        // Essai de génération via Groq ou Gemini
        $groqKey = config('services.groq.key') ?? env('GROQ_API_KEY');
        $geminiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');

        $digest = null;
        $source = 'template';

        if (!empty($groqKey)) {
            try {
                $prompt = "Tu es l'assistant éditorial en chef de l'application cartographique U-map de l'Université d'Abomey-Calavi (UAC). Rédige une brève synthèse dynamique et captivante (en français, style professionnel et bienveillant, formaté en Markdown avec emojis) sur l'état du campus et les recommandations de la semaine pour les étudiants et modérateurs.\nDonnées actuelles : $totalUsers étudiants inscrits, $totalPlaces lieux répertoriés, $recentReports signalements traités cette semaine.\nDerniers lieux remarquables :\n$placesSummary";
                
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$groqKey}",
                    'Content-Type' => 'application/json',
                ])->timeout(12)->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un rédacteur et assistant IA d\'élite pour une application universitaire en Afrique de l\'Ouest (Bénin, UAC).'],
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'max_tokens' => 600,
                    'temperature' => 0.7,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $digest = $json['choices'][0]['message']['content'] ?? null;
                    $source = 'Groq (Llama 3.3 70B)';
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        if (!$digest && !empty($geminiKey)) {
            try {
                $prompt = "Rédige une synthèse dynamique pour le bulletin campus U-map (Université d'Abomey-Calavi) : $totalUsers utilisateurs, $totalPlaces lieux.";
                $response = Http::timeout(10)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiKey}", [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ],
                ]);
                if ($response->successful()) {
                    $json = $response->json();
                    $digest = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    $source = 'Gemini 1.5 Flash';
                }
            } catch (\Throwable $e) {
                // Fallback
            }
        }

        if (!$digest) {
            $digest = "### 📍 Bulletin Hebdomadaire U-map Campus UAC\n\n" .
                      "**Bienvenue dans le récapitulatif de la semaine !**\n\n" .
                      "• **Communauté active :** {$totalUsers} étudiants connectés sur la plateforme.\n" .
                      "• **Patrimoine cartographique :** {$totalPlaces} bâtiments, amphis, bibliothèques et espaces de détente répertoriés avec précision.\n" .
                      "• **Modération & Sérénité :** {$recentReports} signalements analysés et résolus par l'équipe administrative.\n\n" .
                      "💡 **Conseil d'optimisation :** Pensez à enrichir les descriptions des bâtiments nouvellement ajoutés pour faciliter l'orientation des nouveaux arrivants à l'UAC.";
            $source = 'Générateur interne U-map';
        }

        AdminAction::log('generate_digest', 'ai', 0, [
            'source' => $source,
            'generated_at' => now()->toIso8601String(),
        ], $adminId);

        return response()->json([
            'digest' => $digest,
            'source' => $source,
            'generated_at' => now()->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Utilitaire de formatage de taille d'octets.
     */
    protected function formatBytes(int|float $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
