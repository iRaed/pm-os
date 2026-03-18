<?php

declare(strict_types=1);

namespace App\Core\MultiTenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

/**
 * نموذج الشركة المستأجرة (Tenant)
 * كل شركة إدارة أملاك = Tenant واحد بقاعدة بيانات مستقلة
 *
 * @property string $id UUID
 * @property string $name اسم الشركة
 * @property string $name_en اسم الشركة بالإنجليزية
 * @property string $commercial_reg رقم السجل التجاري
 * @property string $vat_number الرقم الضريبي
 * @property string $phone هاتف الشركة
 * @property string $email بريد الشركة
 * @property string $plan_id خطة الاشتراك
 * @property string $status حالة الشركة
 * @property array $settings إعدادات مخصصة
 * @property array $data بيانات إضافية مرنة
 */
class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids;

    /**
     * الحقول القابلة للتعبئة
     */
    protected static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'name_en',
            'commercial_reg',
            'vat_number',
            'phone',
            'email',
            'logo_path',
            'plan_id',
            'status',
            'max_units',
            'max_users',
            'subscription_ends_at',
            'settings',
            'trial_ends_at',
        ];
    }

    /**
     * Type casting
     */
    protected $casts = [
        'settings' => 'array',
        'data' => 'array',
        'subscription_ends_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'max_units' => 'integer',
        'max_users' => 'integer',
    ];

    /**
     * الحالات المتاحة للشركة
     */
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_CANCELLED = 'cancelled';

    // ─── Relationships ───────────────────────────────

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL);
    }

    // ─── Helpers ─────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL
            && $this->trial_ends_at?->isFuture();
    }

    public function hasReachedUnitLimit(): bool
    {
        // يتم التحقق داخل Tenant DB
        return false; // placeholder — will check from tenant context
    }

    public function hasReachedUserLimit(): bool
    {
        return false; // placeholder
    }

    /**
     * اسم قاعدة البيانات للشركة
     */
    public function database(): string
    {
        return config('tenancy.database.prefix') . $this->id;
    }
}
