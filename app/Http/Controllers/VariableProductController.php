<?php

namespace App\Http\Controllers;

use App\Models\{AwProduct, AwBrand, AwCategory, AwProductImage, AwProductVariant, AwVariant, AwVariantValue};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VariableProductController extends Controller
{
        public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);

        return view("products/{$type}/step-{$step}", compact(
            'product'
        ));
    }

    public static function store($request, $step, $id, $type = 'variable') {

        switch ($step) {
            case 1: //basic & media
            
            case 2: //variants management
                
            case 3: //units & unit conversation mapping

            case 4: // pricing units wise (tier or non-tier pricing)

            case 5: // supplier mapping

            case 6: // inventory & stock management

            case 7: // categories & seo content

            case 8: // final overview

            default:
                abort(404);
                break;
        }
    }
}
