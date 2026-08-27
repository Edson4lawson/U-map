<?php

namespace App\Http\Controllers;

use App\Models\UserDevice;
use Illuminate\Http\Request;

class UserDeviceController extends Controller
{
    public function index(Request $request)
    {
        $devices = $request->user()->devices()->orderBy('last_active_at', 'desc')->get();
        return response()->json($devices);
    }

    public function destroy(Request $request, $id)
    {
        $device = $request->user()->devices()->find($id);

        if (!$device) {
            return response()->json(['message' => 'Appareil non trouvé.'], 404);
        }

        $device->delete();

        // Optionally, if we want to logout other sessions, we would delete the personal access tokens.
        // For simplicity and safety, we revoke the device entry. In a real-world scenario, we could map tokens.

        return response()->json(['message' => 'Session de l\'appareil révoquée avec succès.']);
    }
}
