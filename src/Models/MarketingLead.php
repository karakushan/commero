<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingLead extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'status',
        'subject',
        'name',
        'phone',
        'email',
        'message',
        'product_id',
        'locale',
        'source_url',
        'form_data',
        'client_meta',
        'internal_note',
        'processed_at',
    ];

    protected $casts = [
        'form_data' => 'array',
        'client_meta' => 'array',
        'processed_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
