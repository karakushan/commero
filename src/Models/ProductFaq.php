<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'locale',
        'question',
        'answer',
        'sort',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
