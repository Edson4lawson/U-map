<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\MessageEncryptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class MessageController extends Controller
{
    /**
     * Récupère les messages d'une conversation avec pagination (Éphémère : 7 jours max).
     * Optimisé avec eager loading et index.
     */
    public function getMessages(Request $request, int $receiverId)
    {
        $userId = Auth::id();

        if (!User::where('id', $receiverId)->exists()) {
            return response()->json(['error' => 'Utilisateur introuvable'], 404);
        }

        // Vérifier l'autorisation d'accès à la conversation via la Policy Message
        if (Gate::denies('viewConversation', [Message::class, (int) $receiverId])) {
            return response()->json(['error' => 'Impossible de consulter cette conversation.'], 403);
        }

        // S'assurer que le fil de conversation existe pour garder l'historique de la discussion
        Conversation::findOrCreateBetween($userId, $receiverId);

        $sevenDaysAgo = Carbon::now()->subDays(7);
        $perPage = min($request->input('per_page', 50), 100);
        $page = $request->input('page', 1);

        $messages = Message::where(function ($query) use ($userId, $receiverId) {
            $query->where(function ($q) use ($userId, $receiverId) {
                $q->where('sender_id', $userId)->where('receiver_id', $receiverId);
            })->orWhere(function ($q) use ($userId, $receiverId) {
                $q->where('sender_id', $receiverId)->where('receiver_id', $userId);
            });
        })
            ->where('created_at', '>=', $sevenDaysAgo)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);

        // Marquer comme lu en arrière-plan (non bloquant)
        if ($messages->isNotEmpty()) {
            Message::where('sender_id', $receiverId)
                ->where('receiver_id', $userId)
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget("user:{$userId}:unread_count");
        }

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
        ]);
    }

    /**
     * Envoie un nouveau message et broadcast via WebSocket.
     */
    public function sendMessage(SendMessageRequest $request)
    {
        $senderId = Auth::id();
        $receiverId = $request->receiver_id;

        $message = new Message();
        $message->sender_id   = $senderId;
        $message->receiver_id = $receiverId;
        $message->content     = $request->content; // déclenche setContentAttribute → chiffrement
        $message->save();

        // Mettre à jour / Créer le fil de conversation avec le timestamp du dernier message
        $conversation = Conversation::findOrCreateBetween($senderId, $receiverId);
        $conversation->last_message_at = Carbon::now();
        $conversation->save();

        // Charger les relations pour le broadcast
        $message->load(['sender:id,name', 'receiver:id,name']);

        // Broadcast l'événement via WebSocket
        broadcast(new MessageSent($message));

        Cache::forget("user:{$receiverId}:unread_count");

        return response()->json([
            'message' => $message,
        ], 201);
    }

    /**
     * Liste les utilisateurs avec qui on a discuté récemment.
     * Conserve le fil de discussion même si les messages individuels ont expiré après 7 jours.
     */
    public function getConversations(Request $request)
    {
        $userId = Auth::id();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $perPage = min($request->input('per_page', 20), 50);
        $page = $request->input('page', 1);

        // Rétro-compatibilité : synchroniser les messages existants s'ils n'ont pas encore de ligne conversation
        $existingMessagePartners = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->select(DB::raw('CASE WHEN sender_id = '.$userId.' THEN receiver_id ELSE sender_id END as partner_id'), DB::raw('MAX(created_at) as max_created'))
            ->groupBy('partner_id')
            ->get();

        foreach ($existingMessagePartners as $partner) {
            $conv = Conversation::findOrCreateBetween($userId, (int) $partner->partner_id);
            $maxCreated = Carbon::parse($partner->max_created);
            if (!$conv->last_message_at || $maxCreated->gt($conv->last_message_at)) {
                $conv->last_message_at = $maxCreated;
                $conv->save();
            }
        }

        // Récupérer toutes les conversations du fil de l'utilisateur
        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->get();

        if ($conversations->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 1,
                ],
            ]);
        }

        // Extraire la liste des IDs des autres utilisateurs du fil
        $otherUserIds = $conversations->map(fn ($c) => $c->getOtherUserId($userId))->unique()->values();

        // Récupérer les informations utilisateur
        $users = User::whereIn('id', $otherUserIds)
            ->select(['id', 'name', 'email', 'study_status', 'study_location'])
            ->get()
            ->keyBy('id');

        // Récupérer le dernier message actif (dans les 7 jours) pour chaque interlocuteur
        $latestMessagesRaw = Message::where('created_at', '>=', $sevenDaysAgo)
            ->where(function ($q) use ($userId, $otherUserIds) {
                $q->where(function ($q2) use ($userId, $otherUserIds) {
                    $q2->where('sender_id', $userId)->whereIn('receiver_id', $otherUserIds);
                })->orWhere(function ($q2) use ($userId, $otherUserIds) {
                    $q2->whereIn('sender_id', $otherUserIds)->where('receiver_id', $userId);
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $latestMessages = $latestMessagesRaw->groupBy(function ($msg) use ($userId) {
            return $msg->sender_id === $userId ? $msg->receiver_id : $msg->sender_id;
        })->map(function ($group) {
            return $group->first();
        });

        // Nombre de messages non lus par interlocuteur
        $unreadCounts = DB::table('messages')
            ->select('sender_id', DB::raw('COUNT(*) as count'))
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->whereIn('sender_id', $otherUserIds)
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id');

        $encryptionService = new MessageEncryptionService();

        $result = collect();
        foreach ($conversations as $conv) {
            $otherId = $conv->getOtherUserId($userId);
            $user = $users->get($otherId);

            if (!$user) {
                continue;
            }

            $message = $latestMessages->get($otherId);
            $decryptedContent = null;

            if ($message) {
                if ($message->is_encrypted && $message->encrypted_content) {
                    try {
                        $decryptedContent = $encryptionService->decrypt($message->encrypted_content);
                    } catch (\Exception $e) {
                        $decryptedContent = '[Erreur déchiffrement]';
                    }
                }
            }

            $userCopy = clone $user;
            $userCopy->conversation_id = $conv->id;
            $userCopy->last_message_at = $conv->last_message_at ? $conv->last_message_at->toIso8601String() : null;
            $userCopy->last_message = $message ? [
                'id' => $message->id,
                'content' => $decryptedContent,
                'created_at' => $message->created_at ? $message->created_at->toIso8601String() : null,
                'sender_id' => $message->sender_id,
                'is_read' => (bool) $message->is_read,
            ] : null;

            $userCopy->unread_count = (int) $unreadCounts->get($otherId, 0);

            $result->push($userCopy);
        }

        // Pagination manuelle
        $total = $result->count();
        $offset = ($page - 1) * $perPage;
        $paginatedUsers = $result->slice($offset, $perPage)->values();

        return response()->json([
            'data' => $paginatedUsers,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    /**
     * Retourne le nombre de messages non lus avec cache.
     */
    public function getUnreadCount()
    {
        $userId = Auth::id();

        $count = Cache::remember("user:{$userId}:unread_count", 60, function () use ($userId) {
            return Message::where('receiver_id', $userId)
                ->where('is_read', false)
                ->where('created_at', '>=', Carbon::now()->subDays(7))
                ->count();
        });

        return response()->json(['count' => $count]);
    }
}

