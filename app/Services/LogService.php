<?php

namespace App\Services;

use App\Models\Log;
use Illuminate\Http\Request;

class LogService
{
    protected ?Request $request;

    public function __construct(?Request $request = null)
    {
        $this->request = $request;
    }

    public function log(
        string $action,
        ?int $userId = null,
        string $level = 'info',
        ?string $entityType = null,
        ?int $entityId = null,
        ?array $metadata = null
    ): void {
        Log::create([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'metadata'    => $metadata,
            'ip'          => $this->request?->ip(),
            'user_agent'  => $this->request?->userAgent(),
            'level'       => $level,
        ]);
    }

    // Auth
    public function login(int $userId, string $email): void
    {
        $this->log('auth.login', $userId, 'info', 'user', $userId, ['email' => $email]);
    }

    public function logout(int $userId): void
    {
        $this->log('auth.logout', $userId, 'info', 'user', $userId);
    }

    public function register(int $userId, string $email): void
    {
        $this->log('auth.register', $userId, 'info', 'user', $userId, ['email' => $email]);
    }

    public function emailVerified(int $userId): void
    {
        $this->log('auth.email_verified', $userId, 'info', 'user', $userId);
    }

    public function passwordReset(int $userId): void
    {
        $this->log('auth.password_reset', $userId, 'warning', 'user', $userId);
    }

    // Gardes
    public function shiftStart(int $userId, int $shiftId, bool $driver): void
    {
        $this->log('shift.start', $userId, 'info', 'shift', $shiftId, ['driver' => $driver]);
    }

    public function shiftEnd(int $userId, int $shiftId, int $breakMinutes, int $amplitude): void
    {
        $this->log('shift.end', $userId, 'info', 'shift', $shiftId, [
            'break_minutes' => $breakMinutes,
            'amplitude'     => $amplitude,
        ]);
    }

    // Interventions
    public function interventionCreate(int $userId, int $interventionId, string $category): void
    {
        $this->log('intervention.create', $userId, 'info', 'intervention', $interventionId, ['category' => $category]);
    }

    public function interventionDelete(int $userId, int $interventionId): void
    {
        $this->log('intervention.delete', $userId, 'warning', 'intervention', $interventionId);
    }

    // Items / Sac
    public function itemRestock(int $userId, int $itemId, string $itemName, int $oldQty, int $newQty): void
    {
        $this->log('item.restock', $userId, 'info', 'item', $itemId, [
            'name'    => $itemName,
            'old_qty' => $oldQty,
            'new_qty' => $newQty,
        ]);
    }

    public function itemCreate(int $userId, int $itemId, string $itemName): void
    {
        $this->log('item.create', $userId, 'info', 'item', $itemId, ['name' => $itemName]);
    }

    public function itemDelete(int $userId, int $itemId, string $itemName): void
    {
        $this->log('item.delete', $userId, 'warning', 'item', $itemId, ['name' => $itemName]);
    }

    // Admin
    public function adminUserBlocked(int $adminId, int $targetId, string $targetEmail): void
    {
        $this->log('admin.user_blocked', $adminId, 'danger', 'user', $targetId, ['email' => $targetEmail]);
    }

    public function adminUserUnblocked(int $adminId, int $targetId, string $targetEmail): void
    {
        $this->log('admin.user_unblocked', $adminId, 'warning', 'user', $targetId, ['email' => $targetEmail]);
    }

    public function adminUserDeleted(int $adminId, string $targetEmail): void
    {
        $this->log('admin.user_deleted', $adminId, 'danger', null, null, ['email' => $targetEmail]);
    }

    public function adminPasswordReset(int $adminId, int $targetId, string $targetEmail): void
    {
        $this->log('admin.password_reset', $adminId, 'warning', 'user', $targetId, ['email' => $targetEmail]);
    }
}