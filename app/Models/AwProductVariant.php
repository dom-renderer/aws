<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwProductVariant extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(AwProduct::class);
    }

    public function images()
    {
        return $this->hasMany(AwProductImage::class, 'variant_id');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            AwAttributeValue::class,
            'aw_variant_attribute_values',
            'variant_id',
            'attribute_value_id'
        );
    }

    public function units()
    {
        return $this->hasMany(AwProductUnit::class, 'variant_id');
    }

    public function prices()
    {
        return $this->hasMany(AwPrice::class, 'variant_id');
    }
}
