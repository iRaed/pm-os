<?php

declare(strict_types=1);

namespace Modules\Foundation\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\Traits\CausesActivity;

/**
 * نموذج المستخدم (موظف شركة إدارة الأملاك)
 */
class User extends Authenticatable
{
    use HasUuids, HasApiTokens, HasRoles, Notifiable, SoftDeletes, LogsActivity, CausesActivity;

    protected $fillable = [
        'name', 'name_en', 'email', 'phone',
        'national_id', 'national_id_type', 'employee_number',
        'job_title', 'department', 'avatar_path',
        'password', 'locale', 'is_active',
        'last_login_at', 'last_login_ip', 'settings',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'settings' => 'array',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    // ─── Relationships ───────────────────────────────

    public function managedProperties()
    {
        return $this->hasMany(Property::class, 'manager_id');
    }

    public function assignedWorkOrders()
    {
        return $this->hasMany(\Modules\Maintenance\Models\WorkOrder::class, 'assigned_to_id')
            ->where('assigned_to_type', 'staff');
    }

    public function createdLeases()
    {
        return $this->hasMany(\Modules\Leasing\Models\Lease::class, 'created_by');
    }

    public function collectedPayments()
    {
        return $this->hasMany(\Modules\Collection\Models\Payment::class, 'collected_by');
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, string $dept)
    {
        return $query->where('department', $dept);
    }

    // ─── Helpers ─────────────────────────────────────

    public function recordLogin(): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
        ]);
    }

    public function getPreferredLocale(): string
    {
        return $this->locale ?? 'ar';
    }

    // ─── Activity Log ────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'is_active', 'job_title', 'department'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $e) => "تم {$e} المستخدم: {$this->name}");
    }
}
