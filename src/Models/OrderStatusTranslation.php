<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusTranslation extends Model
{
    protected $fillable = [
        'order_status_id',
        'locale',
        'name',
    ];

    public function orderStatus(): BelongsTo
    {
        return $this->belongsTo(OrderStatus::class);
    }
}
