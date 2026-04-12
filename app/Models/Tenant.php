<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'db_name', 'industry'])]
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * 該租戶下的所有使用者。
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * 產業別的中文標籤，用於 Query Engine 的 system prompt。
     */
    public function domainContextLabel(): ?string
    {
        return match ($this->industry) {
            'restaurant' => '餐飲業',
            'retail' => '零售業',
            'manufacturing' => '製造業',
            default => $this->industry,
        };
    }
}
