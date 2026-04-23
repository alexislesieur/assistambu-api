<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function send(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email déjà vérifié.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Email de vérification envoyé.']);
    }

    public function verify(Request $request, $id, $hash)
    {
        $frontAuthUrl = env('FRONTEND_AUTH_URL', 'http://localhost:5173');

        // Vérifier la signature
        if (!URL::hasValidSignature($request)) {
            return redirect($frontAuthUrl . '/verify-email?error=invalid');
        }

        $user = User::findOrFail($id);

        // Vérifier le hash
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return redirect($frontAuthUrl . '/verify-email?error=invalid');
        }

        if (!$user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        // Redirect vers la page intermédiaire qui tente le deep link
        return redirect($frontAuthUrl . '/open-app?status=verified');
    }
}