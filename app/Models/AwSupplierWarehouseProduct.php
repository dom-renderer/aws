<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class AwSupplierWarehouseProduct extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function supplier()
    {
        return $this->belongsTo(AwSupplier::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(AwWarehouse::class);
    }

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
}
