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
}
