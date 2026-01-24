<?php

namespace App\Http\Controllers;

use App\Models\{AwProduct, AwBrand, AwTag, AwProductTag, AwProductUnit, AwCategory, AwProductImage, AwProductVariant, AwUnit, AwVariant, AwVariantValue};
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SimpleProductController extends Controller
{
    public static function view($product, $step, $type)
    {
        $product = AwProduct::findOrFail($product->id);
        $brands = AwBrand::active()->get();
        $units = AwUnit::get();
        $allTags = AwTag::get();
        $productTagIds = $product->tags->pluck('id')->toArray();
        $mainImage = $product->images->where('position', 0)->first();
        $gallery = $product->images->where('position', '>', 0)->sortBy('position');

        return view("products/{$type}/step-{$step}", compact('product', 'step', 'type', 'brands', 'productTagIds', 'allTags', 'mainImage', 'gallery', 'units'));
    }

    public static function store(Request $request, $step, $id, $type = 'simple')
    {
        $product = AwProduct::findOrFail($id);

        switch ($step) {
            case 1: //basic & media
                return self::basic($request, $step, $id, $product, $type = 'simple');
            case 2: //units & unit conversation mapping
                return self::units($request, $step, $id, $product, $type = 'simple');
            case 3: // pricing units wise (tier or non-tier pricing)

            case 4: // supplier mapping

            case 5: // inventory & stock management

            case 6: // categories & seo content

            case 7: // final overview

            default:
                abort(404);
                break;
        }
    }

    private static function basic(Request $request, $step, $id, $product, $type = 'simple')
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:aw_products,name,' . $id,
            'brand_id' => 'required|exists:aw_brands,id',
            'short_description' => 'required|string|min:100',
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

    private static function units(Request $request, $step, $id, $product, $type = 'simple')
    {
        $request->validate([
            'base_unit_id' => 'required|exists:aw_units,id',
            'units' => 'nullable|array',
            'units.*.unit_id' => 'required|distinct|exists:aw_units,id|different:base_unit_id',
            'units.*.quantity' => 'required|numeric|min:1',
            'default_selling_unit' => 'required'
        ]);

        DB::beginTransaction();
        try {
            AwProductUnit::where('product_id', $id)->delete();

            $baseUnit = AwProductUnit::create([
                'product_id' => $id,
                'unit_id' => $request->base_unit_id,
                'parent_unit_id' => null,
                'conversion_factor' => 1.0000,
                'is_base' => 1,
                'is_default_selling' => ($request->default_selling_unit == 'base') ? 1 : 0
            ]);

            $prevParentId = $request->base_unit_id;
            $runningFactor = 1.0000;

            if ($request->has('units')) {
                foreach ($request->units as $index => $u) {
                    $runningFactor = $runningFactor * $u['quantity'];
                    
                    AwProductUnit::create([
                        'product_id' => $id,
                        'unit_id' => $u['unit_id'],
                        'parent_unit_id' => $prevParentId,
                        'conversion_factor' => $runningFactor,
                        'is_base' => 0,
                        'is_default_selling' => ($request->default_selling_unit == $index) ? 1 : 0
                    ]);

                    $prevParentId = $u['unit_id'];
                }
            }

            DB::commit();
            return redirect()->route('product-management', ['type' => encrypt($type), 'step' => encrypt(3), 'id' => encrypt($id)]);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Unit structure error: ' . $e->getMessage());
        }
    }
}
