<?php

namespace App\Http\Controllers;

use App\Models\{AwProduct, AwBrand, AwTag, AwProductTag, AwCategory, AwProductImage, AwProductVariant, AwVariant, AwVariantValue};
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SimpleProductController extends Controller
{
    public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);
        $brands = AwBrand::active()->get();
        $allTags = AwTag::get();
        $productTagIds = $product->tags->pluck('id')->toArray();

        return view("products/{$type}/step-{$step}", compact('product', 'step', 'type', 'brands', 'productTagIds', 'allTags'));
    }

    public static function store(Request $request, $step, $id, $type = 'simple') {
        $product = AwProduct::findOrFail($id);

        switch ($step) {
            case 1: //basic & media
            $request->validate([
                'name' => 'required|string|max:255|unique:aw_products,name,' . $id,
                'brand_id' => 'required|exists:aw_brands,id',
                'short_description' => 'required|string|min:100',
                'long_description' => 'required|string|min:200',
                // 'main_image' => 'required|image|mimes:jpeg,png,webp|max:3072|dimensions:width=800,height=800',
                'main_image' => 'required|image|mimes:jpeg,png,webp|max:3072',
                'secondary_media.*' => 'nullable|mimes:jpeg,png,webp,mp4,wav|max:5120',
                'tags' => 'nullable|array'
            ]);

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

                if ($request->hasFile('secondary_media')) {
                    foreach ($request->file('secondary_media') as $index => $media) {
                        $path = $media->store('products/gallery', 'public');
                        AwProductImage::create([
                            'product_id' => $id,
                            'image_path' => $path,
                            'position' => $index + 1
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

            case 2: //units & unit conversation mapping

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

}
