<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    // Lister les gardes de l'utilisateur connecté
    public function index(Request $request)
    {
        $shifts = Shift::where('user_id', $request->user()->id)
            ->orderBy('started_at', 'desc')
            ->get();

        return response()->json($shifts);
    }

    // Créer une nouvelle garde
    public function store(Request $request)
    {
        $validated = $request->validate([
            'started_at' => 'required|date',
            'driver'     => 'required|boolean',
        ]);

        // Vérifier qu'aucune garde n'est en cours
        $ongoingShift = Shift::where('user_id', $request->user()->id)
            ->whereNull('ended_at')
            ->first();

        if ($ongoingShift) {
            return response()->json([
                'message' => 'Une garde est déjà en cours.',
            ], 422);
        }

        $shift = Shift::create([
            'user_id'    => $request->user()->id,
            'started_at' => $validated['started_at'],
            'driver'     => $validated['driver'],
        ]);

        return response()->json($shift, 201);
    }

    // Afficher une garde
    public function show(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($shift->load('interventions'));
    }

    // Terminer une garde
    public function end(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        if (!$shift->isOngoing()) {
            return response()->json(['message' => 'Cette garde est déjà terminée.'], 422);
        }

        $shift->update(['ended_at' => now()]);

        return response()->json($shift);
    }

    // Supprimer une garde
    public function destroy(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $shift->delete();

        return response()->json(['message' => 'Garde supprimée.']);
    }
}