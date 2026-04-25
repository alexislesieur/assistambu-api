<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Intervention;
use App\Models\Item;
use App\Models\Waitlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AdminController extends Controller
{
    private function checkAdmin(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'Accès refusé.');
        }
    }

    // Stats globales dashboard
    public function stats(Request $request)
    {
        $this->checkAdmin($request);

        return response()->json([
            'users'         => [
                'total'     => User::count(),
                'admins'    => User::where('role', 'admin')->count(),
                'verified'  => User::whereNotNull('email_verified_at')->count(),
                'new_week'  => User::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'shifts'        => [
                'total'     => Shift::count(),
                'active'    => Shift::whereNull('ended_at')->count(),
                'this_week' => Shift::where('created_at', '>=', now()->subDays(7))->count(),
            ],
            'interventions' => [
                'total'      => Intervention::count(),
                'this_week'  => Intervention::where('created_at', '>=', now()->subDays(7))->count(),
                'this_month' => Intervention::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'items'         => [
                'total'     => Item::count(),
                'low_stock' => Item::whereRaw('quantity < max_quantity * 0.3')->count(),
                'expired'   => Item::where('dlc', '<', now())->whereNotNull('dlc')->count(),
            ],
            'waitlist'      => [
                'total'    => Waitlist::count(),
                'new_week' => Waitlist::where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    // Liste utilisateurs
    public function users(Request $request)
    {
        $this->checkAdmin($request);

        $users = User::orderBy('created_at', 'desc')->get()->map(fn($u) => [
            'id'                  => $u->id,
            'name'                => $u->name,
            'email'               => $u->email,
            'role'                => $u->role,
            'blocked'             => (bool) $u->blocked,
            'email_verified_at'   => $u->email_verified_at,
            'created_at'          => $u->created_at,
            'shifts_count'        => $u->shifts()->count(),
            'interventions_count' => Intervention::whereHas('shift', fn($q) => $q->where('user_id', $u->id))->count(),
        ]);

        return response()->json($users);
    }

    // Modifier un utilisateur (role, blocked)
    public function updateUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        // Empêcher le blocage d'un admin
        if ($user->role === 'admin' && $request->has('blocked') && $request->blocked) {
            return response()->json(['message' => 'Impossible de bloquer un compte administrateur.'], 422);
        }

        $validated = $request->validate([
            'role'    => 'sometimes|in:user,admin',
            'name'    => 'sometimes|string|max:255',
            'blocked' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    // Supprimer un utilisateur
    public function destroyUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        // Empêcher la suppression de son propre compte
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        // Empêcher la suppression d'un admin
        if ($user->role === 'admin') {
            return response()->json(['message' => 'Impossible de supprimer un compte administrateur.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    // Envoyer un lien de reset de mot de passe
    public function resetPasswordUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        return response()->json(['message' => 'Email de reset envoyé.']);
    }

    // Gardes
    public function shifts(Request $request)
    {
        $this->checkAdmin($request);

        $shifts = Shift::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return response()->json($shifts);
    }

    // Interventions
    public function interventions(Request $request)
    {
        $this->checkAdmin($request);

        $interventions = Intervention::with(['shift.user:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->limit(200)
            ->get();

        return response()->json($interventions);
    }

    // Articles
    public function items(Request $request)
    {
        $this->checkAdmin($request);

        $items = Item::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($items);
    }
}