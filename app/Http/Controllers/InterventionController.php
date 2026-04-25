<?php

namespace App\Http\Controllers;

use App\Models\Intervention;
use App\Models\InterventionItem;
use App\Models\Item;
use App\Models\Shift;
use App\Services\LogService;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function __construct(protected LogService $log) {}

    public function index(Request $request)
    {
        $interventions = Intervention::whereHas('shift', fn($q) => $q->where('user_id', $request->user()->id))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($interventions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shift_id'       => 'required|exists:shifts,id',
            'category'       => 'required|string',
            'patient_gender' => 'required|in:M,F',
            'patient_age'    => 'required|integer|min:0',
            'gestures'       => 'nullable|array',
            'driving'        => 'nullable|in:outbound,return,round_trip,none',
            'no_transport'   => 'nullable|boolean',
            'hospital_id'    => 'nullable|exists:hospitals,id',
            'items'          => 'nullable|array',
            'items.*.item_id'=> 'required|exists:items,id',
            'items.*.quantity_used' => 'required|integer|min:1',
        ]);

        $shift = Shift::findOrFail($validated['shift_id']);
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $intervention = Intervention::create([
            'shift_id'       => $validated['shift_id'],
            'user_id'        => $request->user()->id,
            'category'       => $validated['category'],
            'patient_gender' => $validated['patient_gender'],
            'patient_age'    => $validated['patient_age'],
            'gestures'       => $validated['gestures'] ?? [],
            'driving'        => $validated['driving'] ?? 'none',
            'no_transport'   => $validated['no_transport'] ?? false,
            'hospital_id'    => $validated['hospital_id'] ?? null,
        ]);

        // Déduction du stock
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $itemData) {
                $item = Item::find($itemData['item_id']);
                if ($item) {
                    InterventionItem::create([
                        'intervention_id' => $intervention->id,
                        'article_id'      => $item->id,
                        'quantity_used'   => $itemData['quantity_used'],
                    ]);
                    $item->decrement('quantity', $itemData['quantity_used']);
                }
            }
        }

        $this->log->interventionCreate($request->user()->id, $intervention->id, $intervention->category);

        return response()->json($intervention, 201);
    }

    public function show(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }
        return response()->json($intervention->load('items'));
    }

    public function update(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'category'       => 'sometimes|string',
            'patient_gender' => 'sometimes|in:M,F',
            'patient_age'    => 'sometimes|integer|min:0',
            'gestures'       => 'nullable|array',
            'driving'        => 'nullable|in:outbound,return,round_trip,none',
            'no_transport'   => 'nullable|boolean',
            'hospital_id'    => 'nullable|exists:hospitals,id',
        ]);

        $intervention->update($validated);
        return response()->json($intervention);
    }

    public function destroy(Request $request, Intervention $intervention)
    {
        if ($intervention->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        // Remise en stock
        foreach ($intervention->interventionItems as $interventionItem) {
            $item = Item::find($interventionItem->article_id);
            if ($item) {
                $item->increment('quantity', $interventionItem->quantity_used);
            }
        }

        $this->log->interventionDelete($request->user()->id, $intervention->id);

        $intervention->delete();
        return response()->json(['message' => 'Intervention supprimée.']);
    }

    public function byShift(Request $request, Shift $shift)
    {
        if ($shift->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($shift->interventions()->orderBy('created_at', 'desc')->get());
    }
}