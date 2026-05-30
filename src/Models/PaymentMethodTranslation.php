<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethodTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_method_id',
        'locale',
        'name',
        'description',
    ];

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
