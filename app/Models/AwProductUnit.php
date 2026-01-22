<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwProductUnit extends Model
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

    public function parentUnit()
    {
        return $this->belongsTo(AwProductUnit::class, 'parent_unit_id');
    }
}
