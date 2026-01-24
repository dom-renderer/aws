<?php

namespace App\Http\Controllers;

use App\Models\{AwProduct, AwBrand, AwCategory, AwProductImage, AwProductVariant, AwVariant, AwVariantValue};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BundledProductController extends Controller
{
        public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);

        return view("products/{$type}/step-{$step}", compact(
            'product'
        ));
    }

    public static function store($request, $step, $id, $type = 'bundle') {

        switch ($step) {
            case 1: //basic & media

            case 2: // products selection with their variant base unit

            case 3: // pricing management

            case 4: // final overview

            default:
                abort(404);
                break;
        }
    }
}