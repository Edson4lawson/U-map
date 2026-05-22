<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MessageController extends Controller
{
    /**
     * Récupère les messages d'une conversation (Éphémère : 7 jours max).
     */
    public function getMessages($receiverId)
    {
        $userId = auth()->id();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        $messages = Message::where(function($query) use ($userId, $receiverId) {
            $query->where('sender_id', $userId)->where('receiver_id', $receiverId);
        })->orWhere(function($query) use ($userId, $receiverId) {
            $query->where('sender_id', $receiverId)->where('receiver_id', $userId);
        })
        ->where('created_at', '>=', $sevenDaysAgo) // Filtre éphémère
        ->orderBy('created_at', 'asc')
        ->get();

        // Marquer comme lu les messages reçus de cet utilisateur
        Message::where('sender_id', $receiverId)
               ->where('receiver_id', $userId)
               ->where('is_read', false)
               ->update(['is_read' => true]);

        return response()->json($messages);
    }

    /**
     * Envoie un nouveau message.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000'
        ]);

        $message = Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $request->receiver_id,
            'content' => $request->content
        ]);

        return response()->json($message);
    }

    /**
     * Liste les utilisateurs avec qui on a discuté récemment.
     */
    public function getConversations()
    {
        $userId = auth()->id();
        $sevenDaysAgo = Carbon::now()->subDays(7);

        // Récupère les IDs des gens avec qui on a échangé
        $userIds = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->get()
            ->map(function($msg) use ($userId) {
                return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
            })
            ->unique();

        $users = \App\Models\User::whereIn('id', $userIds)->get();

        return response()->json($users);
    }

    /**
     * Retourne le nombre de messages non lus pour l'utilisateur actuel.
     */
    public function getUnreadCount()
    {
        $count = Message::where('receiver_id', auth()->id())
            ->where('is_read', false)
            ->count();
        return response()->json(['count' => $count]);
    }

    /**
     * Nettoyage manuel (optionnel, peut être mis en tâche planifiée).
     */
    public function cleanup()
    {
        $count = Message::where('created_at', '<', Carbon::now()->subDays(7))->delete();
        return response()->json(['deleted' => $count]);
    }
}
