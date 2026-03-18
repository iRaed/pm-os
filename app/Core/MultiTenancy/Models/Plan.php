<?php

declare(strict_types=1);

namespace App\Core\MultiTenancy\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * خطط الاشتراك
 *
 * @property string $id
 * @property string $name اسم الخطة (أساسية، احترافية، مؤسسية)
 * @property string $name_en
 * @property int $max_units الحد الأقصى للوحدات
 * @property int $max_users الحد الأقصى للمستخدمين
 * @property float $monthly_price السعر الشهري (ريال)
 * @property float $annual_price السعر السنوي (ريال)
 * @property array $features الميزات المتاحة
 * @property bool $is_active
 */
class Plan extends Model
{
    use HasUuids;

    protected $connection = 'central';

    protected $fillable = [
        'name',
        'name_en',
        'slug',
        'max_units',
        'max_users',
        'monthly_price',
        'annual_price',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'monthly_price' => 'decimal:2',
        'annual_price' => 'decimal:2',
        'max_units' => 'integer',
        'max_users' => 'integer',
        'is_active' => 'boolean',
    ];

    // ─── الخطط الأساسية ─────────────────────────────

    const PLAN_STARTER = 'starter';       // حتى 50 وحدة
    const PLAN_PROFESSIONAL = 'professional'; // حتى 500 وحدة
    const PLAN_ENTERPRISE = 'enterprise';    // غير محدود

    // ─── Relationships ───────────────────────────────

    public function tenants()
    {
        return $this->hasMany(Tenant::class);
    }

    // ─── Scopes ──────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // ─── Helpers ─────────────────────────────────────

    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }
}
