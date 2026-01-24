<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwProduct extends Model
{
   use SoftDeletes;

    protected $guarded = [];

    public function variants()
    {
        return $this->hasMany(AwProductVariant::class);
    }

    public function categories()
    {
        return $this->hasMany(AwProductCategory::class);
    }

    public function images()
    {
        return $this->hasMany(AwProductImage::class);
    }

    public function units()
    {
        return $this->hasMany(AwProductUnit::class);
    }

    public function prices()
    {
        return $this->hasMany(AwPrice::class);
    }

    public function bundle()
    {
        return $this->hasOne(AwBundle::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(AwInventoryMovement::class);
    }

    public function tags()
    {
        return $this->belongsToMany(AwTag::class, 'aw_product_tags', 'product_id', 'tag_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }
}
