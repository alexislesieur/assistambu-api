<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    // Renvoyer l'email de vérification
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de vérification envoyé.']);
    }

    // Vérifier l'email — accessible sans session, vérifie signature manuellement
    public function verify(Request $request, $id, $hash)
    {
        $frontUrl = env('FRONTEND_AUTH_URL', 'http://localhost:5175');

        // Vérifier la signature de l'URL
        if (!URL::hasValidSignature($request)) {
            return redirect($frontUrl . '/verify-email?error=invalid');
        }

        $user = User::findOrFail($id);

        // Vérifier le hash
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect($frontUrl . '/verify-email?error=invalid');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect($frontUrl . '/verify-email/success?already=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect($frontUrl . '/verify-email/success');
    }
}