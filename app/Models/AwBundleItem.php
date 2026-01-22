<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwBundleItem extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function bundle()
    {
        return $this->belongsTo(AwBundle::class);
    }

    public function product()
    {
        return $this->belongsTo(AwProduct::class);
    }

    public function variant()
    {
        return $this->belongsTo(AwProductVariant::class);
    }
}
