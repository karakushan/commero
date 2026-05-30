<?php

namespace Commero\Models;

use Commero\Support\Phone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'status',
        'is_quick_order',
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'has_other_recipient',
        'recipient_first_name',
        'recipient_last_name',
        'recipient_phone',
        'recipient_email',
        'comment',
        'total_amount',
        'payment_method_code',
        'payment_method_name',
        'shipping_method_code',
        'shipping_method_name',
        'delivery_city_ref',
        'delivery_city_name',
        'delivery_warehouse_ref',
        'delivery_warehouse_name',
        'delivery_street',
        'delivery_house',
        'delivery_apartment',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'is_quick_order' => 'boolean',
        'has_other_recipient' => 'boolean',
    ];

    protected function customerPhone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => Phone::normalize($value),
        );
    }

    protected function recipientPhone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => Phone::normalize($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
