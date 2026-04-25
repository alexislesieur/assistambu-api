<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    // Inscription publique depuis la vitrine
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:waitlist,email',
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
            'email.unique'   => 'Cette adresse email est déjà inscrite.',
        ]);

        Waitlist::create(['email' => $request->email]);

        return response()->json([
            'message' => 'Vous êtes inscrit sur la liste d\'attente !',
        ], 201);
    }

    // Liste complète — admin uniquement
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json(
            Waitlist::orderBy('created_at', 'desc')->get()
        );
    }

    // Ajouter un email manuellement — admin uniquement
    public function adminStore(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $request->validate([
            'email' => 'required|email|unique:waitlist,email',
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
            'email.unique'   => 'Cette adresse email est déjà inscrite.',
        ]);

        $entry = Waitlist::create(['email' => $request->email]);

        return response()->json($entry, 201);
    }

    // Supprimer un email — admin uniquement
    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $entry = Waitlist::findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Entrée supprimée.']);
    }
}