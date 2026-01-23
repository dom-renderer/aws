<?php

namespace App\Http\Controllers;

use App\Models\{AwProduct, AwBrand, AwCategory, AwProductImage, AwProductVariant, AwVariant, AwVariantValue};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SimpleProductController extends Controller
{
    public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);

        return view("products/{$type}/step-{$step}", compact(
            'product'
        ));
    }

    public static function store($request, $step, $id, $type = 'simple') {

        switch ($step) {
            case 1:
                
            case 2:

            case 3:

            case 4:

            case 5:

            case 6:

            case 7:

            case 8:

            default:
                abort(404);
                break;
        }
    }

}
