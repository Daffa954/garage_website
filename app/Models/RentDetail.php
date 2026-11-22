<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $type
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $total_day
 * @property int $total_price
 * @property bool $is_extension
 * @property int $rent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Rent $rent
 */
class RentDetail extends Model
{
    protected $fillable = [
        'type',
        'start_date',
        'end_date',
        'total_day',
        'total_price',
        'is_extension',
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
            'start_date' => 'date',
            'end_date' => 'date',
            'is_extension' => 'boolean',
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
