<?php

namespace App\Http\Controllers;

use App\Models\Waitlist;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|unique:waitlist,email',
        ], [
            'email.required' => 'L\'adresse email est obligatoire.',
            'email.email'    => 'L\'adresse email n\'est pas valide.',
            'email.unique'   => 'Cette adresse email est déjà inscrite.',
        ]);

        Waitlist::create([
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'Vous êtes inscrit sur la liste d\'attente !',
        ], 201);
    }

    public function index(Request $request)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $waitlist = Waitlist::orderBy('created_at', 'desc')->get();

        return response()->json($waitlist);
    }
}
