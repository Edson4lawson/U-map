<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = min($request->input('per_page', 50), 100);
        $page = $request->input('page', 1);

        $students = User::where('id', '!=', auth()->id())
            ->select(['id', 'name', 'email', 'study_status', 'study_location'])
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'per_page' => $students->perPage(),
                'total' => $students->total(),
                'last_page' => $students->lastPage(),
            ],
        ]);
    }

    public function report(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        if (!User::where('id', $id)->exists()) {
            return response()->json(['message' => 'L\'utilisateur signalé n\'existe pas.'], 404);
        }

        Report::create([
            'reporter_id' => auth()->id(),
            'reported_user_id' => $id,
            'reason' => strip_tags($request->reason),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Signalement envoyé.']);
    }

    public function updateStudyStatus(Request $request)
    {
        $request->validate([
            'study_status' => 'nullable|string|max:255',
            'study_location' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $user->update([
            'study_status' => $request->study_status,
            'study_location' => $request->study_location,
        ]);

        return response()->json([
            'message' => 'Statut mis à jour.',
            'user' => $user,
        ]);
    }

    public function studyBuddies()
    {
        return User::whereNotNull('study_status')
            ->where('study_status', '!=', '')
            ->where('id', '!=', auth()->id())
            ->get(['id', 'name', 'email', 'study_status', 'study_location']);
    }
}
