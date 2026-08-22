<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'min_quantity',
        'max_quantity',
        'price_per_credit',
        'bonus_percentage',
        'is_active',
        'sort_order',
        'badge_text',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'price_per_credit' => 'decimal:2',
            'bonus_percentage' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order', 'asc');
    }

    /**
     * Calculate price and bonus breakdown for a specific quantity
     */
    public static function calculateForQuantity(int $quantity): array
    {
        $quantity = max(1, $quantity);

        // Find active plan matching quantity range
        $plan = static::active()
            ->where('min_quantity', '<=', $quantity)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')
                  ->orWhere('max_quantity', '>=', $quantity);
            })
            ->first();

        // Fallback default if no matching plan found
        if (!$plan) {
            $plan = static::active()->orderBy('min_quantity', 'desc')->first();
        }

        $rate = $plan ? (float) $plan->price_per_credit : 10.00;
        $bonusPct = $plan ? (int) $plan->bonus_percentage : 0;
        $planName = $plan ? $plan->name : 'Standard';

        $bonusCredits = (int) round(($quantity * $bonusPct) / 100);
        $totalCredits = $quantity + $bonusCredits;
        $subtotal = round($quantity * $rate, 2);
        $gst = round($subtotal * 0.18, 2); // 18% GST
        $totalAmount = $subtotal + $gst;
        $effectiveRate = $totalCredits > 0 ? round($subtotal / $totalCredits, 2) : $rate;

        // Check next tier for upsell incentive
        $nextTier = static::active()
            ->where('min_quantity', '>', $quantity)
            ->orderBy('min_quantity', 'asc')
            ->first();

        $upsellNudge = null;
        if ($nextTier) {
            $diff = $nextTier->min_quantity - $quantity;
            $upsellNudge = [
                'needed_more' => $diff,
                'next_plan_name' => $nextTier->name,
                'next_bonus_pct' => $nextTier->bonus_percentage,
                'next_rate' => (float) $nextTier->price_per_credit,
            ];
        }

        return [
            'plan_id' => $plan?->id,
            'plan_name' => $planName,
            'quantity' => $quantity,
            'rate' => $rate,
            'bonus_percentage' => $bonusPct,
            'bonus_credits' => $bonusCredits,
            'total_credits' => $totalCredits,
            'subtotal' => $subtotal,
            'gst' => $gst,
            'total_amount' => $totalAmount,
            'effective_rate' => $effectiveRate,
            'upsell_nudge' => $upsellNudge,
        ];
    }
}
