<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property float $current_battery
 * @property int $price_charge
 * @property string|null $note
 * @property string $url_payment_receipt
 * @property int $rent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Rent $rent
 */
class EvCharge extends Model
{
    protected $fillable = [
        'current_battery',
        'price_charge',
        'note',
        'url_payment_receipt',
        'rent_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string> Array of attribute cast definitions.
     */
    protected function casts(): array
    {
        return [
            'current_battery' => 'float',
        ];
    }

    /**
     * Get the parent rental booking relationship.
     *
     * @return BelongsTo BelongsTo relationship instance to Rent model.
     */
    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class, 'rent_id');
    }
}
