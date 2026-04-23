<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Models\Shift;
use App\Models\Item;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    // Lister les interventions de l'utilisateur connecté
    public function index(Request $request)
    {
        $interventions = Intervention::where('user_id', $request->user()->id)
            ->with(['shift', 'hospital'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($interventions);
    }

    // Lister les interventions d'une garde
    public function byShift(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $interventions = Intervention::where('shift_id', $shift->id)
            ->with(['hospital', 'items'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($interventions);
    }

    // Créer une intervention
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id'       => 'required|exists:shifts,id',
            'category'       => 'required|in:respi,cardio,trauma,neuro,pedia,general',
            'patient_gender' => 'required|in:male,female',
            'patient_age'    => 'required|integer|min:0|max:120',
            'gestures'       => 'nullable|array',
            'driving'        => 'required|in:outbound,return,round_trip,none',
            'no_transport'   => 'required|boolean',
            'hospital_id'    => 'nullable|exists:hospitals,id',
            'items'          => 'nullable|array',
            'items.*.id'     => 'required|exists:items,id',
            'items.*.quantity_used' => 'required|integer|min:1',
        ]);

        // Vérifier que la garde appartient à l'utilisateur
        $shift = Shift::find($validated['shift_id']);
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Vérifier que la garde est en cours
        if (!$shift->isOngoing()) {
            return response()->json(['message' => 'Cette garde est terminée.'], 422);
        }

        // Vérifier cohérence transport / hôpital
        if (!$validated['no_transport'] && empty($validated['hospital_id'])) {
            return response()->json(['message' => 'Un hôpital de destination est requis.'], 422);
        }

        // Créer l'intervention
        $intervention = Intervention::create([
            'shift_id'       => $validated['shift_id'],
            'user_id'        => $request->user()->id,
            'category'       => $validated['category'],
            'patient_gender' => $validated['patient_gender'],
            'patient_age'    => $validated['patient_age'],
            'gestures'       => $validated['gestures'] ?? [],
            'driving'        => $validated['driving'],
            'no_transport'   => $validated['no_transport'],
            'hospital_id'    => $validated['no_transport'] ? null : $validated['hospital_id'],
        ]);

        // Déduire le matériel du sac
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $item = Item::where('id', $itemData['id'])
                    ->where('user_id', $request->user()->id)
                    ->first();

                if ($item) {
                    $intervention->items()->attach($item->id, [
                        'quantity_used' => $itemData['quantity_used'],
                    ]);

                    $item->update([
                        'quantity' => max(0, $item->quantity - $itemData['quantity_used']),
                    ]);
                }
            }
        }

        return response()->json($intervention->load(['hospital', 'items']), 201);
    }

    // Afficher une intervention
    public function show(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($intervention->load(['shift', 'hospital', 'items']));
    }

    // Modifier une intervention
    public function update(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'category'       => 'sometimes|in:respi,cardio,trauma,neuro,pedia,general',
            'patient_gender' => 'sometimes|in:male,female',
            'patient_age'    => 'sometimes|integer|min:0|max:120',
            'gestures'       => 'nullable|array',
            'driving'        => 'sometimes|in:outbound,return,round_trip,none',
            'no_transport'   => 'sometimes|boolean',
            'hospital_id'    => 'nullable|exists:hospitals,id',
        ]);

        $intervention->update($validated);

        return response()->json($intervention->load(['hospital', 'items']));
    }

    // Supprimer une intervention
    public function destroy(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Remettre le stock
        foreach ($intervention->items as $item) {
            $item->update([
                'quantity' => $item->quantity + $item->pivot->quantity_used,
            ]);
        }

        $intervention->delete();

        return response()->json(['message' => 'Intervention supprimée.']);
    }
}