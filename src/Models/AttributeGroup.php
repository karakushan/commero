<?php

namespace Commero\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttributeGroup extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'sort'];

    public function attributes(): HasMany
    {
        return $this->hasMany(ProductAttribute::class, 'group_id');
    }
}
