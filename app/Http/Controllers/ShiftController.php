<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Services\LogService;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function __construct(protected LogService $log) {}

    public function index(Request $request)
    {
        $shifts = Shift::where('user_id', $request->user()->id)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json($shifts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'started_at' => 'required|date',
            'driver'     => 'required|boolean',
        ]);

        $activeShift = Shift::where('user_id', $request->user()->id)
            ->whereNull('ended_at')
            ->first();

        if ($activeShift) {
            return response()->json(['message' => 'Une garde est déjà en cours.'], 422);
        }

        $shift = Shift::create([
            'user_id'    => $request->user()->id,
            'started_at' => $validated['started_at'],
            'driver'     => $validated['driver'],
        ]);

        $this->log->shiftStart($request->user()->id, $shift->id, $shift->driver);

        return response()->json($shift, 201);
    }

    public function show(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        return response()->json($shift);
    }

    public function end(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'break_minutes' => 'required|integer|min:0',
        ]);

        $shift->update([
            'ended_at'      => now(),
            'break_minutes' => $validated['break_minutes'],
        ]);

        $amplitude = (int) $shift->started_at->diffInMinutes($shift->ended_at);
        $tte       = $amplitude - $validated['break_minutes'];

        $this->log->shiftEnd($request->user()->id, $shift->id, $validated['break_minutes'], $amplitude);

        return response()->json([
            'shift'     => $shift,
            'amplitude' => $amplitude,
            'tte'       => $tte,
        ]);
    }

    public function destroy(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        $shift->delete();
        return response()->json(['message' => 'Garde supprimée.']);
    }
}