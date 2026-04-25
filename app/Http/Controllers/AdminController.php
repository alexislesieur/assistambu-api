<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Intervention;
use App\Models\Item;
use App\Models\Waitlist;
use Illuminate\Http\Request;

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
                'total'     => Intervention::count(),
                'this_week' => Intervention::where('created_at', '>=', now()->subDays(7))->count(),
                'this_month'=> Intervention::where('created_at', '>=', now()->subDays(30))->count(),
            ],
            'items'         => [
                'total'     => Item::count(),
                'low_stock' => Item::whereRaw('quantity < max_quantity * 0.3')->count(),
                'expired'   => Item::where('dlc', '<', now())->whereNotNull('dlc')->count(),
            ],
            'waitlist'      => [
                'total'     => Waitlist::count(),
                'new_week'  => Waitlist::where('created_at', '>=', now()->subDays(7))->count(),
            ],
        ]);
    }

    // Utilisateurs
    public function users(Request $request)
    {
        $this->checkAdmin($request);

        $users = User::orderBy('created_at', 'desc')->get()->map(fn($u) => [
            'id'                => $u->id,
            'name'              => $u->name,
            'email'             => $u->email,
            'role'              => $u->role,
            'email_verified_at' => $u->email_verified_at,
            'created_at'        => $u->created_at,
            'shifts_count'      => $u->shifts()->count(),
            'interventions_count' => Intervention::whereHas('shift', fn($q) => $q->where('user_id', $u->id))->count(),
        ]);

        return response()->json($users);
    }

    public function updateUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $validated = $request->validate([
            'role' => 'sometimes|in:user,admin',
            'name' => 'sometimes|string|max:255',
        ]);

        $user->update($validated);

        return response()->json($user);
    }

    public function destroyUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Utilisateur supprimé.']);
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