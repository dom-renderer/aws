<?php

namespace App\Http\Controllers;

use App\Models\{User, AwProduct, AwAttribute, AwAttributeValue, AwVariantAttributeValue, AwProductVariant, AwWarehouse, AwBrand, AwCategory, AwTag, AwProductTag, AwProductUnit, AwSupplierWarehouseProduct, AwInventoryMovement, AwProductImage, AwUnit, AwPrice, AwPriceTier, AwProductCategory};
use App\Models\{AwBundle, AwBundleItem};
use Illuminate\Support\Facades\{Storage, Log, DB};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BundledProductController extends Controller
{
    public static function view($product, $step, $type) {   
        $product = AwProduct::findOrFail($product->id);
        $brands = AwBrand::active()->get();
        $units = AwUnit::get();
        $allTags = AwTag::get();
        $productTagIds = $product->tags->pluck('id')->toArray();
        $mainImage = $product->images->where('position', 0)->first();
        $gallery = $product->images->where('position', '>', 0)->sortBy('position');
        $baseProductUnit = $product->units->where('is_base', 1)->first();
        $additionalUnits = $product->units->where('is_base', 0)->sortBy('conversion_factor')->values();
        $allUnits = $product->units->sortByDesc('is_base');
        $warehouses = AwWarehouse::all();
        $suppliers = User::whereHas('roles', function ($q) {
            $q->where('slug', 'supplier');
        })->get();
        $existingInventory = $product->supplierWarehouseProducts;

        $categories = AwCategory::buildCategoryTree();
        $additionalCategories = AwCategory::where('status', 1)->orderBy('name')->get();
        $selectedPrimaryCategory = null;
        $selectedAdditionalCategories = [];

        $primaryCategory = AwProductCategory::where('product_id', $product->id)
            ->where('is_primary', 1)
            ->first();

        $selectedPrimaryCategory = $primaryCategory ? $primaryCategory->category_id : null;
        $selectedAdditionalCategories = AwProductCategory::where('product_id', $product->id)
            ->where('is_primary', 0)
            ->pluck('category_id')
            ->toArray();

        $bundle = $product->bundle()->with([
            'items' => function ($q) {
                $q->with(['product', 'variant']);
            }
        ])->first();

        return view("products/{$type}/step-{$step}", compact(
            'product',
            'step',
            'type',
            'brands',
            'productTagIds',
            'allTags',
            'mainImage',
            'gallery',
            'units',
            'baseProductUnit',
            'additionalUnits',
            'allUnits',
            'warehouses',
            'suppliers',
            'existingInventory',
            'categories',
            'additionalCategories',
            'selectedPrimaryCategory',
            'selectedAdditionalCategories',
            'bundle'
        ));
    }

    public static function store($request, $step, $id, $type = 'bundle') {

        $product = AwProduct::findOrFail($id);

        switch ($step) {
            case 1: //basic & media
                return self::basic($request, $step, $id, $product, $type = 'bundle');
            case 2: // products selection with their variant base unit
                return self::selection($request, $step, $id, $product, $type = 'bundle');
            case 3: // pricing management
                return self::pricing($request, $step, $id, $product, $type = 'bundle');
            case 4: // final overview
                return self::review($request, $step, $id, $product, $type = 'bundle');
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

    protected static function selection(Request $request, $step, $id, $product, $type)
    {
        $request->validate([
            'bundle_items' => 'required|array|min:1',
            'bundle_items.*.product_id' => 'required|exists:aw_products,id',
            'bundle_items.*.variant_id' => 'nullable|exists:aw_product_variants,id',
            'bundle_items.*.unit_id' => 'required|exists:aw_units,id',
            'bundle_items.*.quantity' => 'required|integer|min:1',
        ]);

        $bundleItems = $request->input('bundle_items', []);

        // Additional integrity checks (enterprise-grade):
        // - product cannot be the bundle itself
        // - only simple/variable products can be bundled
        // - variable products require variant_id
        // - unit must belong to product (simple) or variant (variable) via aw_product_units
        // - no duplicate product+variant combos
        $seenKeys = [];
        foreach ($bundleItems as $idx => $item) {
            $lineRef = "bundle_items.{$idx}";
            $childProduct = AwProduct::find($item['product_id']);
            if (!$childProduct) {
                return back()->withErrors("Invalid product at {$lineRef}.product_id")->withInput();
            }

            if ((int)$childProduct->id === (int)$id) {
                return back()->withErrors("Bundle cannot include itself (row " . ($idx + 1) . ").")->withInput();
            }

            if (!in_array($childProduct->product_type, ['simple', 'variable'], true)) {
                return back()->withErrors("Only Simple/Variable products can be bundled (row " . ($idx + 1) . ").")->withInput();
            }

            $variantId = $item['variant_id'] ?? null;
            if ($childProduct->product_type === 'variable') {
                if (empty($variantId)) {
                    return back()->withErrors("Variant is required for variable products (row " . ($idx + 1) . ").")->withInput();
                }
                $variant = AwProductVariant::where('id', $variantId)->where('product_id', $childProduct->id)->first();
                if (!$variant) {
                    return back()->withErrors("Selected variant does not belong to selected product (row " . ($idx + 1) . ").")->withInput();
                }
            } else {
                // Simple product must not carry variant_id
                if (!empty($variantId)) {
                    return back()->withErrors("Variant should be empty for simple products (row " . ($idx + 1) . ").")->withInput();
                }
                $variantId = null;
            }

            $key = $childProduct->id . ':' . ($variantId ?? 'null');
            if (isset($seenKeys[$key])) {
                return back()->withErrors("Duplicate product/variant combination detected (row " . ($idx + 1) . ").")->withInput();
            }
            $seenKeys[$key] = true;

            $unitId = (int)$item['unit_id'];
            $unitExistsInMapping = AwProductUnit::where('product_id', $childProduct->id)
                ->where('unit_id', $unitId)
                ->when($variantId, fn($q) => $q->where('variant_id', $variantId), fn($q) => $q->whereNull('variant_id'))
                ->exists();

            if (!$unitExistsInMapping) {
                return back()->withErrors("Selected unit is not available for the selected product/variant (row " . ($idx + 1) . ").")->withInput();
            }
        }

        DB::beginTransaction();
        try {
            $bundle = AwBundle::updateOrCreate(
                ['product_id' => $id],
                [
                    // keep existing pricing config; if none exist, default it
                    'pricing_mode' => $product->bundle?->pricing_mode ?? 'fixed',
                    'discount_type' => $product->bundle?->discount_type,
                    'discount_value' => $product->bundle?->discount_value,
                ]
            );

            // Replace items atomically (soft delete old, create new)
            AwBundleItem::where('bundle_id', $bundle->id)->delete();

            foreach ($bundleItems as $item) {
                AwBundleItem::create([
                    'bundle_id' => $bundle->id,
                    'product_id' => (int)$item['product_id'],
                    'variant_id' => !empty($item['variant_id']) ? (int)$item['variant_id'] : null,
                    'unit_id' => (int)$item['unit_id'],
                    'quantity' => (int)$item['quantity'],
                ]);
            }

            DB::commit();
            return redirect()->route('product-management', [
                'type' => encrypt($type),
                'step' => encrypt(3),
                'id' => encrypt($id)
            ])->with('success', 'Bundle items saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Bundle selection failed: ' . $e->getMessage())->withInput();
        }
    }

    protected static function pricing(Request $request, $step, $id, $product, $type)
    {
        $request->validate([
            'pricing_mode' => 'required|in:fixed,sum_discount',
            'discount_type' => 'nullable|in:percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
        ]);

        $bundle = $product->bundle()->with('items')->first();
        if (!$bundle || $bundle->items->count() < 1) {
            return back()->withErrors('Please add at least one bundle item in Step 2 before configuring pricing.');
        }

        // Compute current sum for server-side validation of fixed discount
        $sum = self::calculateBundleItemsSubtotal($bundle);

        $pricingMode = $request->input('pricing_mode');
        $discountType = $request->input('discount_type');
        $discountValue = $request->input('discount_value');

        if ($pricingMode === 'fixed') {
            $discountType = null;
            $discountValue = null;
        } else {
            if (empty($discountType)) {
                return back()->withErrors('Discount type is required for Discount-based pricing mode.')->withInput();
            }
            if ($discountValue === null || $discountValue === '') {
                return back()->withErrors('Discount value is required for Discount-based pricing mode.')->withInput();
            }
            $discountValue = (float)$discountValue;

            if ($discountType === 'percentage' && $discountValue > 100) {
                return back()->withErrors('Percentage discount cannot exceed 100%.')->withInput();
            }
            if ($discountType === 'fixed' && $discountValue > $sum) {
                return back()->withErrors('Fixed discount cannot exceed bundle subtotal.')->withInput();
            }
        }

        DB::beginTransaction();
        try {
            AwBundle::updateOrCreate(
                ['product_id' => $id],
                [
                    'pricing_mode' => $pricingMode,
                    'discount_type' => $discountType,
                    'discount_value' => $discountValue,
                ]
            );
            DB::commit();

            return redirect()->route('product-management', [
                'type' => encrypt($type),
                'step' => encrypt(4),
                'id' => encrypt($id),
            ])->with('success', 'Bundle pricing saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Bundle pricing failed: ' . $e->getMessage())->withInput();
        }
    }

    protected static function review(Request $request, $step, $id, $product, $type)
    {
        try {
            $product = AwProduct::with(['bundle.items'])->findOrFail($id);
            $bundle = $product->bundle;

            if (!$bundle || $bundle->items->count() < 1) {
                return back()->withErrors('Error: You must add at least one bundle item in Step 2 before publishing.');
            }

            if (!$bundle->pricing_mode) {
                return back()->withErrors('Error: You must configure pricing in Step 3 before publishing.');
            }

            $product->update([
                'status' => $request->has('status') && $request->status ? 'active' : 'inactive'
            ]);

            return redirect()->route('products.index')->with('success', 'Bundle product "' . $product->name . '" has been published successfully!');
        } catch (\Exception $e) {
            return back()->withErrors('Publishing failed: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Search products for bundle selection (Select2 remote)
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q');
        $excludeId = $request->get('exclude');

        $products = AwProduct::query()
            ->with('brand')
            ->where('id', '!=', $excludeId)
            ->whereIn('product_type', ['simple', 'variable'])
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('variants', function ($v) use ($search) {
                        $v->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('sku', 'LIKE', "%{$search}%");
                    });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'results' => $products->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => $p->name . ' (' . ucfirst($p->product_type) . ')',
                    'product_type' => $p->product_type,
                    'brand' => $p->brand?->name,
                ];
            }),
        ]);
    }

    /**
     * AJAX: Variants for a variable product.
     */
    public function variants(AwProduct $product)
    {
        if ($product->product_type !== 'variable') {
            return response()->json(['results' => []]);
        }

        $variants = $product->variants()->orderBy('name')->get(['id', 'name', 'sku']);
        return response()->json([
            'results' => $variants->map(fn($v) => [
                'id' => $v->id,
                'text' => $v->name . ($v->sku ? " ({$v->sku})" : ''),
            ]),
        ]);
    }

    /**
     * AJAX: Units based on product or variant.
     */
    public function units(Request $request, AwProduct $product)
    {
        $variantId = $request->get('variant_id');

        $unitQuery = AwProductUnit::query()
            ->with('unit')
            ->where('product_id', $product->id);

        if ($product->product_type === 'variable') {
            if (empty($variantId)) {
                return response()->json(['results' => []]);
            }
            $unitQuery->where('variant_id', $variantId);
        } else {
            $unitQuery->whereNull('variant_id');
        }

        $units = $unitQuery->orderByDesc('is_default_selling')->orderByDesc('is_base')->get();

        return response()->json([
            'results' => $units->map(function ($pu) {
                return [
                    'id' => $pu->unit_id,
                    'text' => $pu->unit?->name ?? 'Unit',
                    'is_base' => (bool)$pu->is_base,
                    'is_default_selling' => (bool)$pu->is_default_selling,
                ];
            }),
        ]);
    }

    /**
     * AJAX: Pricing for a selected bundle item (used for live bundle totals).
     */
    public function itemPrice(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:aw_products,id',
            'variant_id' => 'nullable|exists:aw_product_variants,id',
            'unit_id' => 'required|exists:aw_units,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $unitPrice = self::resolveUnitPrice(
            (int)$request->product_id,
            $request->variant_id ? (int)$request->variant_id : null,
            (int)$request->unit_id,
            (int)$request->quantity
        );

        return response()->json([
            'unit_price' => $unitPrice,
            'line_total' => $unitPrice * (int)$request->quantity,
        ]);
    }

    protected static function resolveUnitPrice(int $productId, ?int $variantId, int $originalUnitId, int $qty): float
    {
        $price = AwPrice::with('tiers')
            ->where('product_id', $productId)
            ->where('original_unit_id', $originalUnitId)
            ->when($variantId, fn($q) => $q->where('variant_id', $variantId), fn($q) => $q->whereNull('variant_id'))
            ->first();

        if (!$price) {
            return 0.0;
        }

        if ($price->pricing_type === 'tiered') {
            $tier = $price->tiers()
                ->where('min_qty', '<=', $qty)
                ->where(function ($q) use ($qty) {
                    $q->whereNull('max_qty')->orWhere('max_qty', '>=', $qty);
                })
                ->orderBy('min_qty', 'desc')
                ->first();

            if ($tier) {
                return (float)$tier->price;
            }
        }

        return (float)$price->base_price;
    }

    protected static function calculateBundleItemsSubtotal(AwBundle $bundle): float
    {
        $sum = 0.0;
        foreach ($bundle->items as $item) {
            $sum += self::resolveUnitPrice(
                (int)$item->product_id,
                $item->variant_id ? (int)$item->variant_id : null,
                (int)$item->unit_id,
                (int)$item->quantity
            ) * (int)$item->quantity;
        }
        return $sum;
    }
}