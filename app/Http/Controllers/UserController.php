<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    // Afficher le profil
    public function show(Request $request)
    {
        return response()->json($request->user());
    }

    // Modifier le profil
    public function update(Request $request)
    {
        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
        ]);

        // Si l'email change, on réinitialise la vérification
        if (isset($validated['email']) && $validated['email'] !== $request->user()->email) {
            $validated['email_verified_at'] = null;
        }

        $request->user()->update($validated);

        // Renvoyer l'email de vérification si l'email a changé
        if (isset($validated['email_verified_at'])) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json($request->user()->fresh());
    }

    // Modifier le mot de passe
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($request->current_password, $request->user()->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.',
            ], 422);
        }

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès.',
        ]);
    }
}