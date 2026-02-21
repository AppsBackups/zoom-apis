<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Alert;

class AlertController extends Controller
{
    // Fetch alerts for superadmin
    public function superAlert(Request $request)
    {
        $superDeviceId = $request->superdeviceid;
        $superId = $request->superid;

        // Add validation if needed
        if (!$superDeviceId || !$superId) {
            return response()->json(['error' => true, 'message' => 'Missing params']);
        }

        $alerts = Alert::all();

        return response()->json([
            'empty' => $alerts->isEmpty(),
            'error' => false,
            'content' => $alerts
        ]);
    }

    // Update alert (superadmin)
    public function superAlertUpdate(Request $request)
    {
        $validated = $request->validate([
            'massageid' => 'required|integer|exists:alerts,massageid',
            'massagetitle' => 'required|string',
            'description' => 'required|string',
        ]);

        $alert = Alert::find($validated['massageid']);
        $alert->massagetitle = $validated['massagetitle'];
        $alert->description = $validated['description'];
        $alert->save();

        return response()->json(['error' => false, 'message' => 'Alert Update successfully']);
    }

    // Fetch alert for client
    public function clientAlert(Request $request)
    {
        $userDeviceId = $request->userdeviceid;

        if (!$userDeviceId) {
            return response()->json(['error' => true, 'message' => 'Missing userdeviceid']);
        }

        $alerts = Alert::all();

        return response()->json([
            'empty' => $alerts->isEmpty(),
            'error' => false,
            'content' => $alerts
        ]);
    }
}
