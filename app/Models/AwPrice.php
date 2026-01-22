<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwPrice extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    public $timestamps = false;

    public function product()
    {
        return $this->belongsTo(AwProduct::class);
    }

    public function variant()
    {
        return $this->belongsTo(AwProductVariant::class);
    }

    public function unit()
    {
        return $this->belongsTo(AwUnit::class);
    }

    public function tiers()
    {
        return $this->hasMany(AwPriceTier::class);
    }
}
