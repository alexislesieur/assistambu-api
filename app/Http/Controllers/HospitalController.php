<?php

namespace App\Http\Controllers;

use App\Models\Hospital;
use Illuminate\Http\Request;

class HospitalController extends Controller
{
    // Lister tous les hôpitaux (tous les utilisateurs)
    public function index(Request $request)
    {
        $hospitals = Hospital::orderBy('ville')->orderBy('nom')->get();

        return response()->json($hospitals);
    }

    // Afficher un hôpital
    public function show(Hospital $hospital)
    {
        return response()->json($hospital);
    }

    // Créer un hôpital (admin only)
    public function store(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom'         => 'required|string|max:255',
            'adresse'     => 'required|string|max:255',
            'code_postal' => 'required|string|max:10',
            'ville'       => 'required|string|max:255',
            'latitude'    => 'required|numeric',
            'longitude'   => 'required|numeric',
            'type'        => 'required|in:adult,pediatric,maternity,psychiatry',
        ]);

        $hospital = Hospital::create([
            ...$validated,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($hospital, 201);
    }

    // Modifier un hôpital (admin only)
    public function update(Request $request, Hospital $hospital)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $validated = $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'adresse'     => 'sometimes|string|max:255',
            'code_postal' => 'sometimes|string|max:10',
            'ville'       => 'sometimes|string|max:255',
            'latitude'    => 'sometimes|numeric',
            'longitude'   => 'sometimes|numeric',
            'type'        => 'sometimes|in:adult,pediatric,maternity,psychiatry',
        ]);

        $hospital->update([
            ...$validated,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json($hospital);
    }

    // Supprimer un hôpital (admin only)
    public function destroy(Request $request, Hospital $hospital)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $hospital->delete();

        return response()->json(['message' => 'Hôpital supprimé.']);
    }
}