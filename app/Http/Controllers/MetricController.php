<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Metric;

class MetricController extends Controller
{
    // Get metrics for a superadmin
    public function getMetrics(Request $request)
    {
        $superid = $request->superid;

        if (!$superid) {
            return response()->json(['error' => true, 'message' => 'Missing superid']);
        }

        $metric = Metric::where('superid', $superid)->first();

        if (!$metric) {
            return response()->json(['error' => true, 'message' => 'No metrics found']);
        }

        return response()->json([
            'error' => false,
            'demo' => $metric->demo,
            'monthly' => $metric->monthly,
            'mbrand' => $metric->mbrand
        ]);
    }

    // Update metrics for a superadmin
    public function updateMetrics(Request $request)
    {
        $validated = $request->validate([
            'superid' => 'required|integer|exists:metrics,superid',
            'demo' => 'required|integer',
            'monthly' => 'required|integer',
        ]);

        $metric = Metric::where('superid', $validated['superid'])->first();
        $metric->demo = $validated['demo'];
        $metric->monthly = $validated['monthly'];
        $metric->save();

        return response()->json([
            'error' => false,
            'message' => 'Metrics Update successfully'
        ]);
    }
}
