<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\Intervention;
use App\Models\Item;
use App\Models\Waitlist;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AdminController extends Controller
{
    public function __construct(protected LogService $log) {}

    private function checkAdmin(Request $request)
    {
        if (!in_array($request->user()->role, ['admin', 'superadmin'])) {
            abort(403, 'Accès refusé.');
        }
    }

    private function isSuperAdmin(Request $request): bool
    {
        return $request->user()->role === 'superadmin';
    }

    public function stats(Request $request)
    {
        $this->checkAdmin($request);

        return response()->json([
            'users'         => [
                'total'     => User::count(),
                'admins'    => User::where('role', 'admin')->count(),
                'superadmins' => User::where('role', 'superadmin')->count(),
                'beta'      => User::where('role', 'beta')->count(),
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

    public function updateUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $isSuperAdmin = $this->isSuperAdmin($request);
        $currentUser  = $request->user();

        // Seul superadmin peut modifier un superadmin
        if ($user->role === 'superadmin' && !$isSuperAdmin) {
            return response()->json(['message' => 'Seul le super administrateur peut modifier ce compte.'], 422);
        }

        // Seul superadmin peut modifier un admin
        if ($user->role === 'admin' && !$isSuperAdmin) {
            return response()->json(['message' => 'Seul le super administrateur peut modifier un compte administrateur.'], 422);
        }

        // Pas de blocage d'un admin/superadmin sauf par superadmin
        if (in_array($user->role, ['admin', 'superadmin']) && $request->has('blocked') && $request->blocked && !$isSuperAdmin) {
            return response()->json(['message' => 'Impossible de bloquer ce compte.'], 422);
        }

        $allowedRoles = $isSuperAdmin
            ? ['user', 'beta', 'admin', 'superadmin']
            : ['user', 'beta'];

        $validated = $request->validate([
            'role'    => 'sometimes|in:' . implode(',', $allowedRoles),
            'name'    => 'sometimes|string|max:255',
            'blocked' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        if (isset($validated['blocked'])) {
            $validated['blocked']
                ? $this->log->adminUserBlocked($currentUser->id, $user->id, $user->email)
                : $this->log->adminUserUnblocked($currentUser->id, $user->id, $user->email);
        }

        return response()->json($user);
    }

    public function destroyUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $isSuperAdmin = $this->isSuperAdmin($request);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer votre propre compte.'], 422);
        }

        if ($user->role === 'superadmin') {
            return response()->json(['message' => 'Impossible de supprimer un compte super administrateur.'], 422);
        }

        if ($user->role === 'admin' && !$isSuperAdmin) {
            return response()->json(['message' => 'Seul le super administrateur peut supprimer un compte administrateur.'], 422);
        }

        $email = $user->email;
        $user->tokens()->delete();
        $user->delete();

        $this->log->adminUserDeleted($request->user()->id, $email);

        return response()->json(['message' => 'Utilisateur supprimé.']);
    }

    public function resetPasswordUser(Request $request, User $user)
    {
        $this->checkAdmin($request);

        $token = Password::createToken($user);
        $user->sendPasswordResetNotification($token);

        $this->log->adminPasswordReset($request->user()->id, $user->id, $user->email);

        return response()->json(['message' => 'Email de reset envoyé.']);
    }

    public function shifts(Request $request)
    {
        $this->checkAdmin($request);

        return response()->json(
            Shift::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->limit(200)
                ->get()
        );
    }

    public function interventions(Request $request)
    {
        $this->checkAdmin($request);

        return response()->json(
            Intervention::with(['shift.user:id,name,email'])
                ->orderBy('created_at', 'desc')
                ->limit(200)
                ->get()
        );
    }

    public function items(Request $request)
    {
        $this->checkAdmin($request);

        return response()->json(
            Item::with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->get()
        );
    }

    public function logs(Request $request)
    {
        $this->checkAdmin($request);

        $query = \App\Models\Log::with('user:id,name,email')->orderBy('created_at', 'desc');

        if ($request->has('level'))   $query->where('level', $request->level);
        if ($request->has('action'))  $query->where('action', 'like', '%' . $request->action . '%');
        if ($request->has('user_id')) $query->where('user_id', $request->user_id);

        return response()->json($query->limit(500)->get());
    }
}