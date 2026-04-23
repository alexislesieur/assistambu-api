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
            ->get()
            ->map(fn($shift) => $this->formatShift($shift));

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

        return response()->json($this->formatShift($shift), 201);
    }

    // Afficher une garde
    public function show(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($this->formatShift($shift->load('interventions')));
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

        $validated = $request->validate([
            'break_minutes' => 'nullable|integer|min:0',
        ]);

        $shift->update([
            'ended_at'      => now(),
            'break_minutes' => $validated['break_minutes'] ?? 0,
        ]);

        return response()->json($this->formatShift($shift));
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

    // Formater une garde avec les calculs
    private function formatShift(Shift $shift): array
    {
        return [
            ...$shift->toArray(),
            'amplitude'         => $shift->amplitudeFormatted(),
            'amplitude_minutes' => $shift->amplitudeMinutes(),
            'tte'               => $shift->tteFormatted(),
            'tte_minutes'       => $shift->tteMinutes(),
        ];
    }
}