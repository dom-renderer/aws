<?php

namespace App\Http\Controllers;

use App\Models\{User, AwProduct, AwProductVariant, AwWarehouse, AwBrand, AwCategory, AwTag, AwProductTag, AwProductUnit, AwSupplierWarehouseProduct, AwInventoryMovement, AwProductImage, AwUnit, AwPrice, AwPriceTier, AwProductCategory};
use Illuminate\Support\Facades\{Log, DB};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VariableProductController extends Controller
{
        public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);

        return view("products/{$type}/step-{$step}", compact(
            'product'
        ));
    }

    public static function store(Request $request, $step, $id, $type = 'variable')
    {
        $product = AwProduct::findOrFail($id);

        switch ($step) {
            case 1: //basic & media
                return self::basic($request, $step, $id, $product, $type = 'variable');
            case 2: //variants management
                return self::variants($request, $step, $id, $product, $type = 'variable');
            case 3: // units & unit conversation mapping
                return self::units($request, $step, $id, $product, $type = 'variable');
            case 4: // pricing units wise (tier or non-tier pricing)
                return self::pricing($request, $step, $id, $product, $type = 'variable');
            case 5: // supplier, inventory & stock management
                return self::supplier($request, $step, $id, $product, $type = 'variable');
            case 6: // categories & seo content
                return self::categories($request, $step, $id, $product, $type = 'variable');
            case 7: // substitutes
                return self::substitutes($request, $step, $id, $product, $type = 'variable');
            case 8: // final overview
                return self::review($request, $step, $id, $product, $type = 'variable');
            default:
                abort(404);
                break;
        }
    }

    protected static function basic(Request $request, $step, $id, $product, $type)
{
        $request->validate([
            'name' => 'required|string|max:255|unique:aw_products,name,' . $id,
            'brand_id' => 'required|exists:aw_brands,id',
            'short_description' => 'required|string|min:100',
            'type_switch' => 'required|in:simple,variable,bundle',
            'long_description' => 'required|string|min:200',

            'main_image' => $request->hasFile('main_image')
                ? 'image|mimes:jpeg,png,webp|max:3072|dimensions:width=800,height=800'
                : 'required',

            'secondary_media.*' => 'nullable',
        ]);

        if ($request->hasFile('secondary_media')) {
            $request->validate([
                'secondary_media.*' => 'mimes:jpeg,png,webp,mp4,wav|max:5120'
            ]);
        }

        DB::beginTransaction();
        try {

            $product->update([
                'name' => $request->name,
                'slug' => str($request->name)->slug(),
                'product_type' => $request->type_switch,
                'brand_id' => $request->brand_id,
                'short_description' => $request->short_description,
                'long_description' => $request->long_description,
                'status' => $request->has('status') && $request->status ? 'active' : 'inactive'
            ]);

            if ($request->has('tags') && is_array($request->tags)) {
                $tagIds = [];
                foreach ($request->tags as $tagName) {
                    $tag = AwTag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName)]
                    );
                    $tagIds[] = $tag->id;
                }
                AwProductTag::where('product_id', $id)->whereNotIn('tag_id', $tagIds)->delete();
                foreach ($tagIds as $tagId) {
                    AwProductTag::firstOrCreate(
                        ['product_id' => $id, 'variant_id' => null, 'tag_id' => $tagId]
                    );
                }
            } else {
                AwProductTag::where('product_id', $id)->delete();
            }

            if ($request->hasFile('main_image')) {
                $path = $request->file('main_image')->store('products/main', 'public');
                AwProductImage::updateOrCreate(
                    ['product_id' => $id, 'position' => 0],
                    ['image_path' => $path]
                );
            }

            $existingIds = json_decode($request->existing_gallery_ids ?? '[]', true);
            AwProductImage::where('product_id', $id)
                ->where('position', '>', 0)
                ->whereNotIn('id', $existingIds)
                ->delete();

            foreach ($existingIds as $index => $existingId) {
                AwProductImage::where('id', $existingId)->update(['position' => $index + 1]);
            }

            $lastPos = count($existingIds);
            if ($request->hasFile('secondary_media')) {
                foreach ($request->file('secondary_media') as $media) {
                    $lastPos++;
                    $path = $media->store('products/gallery', 'public');
                    AwProductImage::create([
                        'product_id' => $id,
                        'image_path' => $path,
                        'position' => $lastPos
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('product-management', [
                'type' => encrypt($type),
                'step' => encrypt(2),
                'id' => encrypt($id)
            ])->with('success', 'Step 1 completed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Error saving product: ' . $e->getMessage());
        }
    }

    protected static function variants(Request $request, $step, $id, $product, $type)
    {
        
    }

    protected static function units(Request $request, $step, $id, $product, $type)
    {
    }

    protected static function pricing(Request $request, $step, $id, $product, $type)
    {
    }

    protected static function supplier(Request $request, $step, $id, $product, $type)
    {
    }

    protected static function categories(Request $request, $step, $id, $product, $type)
    {
    }

    protected static function substitutes(Request $request, $step, $id, $product, $type)
    {
    }

    protected static function review(Request $request, $step, $id, $product, $type)
    {
    }
}
