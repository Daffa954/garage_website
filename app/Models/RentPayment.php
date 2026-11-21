<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $url_payment_receipt
 * @property string|null $remarks
 * @property int $rent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Rent $rent
 */
class RentPayment extends Model
{
    protected $fillable = [
        'url_payment_receipt',
        'remarks',
        'rent_id',
    ];

    public function rent(): BelongsTo
    {
        return $this->belongsTo(Rent::class, 'rent_id');
    }
}
