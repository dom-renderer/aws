<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Helpers\Helper;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\Location;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Inventory;
use App\Models\ProductBaseUnit;
use App\Models\ProductAdditionalUnit;
use App\Models\ProductTierPricing;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Models\Unit;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FrontendController extends Controller
{
    public function login(Request $request) {
        if ($request->method() == 'POST') {
            $credentials = $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if ($user && !$user->email_verified_at) {
                return back()->withErrors([
                    'email' => 'Please verify your email address before logging in.',
                ])->onlyInput('email');
            }

            if (auth()->guard('customer')->attempt($credentials, $request->boolean('remember'))) {
                $request->session()->regenerate();

                self::saveAccount(auth()->guard('customer')->user()->id);

                self::syncCartOnLogin($request);

                $cookie = cookie('guest_cart', '', -1);

                return redirect()->intended(route('home'))->cookie($cookie);
            }

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        return view('front.login');
    }

    public static function saveAccount($id)
    {
        $savedAccounts = session()->get('saved_accounts', []);

        if (!in_array($id, $savedAccounts)) {
            $savedAccounts[] = $id;

            session()->put('saved_accounts', $savedAccounts);
        }
    }

    public function register(Request $request) {
        if ($request->method() == 'POST') {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $token = Str::random(64);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
                'verification_token' => $token,
                'verification_token_expires_at' => now()->addMinutes(30)
            ]);

            $user->roles()->attach(2);

            \App\Jobs\SendVerificationEmail::dispatch($user, $token);

            return redirect()->route('login')->with('success', 'Registration successful! Please check your email to verify your account.');
        }

        return view('front.register');
    }

    public function verifyEmail($token) {
        $user = User::where('verification_token', $token)->first();

        if (!$user) {
            return redirect()->route('login')->with('error', 'Invalid verification token.');
        }

        if ($user->verification_token_expires_at < now()) {
            return redirect()->route('login', ['e' => base64_encode($user->email)])->with('verification_token_send', 'Verification token has expired. Click on re-send to get new verification link in email!');
        }

        if (auth()?->guard('customer')?->check()) {
            self::saveAccount(auth()->guard('customer')->user()->id);
        }

        $user->update([
            'email_verified_at' => now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
            'status' => 1,
        ]);

        return redirect()->route('login')->with('success', 'Email verified successfully! You can now login.');
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return redirect()->route('login')->with('success', 'Your email is already verified.');
        }

        $user->verification_token = Str::uuid();
        $user->verification_token_expires_at = now()->addMinutes(30);
        $user->save();

        \App\Jobs\SendVerificationEmail::dispatch($user, $user->verification_token);

        return redirect()->route('login')->with('success', 'Verification link has been sent to your email.');
    }

    public function logout(Request $request)
    {
        if (!auth()?->guard('customer')?->check()) {
            return redirect()->route('home');
        }

        $savedAccounts = $request->session()->get('saved_accounts');

        $id = auth()?->guard('customer')?->user()?->id;
        auth()->guard('customer')->logout();

        $request->session()->regenerateToken();

        if ($savedAccounts) {
            $request->session()->put('saved_accounts', $savedAccounts);

            $savedAccounts = session()->get('saved_accounts', []);

            if (($key = array_search($id, $savedAccounts)) !== false) {
                unset($savedAccounts[$key]);
                session()->put('saved_accounts', array_values($savedAccounts));
            }
        }

        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    public function switchAccount(Request $request)
    {
        $savedAccounts = session()->get('saved_accounts', []);

        if (empty($savedAccounts)) {
            return redirect()->route('login');
        }

        $accounts = User::select('id', 'name', 'profile')->whereIn('id', $savedAccounts)->get();

        return view('front.switch-account', compact('accounts'));
    }

    public function removeAccount($id)
    {
        if (auth()?->guard('customer')?->check() && auth()?->guard('customer')?->user()?->id == $id) {
            $savedAccounts = request()->session()->get('saved_accounts');

            auth()->guard('customer')->logout();

            request()->session()->regenerateToken();

            if ($savedAccounts) {
                request()->session()->put('saved_accounts', $savedAccounts);
            }

            request()->session()->regenerateToken();

            return redirect()->route('switch-account')->with('success', 'Account removed successfully.');
        } else {
            $savedAccounts = session()->get('saved_accounts', []);

            if (($key = array_search($id, $savedAccounts)) !== false) {
                unset($savedAccounts[$key]);
                session()->put('saved_accounts', array_values($savedAccounts));
            }

            return redirect()->route('switch-account')->with('success', 'Account removed successfully.');
        }
    }

    public function addNewAccount()
    {
        if (auth()->guard('customer')->check()) {
            return redirect()->route('switch-account')->with('error', 'you need to logout first from current logged in account');
        }

        return redirect()->route('login');
    }

    public function index(Request $request) {
        $sections = \App\Models\HomePageSetting::oldest('ordering')->get();
        
        $topSellingProducts = Product::with('primaryImage')
            ->where('is_best_seller', 1)
            ->active()
            ->limit(4)
            ->get();
        
        $topSellingProductCount = Product::where('is_best_seller', 1)->active()->count();
        
        $topSellingProduct = $topSellingProducts->map(function ($product) {
            return $this->getProductWithStartingPrice($product);
        });
        
        $recentlyViewedProducts = collect();
        $recentlyViewedIds = session('recently_viewed_products', []);
        
        if (!empty($recentlyViewedIds)) {
            // Get products in the order they were viewed (most recent first)
            $recentlyViewedProducts = Product::with('primaryImage')
                ->whereIn('id', $recentlyViewedIds)
                ->active()
                ->get()
                ->sortBy(function ($product) use ($recentlyViewedIds) {
                    return array_search($product->id, $recentlyViewedIds);
                })
                ->take(4)
                ->map(function ($product) {
                    return $this->getProductWithStartingPrice($product);
                });
        }
        
        return view('front.home', compact('sections', 'topSellingProduct', 'topSellingProductCount', 'recentlyViewedProducts'));
    }
    

    private function getProductWithStartingPrice(Product $product): array
    {
        $startingPrice = 0;
        $originalPrice = 0;
        $defaultUnitType = 0;
        $defaultUnitId = 0;
        $minOrderQty = 1;
        $variantShortUrl = null;
        
        if ($product->type == 'bundled') {
            // BUNDLED PRODUCT: Calculate bundle price
            $bundleItems = \App\Models\ProductBundle::where('product_id', $product->id)
                ->with(['sourceProduct', 'sourceVariant'])
                ->get();
            
            $bundleSubtotal = 0;
            
            foreach ($bundleItems as $bundleItem) {
                $itemProduct = $bundleItem->sourceProduct;
                $itemVariant = $bundleItem->sourceVariant;
                $itemQuantity = (float) $bundleItem->quantity;
                
                // Get unit price from tier pricing
                $pricingQuery = ProductTierPricing::where('product_id', $bundleItem->source_product_id)
                    ->where('unit_type', $bundleItem->unit_type)
                    ->where('product_additional_unit_id', $bundleItem->unit_id);
                
                if ($itemVariant) {
                    $pricingQuery->where('product_variant_id', $itemVariant->id);
                } else {
                    $pricingQuery->whereNull('product_variant_id');
                }
                
                $tierPricing = $pricingQuery->orderBy('min_qty')->first();
                $unitPrice = $tierPricing ? (float) $tierPricing->price_per_unit : ($itemProduct->single_product_price ?? 0);
                $bundleSubtotal += $unitPrice * $itemQuantity;
            }
            
            // Calculate final bundle price
            $bundlePriceType = (int) $product->bundled_product_price_type; // 0 = sum, 1 = fixed
            $bundleFixedPrice = (float) $product->bundled_product_fixed_price;
            $bundleDiscountType = (int) $product->bundled_product_discount_type; // 0 = percentage, 1 = fixed
            $bundleDiscountValue = (float) $product->bundled_product_discount;
            
            $basePrice = $bundlePriceType == 1 ? $bundleFixedPrice : $bundleSubtotal;
            $discountAmount = 0;
            
            if ($bundleDiscountType == 0) { // Percentage
                $discountAmount = $basePrice * ($bundleDiscountValue / 100);
            } else { // Fixed
                $discountAmount = $bundleDiscountValue;
            }
            
            $originalPrice = $basePrice;
            $startingPrice = max(0, $basePrice - $discountAmount);
            $defaultUnitType = 0;
            $defaultUnitId = 0; // Bundled products use unit_type=0, unit_id=0
            
        } elseif ($product->type == 'variable') {
            // VARIABLE PRODUCT: Get first variant's price
            $firstVariant = ProductVariant::where('product_id', $product->id)
                ->active()
                ->first();
            
            if ($firstVariant) {
                $variantShortUrl = $firstVariant->short_url;
                
                // Get default unit for this variant
                $baseUnit = ProductBaseUnit::where('product_id', $product->id)
                    ->where('variant_id', $firstVariant->id)
                    ->where('is_default_selling_unit', 1)
                    ->first();
                
                if ($baseUnit) {
                    $defaultUnitType = 0;
                    $defaultUnitId = $baseUnit->id;
                } else {
                    // Try additional unit
                    $additionalUnit = ProductAdditionalUnit::where('product_id', $product->id)
                        ->where('variant_id', $firstVariant->id)
                        ->where('is_default_selling_unit', 1)
                        ->first();
                    
                    if ($additionalUnit) {
                        $defaultUnitType = 1;
                        $defaultUnitId = $additionalUnit->id;
                    } else {
                        // Get any base unit
                        $anyBaseUnit = ProductBaseUnit::where('product_id', $product->id)
                            ->where('variant_id', $firstVariant->id)
                            ->first();
                        if ($anyBaseUnit) {
                            $defaultUnitType = 0;
                            $defaultUnitId = $anyBaseUnit->id;
                        }
                    }
                }
                
                // Get tier pricing for variant
                if ($defaultUnitId > 0) {
                    $tierPricing = ProductTierPricing::where('product_id', $product->id)
                        ->where('product_variant_id', $firstVariant->id)
                        ->where('unit_type', $defaultUnitType)
                        ->where('product_additional_unit_id', $defaultUnitId)
                        ->orderBy('min_qty')
                        ->first();
                    
                    if ($tierPricing) {
                        $originalPrice = (float) $tierPricing->price_per_unit;
                        $discountAmount = 0;
                        
                        if ($tierPricing->discount_type == 1) { // Percentage
                            $discountAmount = $originalPrice * ($tierPricing->discount_amount / 100);
                        } else { // Fixed
                            $discountAmount = (float) $tierPricing->discount_amount;
                        }
                        
                        $startingPrice = max(0, $originalPrice - $discountAmount);
                        $minOrderQty = (float) $tierPricing->min_qty;
                    }
                }
            }
            
        } else {
            // SIMPLE PRODUCT: Get default unit price
            // Get default unit
            $baseUnit = ProductBaseUnit::where('product_id', $product->id)
                ->whereNull('variant_id')
                ->where('is_default_selling_unit', 1)
                ->first();
            
            if ($baseUnit) {
                $defaultUnitType = 0;
                $defaultUnitId = $baseUnit->id;
            } else {
                // Try additional units with default flag
                $additionalUnit = ProductAdditionalUnit::where('product_id', $product->id)
                    ->whereNull('variant_id')
                    ->where('is_default_selling_unit', 1)
                    ->first();
                
                if ($additionalUnit) {
                    $defaultUnitType = 1;
                    $defaultUnitId = $additionalUnit->id;
                } else {
                    // Get any base unit
                    $anyBaseUnit = ProductBaseUnit::where('product_id', $product->id)
                        ->whereNull('variant_id')
                        ->first();
                    if ($anyBaseUnit) {
                        $defaultUnitType = 0;
                        $defaultUnitId = $anyBaseUnit->id;
                    }
                }
            }
            
            // Get tier pricing for simple product
            if ($defaultUnitId > 0) {
                $tierPricing = ProductTierPricing::where('product_id', $product->id)
                    ->whereNull('product_variant_id')
                    ->where('unit_type', $defaultUnitType)
                    ->where('product_additional_unit_id', $defaultUnitId)
                    ->orderBy('min_qty')
                    ->first();
                
                if ($tierPricing) {
                    $originalPrice = (float) $tierPricing->price_per_unit;
                    $discountAmount = 0;
                    
                    if ($tierPricing->discount_type == 1) { // Percentage
                        $discountAmount = $originalPrice * ($tierPricing->discount_amount / 100);
                    } else { // Fixed
                        $discountAmount = (float) $tierPricing->discount_amount;
                    }
                    
                    $startingPrice = max(0, $originalPrice - $discountAmount);
                    $minOrderQty = (float) $tierPricing->min_qty;
                } else {
                    // Fallback to single_product_price if no tier pricing
                    $startingPrice = (float) ($product->single_product_price ?? 0);
                    $originalPrice = $startingPrice;
                }
            } else {
                // No unit found, use single_product_price
                $startingPrice = (float) ($product->single_product_price ?? 0);
                $originalPrice = $startingPrice;
            }
        }
        
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'short_url' => $product->short_url,
            'type' => $product->type,
            'primary_image' => $product->primaryImage?->file,
            'starting_price' => $startingPrice,
            'original_price' => $originalPrice,
            'has_discount' => $originalPrice > $startingPrice,
            'default_unit_type' => $defaultUnitType,
            'default_unit_id' => $defaultUnitId,
            'min_order_qty' => $minOrderQty,
            'variant_short_url' => $variantShortUrl,
        ];
    }

    public function search(Request $request) {
        $term = trim($request->input('q', ''));

        if ($request->ajax() || $request->wantsJson() || $request->boolean('ajax')) {
            if ($term === '') {
                return response()->json([
                    'products'   => [],
                    'categories' => [],
                ]);
            }

            $products = Product::query()
                ->select('id', 'name', 'slug', 'short_url', 'sku')
                ->active()
                ->where(function ($q) use ($term) {
                    $q->where('name', 'like', '%' . $term . '%')
                        ->orWhere('sku', 'like', '%' . $term . '%');
                })
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(function ($product) {
                    return [
                        'id'   => $product->id,
                        'name' => $product->name,
                        'sku'  => $product->sku,
                        'url'  => route('product.index', [
                            'product_slug' => $product->slug,
                            'short_url'    => $product->short_url,
                        ]),
                    ];
                });

            $categories = \App\Models\Category::query()
                ->select('id', 'name', 'slug', 'short_url')
                ->where('status', 1)
                ->where('name', 'like', '%' . $term . '%')
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(function ($category) {
                    return [
                        'id'   => $category->id,
                        'name' => $category->name,
                        'url'  => route('category.index', [
                            'category_slug' => $category->slug,
                            'short_url'     => $category->short_url,
                        ]),
                    ];
                });

            return response()->json([
                'products'   => $products,
                'categories' => $categories,
            ]);
        }

        // Fallback: just redirect home for now.
        return redirect()->route('home');
    }

    public function category(Request $request, $category_slug = null, $short_url = null) {
        $category = \App\Models\Category::where('short_url', $short_url)
            ->where('status', 1)
            ->firstOrFail();

        $productIds = \App\Models\ProductCategory::where('category_id', $category->id)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->all();

        $productsQuery = Product::with(['primaryImage', 'primaryCategory.category'])
            ->whereIn('id', $productIds)
            ->active();

        // Attribute filters
        $selectedAttributeIdsEncoded = $request->input('attributes', []);
        $selectedAttributeIds = array_filter(array_map(function ($encoded) {
            $decoded = base64_decode($encoded, true);
            return $decoded !== false ? (int) $decoded : null;
        }, is_array($selectedAttributeIdsEncoded) ? $selectedAttributeIdsEncoded : []));

        if (!empty($selectedAttributeIds)) {
            $productsQuery->whereHas('variants.attributes', function ($q) use ($selectedAttributeIds) {
                $q->whereIn('attribute_id', $selectedAttributeIds);
            });
        }

        // Price range filter (based on single_product_price)
        $priceRange = $request->input('price_range');
        if ($priceRange) {
            [$min, $max] = match ($priceRange) {
                'under_50'   => [0, 50],
                '50_100'     => [50, 100],
                '100_200'    => [100, 200],
                '200_500'    => [200, 500],
                'above_500'  => [500, null],
                default      => [null, null],
            };

            if ($min !== null) {
                $productsQuery->where('single_product_price', '>=', $min);
            }
            if ($max !== null) {
                $productsQuery->where('single_product_price', '<=', $max);
            }
        }

        // Sorting
        $sort = $request->input('sort', 'az');
        switch ($sort) {
            case 'za':
                $productsQuery->orderBy('name', 'desc');
                break;
            case 'newest':
                $productsQuery->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $productsQuery->orderBy('created_at', 'asc');
                break;
            default:
                $productsQuery->orderBy('name', 'asc');
                break;
        }

        $products = $productsQuery->paginate(12)->appends($request->query());

        // Build available attribute filters from products in this category
        $variantIds = \App\Models\ProductVariant::whereIn('product_id', $productIds)
            ->active()
            ->pluck('id');

        $attributeMap = [];

        if ($variantIds->isNotEmpty()) {
            $attributeVariants = \App\Models\ProductAttributeVariant::with('attribute')
                ->whereIn('variant_id', $variantIds)
                ->get();

            foreach ($attributeVariants as $attributeVariant) {
                $attribute = $attributeVariant->attribute;
                if (!$attribute) {
                    continue;
                }

                $group = $attribute->title;
                $id    = $attribute->id;
                $label = $attribute->value;

                if (!isset($attributeMap[$group])) {
                    $attributeMap[$group] = [];
                }

                if (!isset($attributeMap[$group][$id])) {
                    $attributeMap[$group][$id] = [
                        'id'       => $id,
                        'label'    => $label,
                        'encoded'  => base64_encode((string) $id),
                        'selected' => in_array($id, $selectedAttributeIds, true),
                    ];
                }
            }
        }

        // Normalise attribute filters for the view
        $attributeFilters = [];
        foreach ($attributeMap as $groupTitle => $values) {
            $attributeFilters[] = [
                'title'  => $groupTitle,
                'values' => array_values($values),
            ];
        }

        return view('front.category', [
            'category'          => $category,
            'products'          => $products,
            'attributeFilters'  => $attributeFilters,
            'selectedAttributes'=> $selectedAttributeIdsEncoded,
            'priceRange'        => $priceRange,
            'sort'              => $sort,
        ]);
    }

    public function product(Request $request, $slug = null, $id = null, $variant = null) {

        $product = Product::where('short_url', $id)->active()->firstOrFail();

        $recentlyViewedProducts = collect();
        $recentlyViewedIds = session('recently_viewed_products', []);
        
        if (!empty($recentlyViewedIds)) {
            // Get products in the order they were viewed (most recent first)
            $recentlyViewedProducts = Product::with('primaryImage')
                ->whereIn('id', $recentlyViewedIds)
                ->active()
                ->get()
                ->sortBy(function ($product) use ($recentlyViewedIds) {
                    return array_search($product->id, $recentlyViewedIds);
                })
                ->take(4)
                ->map(function ($product) {
                    return $this->getProductWithStartingPrice($product);
                });
        }

        // Track recently viewed products in session (limit to 10, most recent first)
        $recentlyViewed = session('recently_viewed_products', []);
        // Remove if already exists (to move to front)
        $recentlyViewed = array_values(array_diff($recentlyViewed, [$product->id]));
        // Add to front
        array_unshift($recentlyViewed, $product->id);
        // Limit to 10
        $recentlyViewed = array_slice($recentlyViewed, 0, 10);
        session(['recently_viewed_products' => $recentlyViewed]);

        $attributes = $existingAttributes = $categoryHierarchy = [];
        Helper::getProductHierarchy($product?->primaryCategory?->category?->id, $categoryHierarchy);

        $categoryHierarchy = collect($categoryHierarchy);

        if ($categoryHierarchy->count() > 3) {
            $firstTwo = collect([[
                'display' => true,
                'name' => '...'
            ],  $categoryHierarchy->take(-1)->first()])
            ->values()->all();
            
            $categoryHierarchy = $categoryHierarchy->take(2)->merge($firstTwo)->reverse()->values()->all();
        }

        // Initialize pricing and inventory data
        $units = collect();
        $tierPricings = collect();
        $totalStock = 0;
        $variantModel = null;

        if ($product->type == 'variable') {
            if (!(!empty($variant) && ProductVariant::where('product_id', $product->id)->active()->where('short_url', $variant)->exists())) {
                $variantModel = ProductVariant::with(['variantImage', 'variantSecondaryImage'])->where('product_id', $product->id)->active()->firstOrFail();

                if (!isset($variantModel->id)) {
                    $variantModel = null;
                    $variant = null;
                } else {
                    return redirect()->route('product.index', ['product_slug' => $product->slug, 'short_url' => $product->short_url, 'variant' => $variantModel->short_url]);
                }
            } else {
                $variantModel = ProductVariant::with('attributes')->where('product_id', $product->id)->where('short_url', $variant)->active()->firstOrFail();

                foreach ($variantModel?->attributes ?? [] as $attributeRelation) {
                    $existingAttributes[] = $attributeRelation->attribute_id;
                }

                $variant = $variantModel->short_url;
            }

            foreach ($product?->variants()?->active()?->with('attributes.attribute')?->get() ?? [] as $eachVariant) {
                foreach ($eachVariant?->attributes ?? [] as $attribute) {
                    if (isset($attribute->attribute->id)) {
                        if (isset($attribute->attribute->title) && isset($attribute->attribute->value)) {
                            if (array_key_exists($attribute->attribute->title, $attributes)) {
                                if (!in_array($attribute->attribute->id, array_column($attributes[$attribute->attribute->title], 'id'))) {
                                    $attributes[$attribute->attribute->title][] = [
                                        'id' => $attribute->attribute->id,
                                        'name' => $attribute->attribute->value,
                                        'is_active' => in_array($attribute->attribute->id, $existingAttributes)
                                    ];
                                }
                            } else {
                                $attributes[$attribute->attribute->title] = [
                                    [
                                        'id' => $attribute->attribute->id,
                                        'name' => $attribute->attribute->value,
                                        'is_active' => in_array($attribute->attribute->id, $existingAttributes)
                                    ]
                                ];
                            }
                        }
                    }
                }
            }

            // Load units and tier pricing for the current variant
            if ($variantModel) {
                // Get base unit
                $baseUnit = ProductBaseUnit::with('unit')
                    ->where('product_id', $product->id)
                    ->where('variant_id', $variantModel->id)
                    ->first();

                // Get additional units
                $additionalUnits = ProductAdditionalUnit::with('unit')
                    ->where('product_id', $product->id)
                    ->where('variant_id', $variantModel->id)
                    ->orderBy('is_default_selling_unit', 'desc')
                    ->get();

                // Build units collection
                $unitsArray = [];

                if ($baseUnit) {
                    $unitsArray[] = [
                        'id' => $baseUnit->id,
                        'unit_type' => 0,
                        'unit_id' => $baseUnit->unit_id,
                        'title' => $baseUnit->unit->title ?? 'Unit',
                        'is_default' => (bool) $baseUnit->is_default_selling_unit,
                    ];
                }

                foreach ($additionalUnits as $addUnit) {
                    $unitsArray[] = [
                        'id' => $addUnit->id,
                        'unit_type' => 1,
                        'unit_id' => $addUnit->unit_id,
                        'title' => $addUnit->unit->title ?? 'Unit',
                        'quantity' => (float) $addUnit->quantity,
                        'is_default' => (bool) $addUnit->is_default_selling_unit,
                    ];
                }

                $units = collect($unitsArray);

                // Get tier pricing for this variant
                $tierPricings = ProductTierPricing::where('product_id', $product->id)
                    ->where('product_variant_id', $variantModel->id)
                    ->orderBy('product_additional_unit_id')
                    ->orderBy('min_qty')
                    ->get()
                    ->map(function ($tier) {
                        $mrp = (float) $tier->price_per_unit;
                        $discountAmount = 0;
                        if ($tier->discount_type == 1) { // Percentage
                            $discountAmount = $mrp * ($tier->discount_amount / 100);
                        } else { // Fixed
                            $discountAmount = (float) $tier->discount_amount;
                        }
                        $yourPrice = max(0, $mrp - $discountAmount);

                        return [
                            'id' => $tier->id,
                            'unit_type' => (int) $tier->unit_type,
                            'product_additional_unit_id' => $tier->product_additional_unit_id,
                            'min_qty' => (float) $tier->min_qty,
                            'max_qty' => (float) $tier->max_qty,
                            'mrp' => $mrp,
                            'your_price' => $yourPrice,
                            'discount_amount' => $discountAmount,
                            'discount_type' => (int) $tier->discount_type,
                            'discount_value' => (float) $tier->discount_amount,
                        ];
                    });

                // Get total inventory stock (sum across all warehouses)

                $totalStock = Inventory::where('product_id', $product->id)
                    ->where('product_variant_id', $variantModel->id)
                    ->sum('quantity');
            }
        } elseif ($product->type == 'bundled') {
            // Bundled Product Logic
            
            // Load bundle items with related data
            $bundleItems = \App\Models\ProductBundle::where('product_id', $product->id)
                ->with(['sourceProduct', 'sourceVariant'])
                ->get();
            
            $bundleItemsData = [];
            $bundleSubtotal = 0;
            $minStock = PHP_INT_MAX;
            
            foreach ($bundleItems as $bundleItem) {
                $itemProduct = $bundleItem->sourceProduct;
                $itemVariant = $bundleItem->sourceVariant;
                $itemQuantity = (float) $bundleItem->quantity;
                
                // Get unit info
                $unitName = 'Unit';
                if ($bundleItem->unit_type == 0) {
                    $baseUnit = ProductBaseUnit::with('unit')->find($bundleItem->unit_id);
                    if ($baseUnit && $baseUnit->unit) {
                        $unitName = $baseUnit->unit->title;
                    }
                } else {
                    $addUnit = ProductAdditionalUnit::with('unit')->find($bundleItem->unit_id);
                    if ($addUnit && $addUnit->unit) {
                        $unitName = $addUnit->unit->title;
                    }
                }
                
                // Get unit price from tier pricing
                $pricingQuery = ProductTierPricing::where('product_id', $bundleItem->source_product_id)
                    ->where('unit_type', $bundleItem->unit_type)
                    ->where('product_additional_unit_id', $bundleItem->unit_id);
                
                if ($itemVariant) {
                    $pricingQuery->where('product_variant_id', $itemVariant->id);
                } else {
                    $pricingQuery->whereNull('product_variant_id');
                }
                
                $tierPricing = $pricingQuery->orderBy('min_qty')->first();
                $unitPrice = $tierPricing ? (float) $tierPricing->price_per_unit : ($itemProduct->single_product_price ?? 0);
                $itemTotal = $unitPrice * $itemQuantity;
                $bundleSubtotal += $itemTotal;
                
                // Get stock for this component
                $inventoryQuery = Inventory::where('product_id', $bundleItem->source_product_id);
                if ($itemVariant) {
                    $inventoryQuery->where('product_variant_id', $itemVariant->id);
                } else {
                    $inventoryQuery->whereNull('product_variant_id');
                }
                $componentStock = $inventoryQuery->sum('quantity');
                
                // Calculate how many bundles can be made from this component
                $bundlesFromComponent = $itemQuantity > 0 ? floor($componentStock / $itemQuantity) : 0;
                $minStock = min($minStock, $bundlesFromComponent);
                
                $bundleItemsData[] = [
                    'product_id' => $bundleItem->source_product_id,
                    'variant_id' => $bundleItem->source_variant_id,
                    'product_name' => $itemProduct->name ?? 'Unknown Product',
                    'variant_name' => $itemVariant ? $itemVariant->name : null,
                    'quantity' => $itemQuantity,
                    'unit_name' => $unitName,
                    'unit_price' => $unitPrice,
                    'total_price' => $itemTotal,
                ];
            }
            
            // Calculate final bundle price
            $bundlePriceType = (int) $product->bundled_product_price_type; // 0 = sum, 1 = fixed
            $bundleFixedPrice = (float) $product->bundled_product_fixed_price;
            $bundleDiscountType = (int) $product->bundled_product_discount_type; // 0 = percentage, 1 = fixed
            $bundleDiscountValue = (float) $product->bundled_product_discount;
            
            $basePrice = $bundlePriceType == 1 ? $bundleFixedPrice : $bundleSubtotal;
            $discountAmount = 0;
            
            if ($bundleDiscountType == 0) { // Percentage
                $discountAmount = $basePrice * ($bundleDiscountValue / 100);
            } else { // Fixed
                $discountAmount = $bundleDiscountValue;
            }
            
            $bundleFinalPrice = max(0, $basePrice - $discountAmount);
            
            // Bundle price data for view
            $bundlePricing = [
                'price_type' => $bundlePriceType,
                'subtotal' => $bundleSubtotal,
                'base_price' => $basePrice,
                'discount_type' => $bundleDiscountType,
                'discount_value' => $bundleDiscountValue,
                'discount_amount' => $discountAmount,
                'final_price' => $bundleFinalPrice,
            ];
            
            // Set total stock as minimum stock across all components
            $totalStock = $minStock === PHP_INT_MAX ? 0 : (int) $minStock;
            
            return view("front.product-details.{$product->type}", compact(
                'categoryHierarchy', 
                'product', 
                'bundleItemsData',
                'bundlePricing',
                'totalStock',
                'recentlyViewedProducts',
                'recentlyViewedIds'
            ));
        } else {
            // Simple Product Logic
            
            // Get base unit
            $baseUnit = ProductBaseUnit::with('unit')
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->first();

            // Get additional units
            $additionalUnits = ProductAdditionalUnit::with('unit')
                ->where('product_id', $product->id)
                ->whereNull('variant_id')
                ->orderBy('is_default_selling_unit', 'desc')
                ->get();

            // Build units collection
            $unitsArray = [];

            if ($baseUnit) {
                $unitsArray[] = [
                    'id' => $baseUnit->id,
                    'unit_type' => 0,
                    'unit_id' => $baseUnit->unit_id,
                    'title' => $baseUnit->unit->title ?? 'Unit',
                    'is_default' => (bool) $baseUnit->is_default_selling_unit,
                ];
            }

            foreach ($additionalUnits as $addUnit) {
                $unitsArray[] = [
                    'id' => $addUnit->id,
                    'unit_type' => 1,
                    'unit_id' => $addUnit->unit_id,
                    'title' => $addUnit->unit->title ?? 'Unit',
                    'quantity' => (float) $addUnit->quantity,
                    'is_default' => (bool) $addUnit->is_default_selling_unit,
                ];
            }

            $units = collect($unitsArray);

            // Get tier pricing for simple product
            $tierPricings = ProductTierPricing::where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->orderBy('product_additional_unit_id')
                ->orderBy('min_qty')
                ->get()
                ->map(function ($tier) {
                    $mrp = (float) $tier->price_per_unit;
                    $discountAmount = 0;
                    if ($tier->discount_type == 1) { // Percentage
                        $discountAmount = $mrp * ($tier->discount_amount / 100);
                    } else { // Fixed
                        $discountAmount = (float) $tier->discount_amount;
                    }
                    $yourPrice = max(0, $mrp - $discountAmount);

                    return [
                        'id' => $tier->id,
                        'unit_type' => (int) $tier->unit_type,
                        'product_additional_unit_id' => $tier->product_additional_unit_id,
                        'min_qty' => (float) $tier->min_qty,
                        'max_qty' => (float) $tier->max_qty,
                        'mrp' => $mrp,
                        'your_price' => $yourPrice,
                        'discount_amount' => $discountAmount,
                        'discount_type' => (int) $tier->discount_type,
                        'discount_value' => (float) $tier->discount_amount,
                    ];
                });

            // Get total inventory stock for simple product
            $totalStock = Inventory::where('product_id', $product->id)
                ->whereNull('product_variant_id')
                ->sum('quantity');
        }

        return view("front.product-details.{$product->type}", compact(
            'categoryHierarchy', 
            'product', 
            'attributes', 
            'existingAttributes', 
            'variant',
            'variantModel',
            'units',
            'tierPricings',
            'totalStock',
            'recentlyViewedProducts',
            'recentlyViewedIds'
        ));
    }

    public function getVariantByAttributes(Request $request) {
        $productShortUrl = $request->input('product_short_url');
        $selectedAttributes = $request->input('attributes', []);

        $decodedAttributes = array_map(function($attr) {
            return (int) base64_decode($attr);
        }, $selectedAttributes);

        $product = Product::where('short_url', $productShortUrl)->active()->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found']);
        }

        $variants = ProductVariant::where('product_id', $product->id)
            ->active()
            ->with('attributes')
            ->get();

        foreach ($variants as $variant) {
            $variantAttributeIds = $variant->attributes->pluck('attribute_id')->toArray();
            
            $matchCount = 0;
            foreach ($decodedAttributes as $attrId) {
                if (in_array($attrId, $variantAttributeIds)) {
                    $matchCount++;
                }
            }

            if ($matchCount === count($decodedAttributes)) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('product.index', [
                        'product_slug' => $product->slug,
                        'short_url' => $product->short_url,
                        'variant' => $variant->short_url
                    ])
                ]);
            }
        }

        return response()->json(['success' => false, 'message' => 'No matching variant found']);
    }

    /**
     * Get product pricing data for AJAX updates.
     * Returns tier pricing and inventory for a specific variant and unit.
     */
    public function getProductPricingData(Request $request)
    {
        $request->validate([
            'product_short_url'         => 'required|string',
            'product_variant_short_url' => 'nullable|string',
            'unit_type'                 => 'nullable|integer|in:0,1',
            'unit_id'                   => 'nullable|integer',
        ]);

        $product = Product::where('short_url', $request->product_short_url)->active()->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        // Handle bundled products (unit_type=0, unit_id=0)
        if ($product->type == 'bundled' && $request->unit_type == 0 && $request->unit_id == 0) {
            // Calculate bundle price from bundle items
            $bundleItems = \App\Models\ProductBundle::where('product_id', $product->id)
                ->with(['sourceProduct', 'sourceVariant'])
                ->get();
            
            $bundleSubtotal = 0;
            
            foreach ($bundleItems as $bundleItem) {
                $itemProduct = $bundleItem->sourceProduct;
                $itemVariant = $bundleItem->sourceVariant;
                $itemQuantity = (float) $bundleItem->quantity;
                
                // Get unit price from tier pricing
                $pricingQuery = ProductTierPricing::where('product_id', $bundleItem->source_product_id)
                    ->where('unit_type', $bundleItem->unit_type)
                    ->where('product_additional_unit_id', $bundleItem->unit_id);
                
                if ($itemVariant) {
                    $pricingQuery->where('product_variant_id', $itemVariant->id);
                } else {
                    $pricingQuery->whereNull('product_variant_id');
                }
                
                $tierPricing = $pricingQuery->orderBy('min_qty')->first();
                $unitPrice = $tierPricing ? (float) $tierPricing->price_per_unit : ($itemProduct->single_product_price ?? 0);
                $bundleSubtotal += $unitPrice * $itemQuantity;
            }
            
            // Calculate final bundle price
            $bundlePriceType = (int) $product->bundled_product_price_type; // 0 = sum, 1 = fixed
            $bundleFixedPrice = (float) $product->bundled_product_fixed_price;
            $bundleDiscountType = (int) $product->bundled_product_discount_type; // 0 = percentage, 1 = fixed
            $bundleDiscountValue = (float) $product->bundled_product_discount;
            
            $basePrice = $bundlePriceType == 1 ? $bundleFixedPrice : $bundleSubtotal;
            $discountAmount = 0;
            
            if ($bundleDiscountType == 0) { // Percentage
                $discountAmount = $basePrice * ($bundleDiscountValue / 100);
            } else { // Fixed
                $discountAmount = $bundleDiscountValue;
            }
            
            $finalPrice = max(0, $basePrice - $discountAmount);
            
            return response()->json([
                'success' => true,
                'pricing_type' => 1, // Single pricing for bundled products
                'single_pricing' => [
                    'id' => 0,
                    'unit_type' => 0,
                    'product_additional_unit_id' => 0,
                    'min_qty' => 1,
                    'max_qty' => 0,
                    'mrp' => $basePrice,
                    'your_price' => $finalPrice,
                    'discount_amount' => $discountAmount,
                    'discount_type' => $bundleDiscountType,
                    'discount_value' => $bundleDiscountValue,
                    'pricing_type' => 1,
                ],
                'tier_pricings' => [],
                'total_stock' => 0, // Bundle stock is calculated differently
            ]);
        }

        $variantId = null;
        if ($request->filled('product_variant_short_url')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('short_url', $request->product_variant_short_url)
                ->active()
                ->first();

            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
            }

            $variantId = $variant->id;
        }

        $unitType = $request->filled('unit_type') ? (int) $request->unit_type : null;
        $unitId = $request->filled('unit_id') ? (int) $request->unit_id : null;

        $pricingQuery = ProductTierPricing::where('product_id', $product->id);

        if ($variantId) {
            $pricingQuery->where('product_variant_id', $variantId);
        } else {
            $pricingQuery->whereNull('product_variant_id');
        }

        if ($unitType !== null) {
            $pricingQuery->where('unit_type', $unitType);
        }

        if ($unitId !== null) {
            $pricingQuery->where('product_additional_unit_id', $unitId);
        }

        $pricingRecords = $pricingQuery->orderBy('min_qty')->get();

        $singlePricing = null;
        $tierPricings = collect();

        foreach ($pricingRecords as $pricing) {
            $mrp = (float) $pricing->price_per_unit;
            $discountAmount = 0;
            if ($pricing->discount_type == 1) {
                $discountAmount = $mrp * ($pricing->discount_amount / 100);
            } else {
                $discountAmount = (float) $pricing->discount_amount;
            }
            $yourPrice = max(0, $mrp - $discountAmount);

            $pricingData = [
                'id' => $pricing->id,
                'unit_type' => (int) $pricing->unit_type,
                'product_additional_unit_id' => $pricing->product_additional_unit_id,
                'min_qty' => (float) $pricing->min_qty,
                'max_qty' => (float) $pricing->max_qty,
                'mrp' => $mrp,
                'your_price' => $yourPrice,
                'discount_amount' => $discountAmount,
                'discount_type' => (int) $pricing->discount_type,
                'discount_value' => (float) $pricing->discount_amount,
                'pricing_type' => (int) $pricing->pricing_type,
            ];

            if ($pricing->pricing_type == 1) {
                $singlePricing = $pricingData;
            } else {
                $tierPricings->push($pricingData);
            }
        }

        $stockQuery = Inventory::where('product_id', $product->id);
        if ($variantId) {
            $stockQuery->where('product_variant_id', $variantId);
        } else {
            $stockQuery->whereNull('product_variant_id');
        }
        $totalStock = $stockQuery->sum('quantity');

        return response()->json([
            'success' => true,
            'pricing_type' => $singlePricing ? 1 : ($tierPricings->isNotEmpty() ? 0 : null),
            'single_pricing' => $singlePricing,
            'tier_pricings' => $tierPricings->values(),
            'total_stock' => (int) $totalStock,
        ]);
    }

    public function wishlist(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return redirect()->route('login');
        }

        $customerId = auth()->guard('customer')->id();
        $wishlists = Wishlist::with([
            'product.primaryImage', 
            'product.images', 
            'product.primaryCategory.category',
            'productVariant.variantImage'
        ])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();

        return view('front.panel.wishlist', compact('wishlists'));
    }

    public function addToWishlist(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Please login to add items to wishlist'], 401);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $customerId = auth()->guard('customer')->id();

        $existing = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $request->product_id)
            ->where('product_variant_id', $request->product_variant_id)
            ->first();

        if ($existing) {
            return response()->json(['success' => false, 'message' => 'Item already in wishlist']);
        }

        Wishlist::updateOrCreate([
            'customer_id' => $customerId,
            'product_id' => $request->product_id,
            'product_variant_id' => $request->product_variant_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Item added to wishlist']);
    }

    public function removeFromWishlist(Request $request, $id)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $wishlist = Wishlist::where('id', $id)
            ->where('customer_id', auth()->guard('customer')->id())
            ->firstOrFail();

        $wishlist->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Item removed from wishlist']);
        }

        return redirect()->route('wishlist')->with('success', 'Item removed from wishlist');
    }

    public function addresses(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return redirect()->route('login');
        }

        $customerId = auth()->guard('customer')->id();
        $addresses = Location::with(['country', 'state', 'city'])
            ->where('customer_id', $customerId)
            ->latest()
            ->get();
        $countries = Country::pluck('name', 'id');

        return view('front.panel.addresses', compact('addresses', 'countries'));
    }

    public function storeAddress(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:locations,code',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'zipcode' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $location = Location::create([
            'customer_id' => auth()->guard('customer')->id(),
            'name' => $request->name,
            'code' => $request->code,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'zipcode' => $request->zipcode,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'fax' => $request->fax,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Address added successfully', 'address' => $location->load(['country', 'state', 'city'])]);
        }

        return redirect()->route('addresses')->with('success', 'Address added successfully');
    }

    public function updateAddress(Request $request, $id)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $location = Location::where('id', $id)
            ->where('customer_id', auth()->guard('customer')->id())
            ->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:locations,code,' . $location->id,
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'zipcode' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'contact_number' => 'required|string|max:20',
            'fax' => 'nullable|string|max:20',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $location->update([
            'name' => $request->name,
            'code' => $request->code,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'country_id' => $request->country_id,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'zipcode' => $request->zipcode,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'fax' => $request->fax,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Address updated successfully', 'address' => $location->load(['country', 'state', 'city'])]);
        }

        return redirect()->route('addresses')->with('success', 'Address updated successfully');
    }

    public function deleteAddress(Request $request, $id)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $location = Location::where('id', $id)
            ->where('customer_id', auth()->guard('customer')->id())
            ->firstOrFail();

        $location->delete();

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Address deleted successfully']);
        }

        return redirect()->route('addresses')->with('success', 'Address deleted successfully');
    }
    
    public function wishlistStatus(Request $request)
    {
        $loggedIn = auth()->guard('customer')->check();

        if (!$loggedIn) {
            return response()->json([
                'success' => true,
                'logged_in' => false,
                'wishlists' => []
            ]);
        }

        $customerId = auth()->guard('customer')->id();
        $wishlists = Wishlist::with(['product:id,short_url', 'productVariant:id,short_url'])
            ->where('customer_id', $customerId)
            ->get()
            ->map(function ($w) {
                return [
                    'product_id' => $w->product_id,
                    'product_variant_id' => $w->product_variant_id,
                    'product_short_url' => $w->product?->short_url,
                    'product_variant_short_url' => $w->productVariant?->short_url,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'logged_in' => true,
            'wishlists' => $wishlists,
        ]);
    }

    public function toggleWishlist(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'product_short_url' => 'required|string',
            'product_variant_short_url' => 'nullable|string',
        ]);

        $product = Product::where('short_url', $request->product_short_url)->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $variantId = null;
        if ($request->filled('product_variant_short_url')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('short_url', $request->product_variant_short_url)
                ->first();
            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
            }
            $variantId = $variant->id;
        }

        $customerId = auth()->guard('customer')->id();

        $existing = Wishlist::where('customer_id', $customerId)
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variantId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['success' => true, 'in_wishlist' => false]);
        }

        Wishlist::updateOrCreate([
            'customer_id' => $customerId,
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
        ]);

        return response()->json(['success' => true, 'in_wishlist' => true]);
    }

    public function mergeWishlist(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $items = $request->input('items', []);
        if (!is_array($items)) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 422);
        }

        $customerId = auth()->guard('customer')->id();
        $merged = 0;

        foreach ($items as $item) {
            $pShort = $item['product_short_url'] ?? null;
            $vShort = $item['product_variant_short_url'] ?? null;
            if (!$pShort) { continue; }

            $product = Product::where('short_url', $pShort)->first();
            if (!$product) { continue; }

            $variantId = null;
            if ($vShort) {
                $variant = ProductVariant::where('product_id', $product->id)
                    ->where('short_url', $vShort)
                    ->first();
                if (!$variant) { continue; }
                $variantId = $variant->id;
            }

            $exists = Wishlist::withTrashed()
                ->where('customer_id', $customerId)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variantId)
                ->first();

            if ($exists) {
                if ($exists->trashed()) {
                    $exists->restore();
                    $merged++;
                }
                continue;
            }

            Wishlist::updateOrCreate([
                'customer_id' => $customerId,
                'product_id' => $product->id,
                'product_variant_id' => $variantId,
            ]);
            $merged++;
        }

        return response()->json(['success' => true, 'merged' => $merged]);
    }
    
    public function cart(Request $request)
    {
        $loggedIn = auth()->guard('customer')->check();
        $cartItems = collect();

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::with([
                'items.product.primaryImage',
                'items.productVariant',
            ])
                ->where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $cartItems = $cart->items;
            }
        } else {
            $sessionId = $request->session()->getId();
            $cart = Cart::with([
                'items.product.primaryImage',
                'items.productVariant',
            ])
                ->where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $cartItems = $cart->items;
            }
        }

        return view('front.cart', [
            'loggedIn'  => $loggedIn,
            'cartItems' => $cartItems,
        ]);
    }

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_short_url' => 'required|string',
            'product_variant_short_url' => 'nullable|string',
            'unit_type' => 'required|integer|in:0,1',
            'unit_id' => 'required|integer|min:0',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $product = Product::where('short_url', $request->product_short_url)->active()->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found'], 404);
        }

        $variantId = null;
        if ($request->filled('product_variant_short_url')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('short_url', $request->product_variant_short_url)
                ->active()
                ->first();
            if (!$variant) {
                return response()->json(['success' => false, 'message' => 'Variant not found'], 404);
            }
            $variantId = $variant->id;
        }

        $loggedIn = auth()->guard('customer')->check();

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                ]);
            }

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variantId)
                ->where('unit_type', $request->unit_type)
                ->where('unit_id', $request->unit_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity = (float) $existingItem->quantity + (float) $request->quantity;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'unit_type' => $request->unit_type,
                    'unit_id' => $request->unit_id,
                    'quantity' => (float) $request->quantity,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart',
            ]);
        } else {
            $cartCookie = $request->cookie('guest_cart');
            $cartData = $cartCookie ? json_decode($cartCookie, true) : [];

            $itemKey = $product->id . '_' . ($variantId ?? '0') . '_' . $request->unit_type . '_' . $request->unit_id;
            
            if (isset($cartData[$itemKey])) {
                $cartData[$itemKey]['quantity'] = (float) $cartData[$itemKey]['quantity'] + (float) $request->quantity;
            } else {
                $cartData[$itemKey] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'unit_type' => (int) $request->unit_type,
                    'unit_id' => (int) $request->unit_id,
                    'quantity' => (float) $request->quantity,
                    'product_short_url' => $product->short_url,
                    'product_variant_short_url' => $request->product_variant_short_url,
                ];
            }

            $sessionId = $request->session()->getId();
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'session_id' => $sessionId,
                ]);
            }

            $existingItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('product_variant_id', $variantId)
                ->where('unit_type', $request->unit_type)
                ->where('unit_id', $request->unit_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity = (float) $existingItem->quantity + (float) $request->quantity;
                $existingItem->save();
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'unit_type' => $request->unit_type,
                    'unit_id' => $request->unit_id,
                    'quantity' => (float) $request->quantity,
                ]);
            }

            $cookie = cookie('guest_cart', json_encode($cartData), 60 * 24 * 30);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart',
            ])->cookie($cookie);
        }
    }

    public function updateCartItem(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.01',
        ]);

        $loggedIn = auth()->guard('customer')->check();

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('id', $request->item_id)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $item->quantity = (float) $request->quantity;
            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Cart updated',
            ]);
        } else {
            $cartCookie = $request->cookie('guest_cart');
            $cartData = $cartCookie ? json_decode($cartCookie, true) : [];

            $sessionId = $request->session()->getId();
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('id', $request->item_id)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $item->quantity = (float) $request->quantity;
            $item->save();

            $itemKey = $item->product_id . '_' . ($item->product_variant_id ?? '0') . '_' . $item->unit_type . '_' . $item->unit_id;
            if (isset($cartData[$itemKey])) {
                $cartData[$itemKey]['quantity'] = (float) $request->quantity;
            }

            $cookie = cookie('guest_cart', json_encode($cartData), 60 * 24 * 30);

            return response()->json([
                'success' => true,
                'message' => 'Cart updated',
            ])->cookie($cookie);
        }
    }

    public function removeFromCart(Request $request)
    {
        $request->validate([
            'item_id' => 'required|integer',
        ]);

        $loggedIn = auth()->guard('customer')->check();

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('id', $request->item_id)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
            ]);
        } else {
            $cartCookie = $request->cookie('guest_cart');
            $cartData = $cartCookie ? json_decode($cartCookie, true) : [];
            $sessionId = $request->session()->getId();
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                return response()->json(['success' => false, 'message' => 'Cart not found'], 404);
            }

            $item = CartItem::where('id', $request->item_id)
                ->where('cart_id', $cart->id)
                ->first();

            if (!$item) {
                return response()->json(['success' => false, 'message' => 'Item not found'], 404);
            }

            $itemKey = $item->product_id . '_' . ($item->product_variant_id ?? '0') . '_' . $item->unit_type . '_' . $item->unit_id;
            unset($cartData[$itemKey]);

            $item->delete();

            $cookie = cookie('guest_cart', json_encode($cartData), 60 * 24 * 30);

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart',
            ])->cookie($cookie);
        }
    }

    public function getCartCount(Request $request)
    {
        $loggedIn = auth()->guard('customer')->check();
        $count = 0;

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $count = $cart->items()->sum('quantity');
            }
        } else {
            $sessionId = $request->session()->getId();
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $count = $cart->items()->sum('quantity');
            }
        }

        return response()->json([
            'success' => true,
            'count' => (int) $count,
        ]);
    }

    public function getCartItemQuantity(Request $request)
    {
        $request->validate([
            'product_short_url' => 'required|string',
            'product_variant_short_url' => 'nullable|string',
            'unit_type' => 'required|integer|in:0,1',
            'unit_id' => 'required|integer|min:0',
        ]);

        $product = Product::where('short_url', $request->product_short_url)->active()->first();
        if (!$product) {
            return response()->json(['success' => false, 'quantity' => 0]);
        }

        $variantId = null;
        if ($request->filled('product_variant_short_url')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('short_url', $request->product_variant_short_url)
                ->active()
                ->first();
            if ($variant) {
                $variantId = $variant->id;
            }
        }

        $loggedIn = auth()->guard('customer')->check();
        $quantity = 0;
        $itemId = null;

        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $item = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variantId)
                    ->where('unit_type', $request->unit_type)
                    ->where('unit_id', $request->unit_id)
                    ->first();

                if ($item) {
                    $quantity = (float) $item->quantity;
                    $itemId = $item->id;
                }
            }
        } else {
            $sessionId = $request->session()->getId();
            $cart = Cart::where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if ($cart) {
                $item = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variantId)
                    ->where('unit_type', $request->unit_type)
                    ->where('unit_id', $request->unit_id)
                    ->first();

                if ($item) {
                    $quantity = (float) $item->quantity;
                    $itemId = $item->id;
                }
            }
        }

        return response()->json([
            'success' => true,
            'quantity' => $quantity,
            'item_id' => $itemId,
        ]);
    }

    public static function syncCartOnLogin(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return;
        }

        $customerId = auth()->guard('customer')->id();
        $sessionId = $request->session()->getId();

        $guestCart = Cart::where('session_id', $sessionId)
            ->whereNull('customer_id')
            ->whereNull('converted_to_order_id')
            ->latest('id')
            ->first();

        if (!$guestCart || $guestCart->items()->count() == 0) {
            $cartCookie = $request->cookie('guest_cart');
            if ($cartCookie) {
                $cartData = json_decode($cartCookie, true);
                if ($cartData && count($cartData) > 0) {
                    $userCart = Cart::where('customer_id', $customerId)
                        ->whereNull('converted_to_order_id')
                        ->latest('id')
                        ->first();

                    if (!$userCart) {
                        $userCart = Cart::create([
                            'customer_id' => $customerId,
                        ]);
                    }

                    foreach ($cartData as $itemData) {
                        $existingItem = CartItem::where('cart_id', $userCart->id)
                            ->where('product_id', $itemData['product_id'])
                            ->where('product_variant_id', $itemData['product_variant_id'])
                            ->where('unit_type', $itemData['unit_type'])
                            ->where('unit_id', $itemData['unit_id'])
                            ->first();

                        if ($existingItem) {
                            $existingItem->quantity = (float) $existingItem->quantity + (float) $itemData['quantity'];
                            $existingItem->save();
                        } else {
                            CartItem::create([
                                'cart_id' => $userCart->id,
                                'product_id' => $itemData['product_id'],
                                'product_variant_id' => $itemData['product_variant_id'],
                                'unit_type' => $itemData['unit_type'],
                                'unit_id' => $itemData['unit_id'],
                                'quantity' => (float) $itemData['quantity'],
                            ]);
                        }
                    }
                }
            }
            return;
        }

        $userCart = Cart::where('customer_id', $customerId)
            ->whereNull('converted_to_order_id')
            ->latest('id')
            ->first();

        if (!$userCart) {
            $guestCart->customer_id = $customerId;
            $guestCart->session_id = null;
            $guestCart->save();
            return;
        }

        foreach ($guestCart->items as $guestItem) {
            $existingItem = CartItem::where('cart_id', $userCart->id)
                ->where('product_id', $guestItem->product_id)
                ->where('product_variant_id', $guestItem->product_variant_id)
                ->where('unit_type', $guestItem->unit_type)
                ->where('unit_id', $guestItem->unit_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity = (float) $existingItem->quantity + (float) $guestItem->quantity;
                $existingItem->save();
                $guestItem->delete();
            } else {
                $guestItem->cart_id = $userCart->id;
                $guestItem->save();
            }
        }

        $guestCart->delete();
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'zipcode' => 'required|string|max:20',
            'country_id' => 'required|exists:countries,id',
            'state_id' => 'required|exists:states,id',
            'payment_method' => 'required|in:credit_debit_card,cash_on_delivery',
            'customer_notes' => 'nullable|string|max:1000',
            'card_number' => 'required_if:payment_method,credit_debit_card|nullable|string|max:19',
            'expiry_date' => 'required_if:payment_method,credit_debit_card|nullable|string',
            'cvv' => 'required_if:payment_method,credit_debit_card|nullable|string|max:4',
            'cardholder_name' => 'required_if:payment_method,credit_debit_card|nullable|string|max:255',
        ]);

        $loggedIn = auth()->guard('customer')->check();
        $sessionId = $request->session()->getId();

        $cart = null;
        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            $cart = Cart::with('items.product', 'items.productVariant')
                ->where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();
        } else {
            $cart = Cart::with('items.product', 'items.productVariant')
                ->where('session_id', $sessionId)
                ->whereNull('customer_id')
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();
        }

        if (!$cart || $cart->items()->count() == 0) {
            return response()->json([
                'success' => false,
                'message' => 'Your cart is empty'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $customer = null;
            if ($loggedIn) {
                $customer = auth()->guard('customer')->user();
            } else {
                $existingUser = User::where('email', $request->email)->first();
                if ($existingUser) {
                    $customer = $existingUser;
                } else {
                    $customer = User::create([
                        'name' => $request->first_name . ' ' . $request->last_name,
                        'email' => $request->email,
                        'password' => Hash::make(Str::random(16)),
                        'status' => 1,
                        'email_verified_at' => now(),
                    ]);

                    $customer->roles()->attach(2);
                }
            }

            $city = City::where('state_id', $request->state_id)->first();
            if (!$city) {
                $city = City::where('country_id', $request->country_id)->first();
            }

            $location = Location::create([
                'customer_id' => $customer->id,
                'name' => $request->first_name . ' ' . $request->last_name,
                'code' => 'LOC-' . time(),
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $city ? $city->id : null,
                'zipcode' => $request->zipcode,
                'email' => $request->email,
                'contact_number' => $request->phone,
            ]);

            $warehouse = Warehouse::where('type', 1)->first();
            if (!$warehouse) {
                throw new \Exception('Stock is not available in the warehouse.');
            }

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_number' => Order::generateOrderNumber(),
                'location_id' => $location->id,
                'warehouse_id' => $warehouse->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'currency' => 'USD',
                'shipping_address_line_1' => $request->address_line_1,
                'shipping_address_line_2' => $request->address_line_2,
                'shipping_country_id' => $request->country_id,
                'shipping_state_id' => $request->state_id,
                'shipping_city_id' => $city ? $city->id : null,
                'shipping_zipcode' => $request->zipcode,
                'shipping_phone' => $request->phone,
                'shipping_email' => $request->email,
                'billing_address_line_1' => $request->address_line_1,
                'billing_address_line_2' => $request->address_line_2,
                'billing_country_id' => $request->country_id,
                'billing_state_id' => $request->state_id,
                'billing_city_id' => $location->city_id,
                'billing_zipcode' => $request->zipcode,
                'billing_phone' => $request->phone,
                'billing_email' => $request->email,
                'customer_notes' => $request->customer_notes,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $subtotal = 0;
            $discountAmount = 0;

            foreach ($cart->items as $cartItem) {
                $product = $cartItem->product;
                $variant = $cartItem->productVariant;
                $quantity = (float) $cartItem->quantity;
                $unitType = $cartItem->unit_type;
                $unitId = $cartItem->unit_id;

                $unit = null;
                $unitName = 'Unit';
                if ($unitType == 0 && $unitId > 0) {
                    $baseUnit = ProductBaseUnit::with('unit')->find($unitId);
                    if ($baseUnit && $baseUnit->unit) {
                        $unit = $baseUnit;
                        $unitName = $baseUnit->unit->title;
                    }
                } elseif ($unitType == 1) {
                    $addUnit = ProductAdditionalUnit::with('unit')->find($unitId);
                    if ($addUnit && $addUnit->unit) {
                        $unit = $addUnit;
                        $unitName = $addUnit->unit->title;
                    }
                }

                $pricePerUnit = 0;
                $discountPerUnit = 0;

                // Handle bundled products (unit_type=0, unit_id=0)
                if ($product->type == 'bundled' && $unitType == 0 && $unitId == 0) {
                    $unitName = 'Bundle';
                    
                    // Calculate bundle price from bundle items
                    $bundleItems = \App\Models\ProductBundle::where('product_id', $product->id)
                        ->with(['sourceProduct', 'sourceVariant'])
                        ->get();
                    
                    $bundleSubtotal = 0;
                    
                    foreach ($bundleItems as $bundleItem) {
                        $itemProduct = $bundleItem->sourceProduct;
                        $itemVariant = $bundleItem->sourceVariant;
                        $itemQuantity = (float) $bundleItem->quantity;
                        
                        // Get unit price from tier pricing
                        $bPricingQuery = ProductTierPricing::where('product_id', $bundleItem->source_product_id)
                            ->where('unit_type', $bundleItem->unit_type)
                            ->where('product_additional_unit_id', $bundleItem->unit_id);
                        
                        if ($itemVariant) {
                            $bPricingQuery->where('product_variant_id', $itemVariant->id);
                        } else {
                            $bPricingQuery->whereNull('product_variant_id');
                        }
                        
                        $tierPricing = $bPricingQuery->orderBy('min_qty')->first();
                        $bUnitPrice = $tierPricing ? (float) $tierPricing->price_per_unit : ($itemProduct->single_product_price ?? 0);
                        $bundleSubtotal += $bUnitPrice * $itemQuantity;
                    }
                    
                    // Calculate final bundle price
                    $bundlePriceType = (int) $product->bundled_product_price_type; // 0 = sum, 1 = fixed
                    $bundleFixedPrice = (float) $product->bundled_product_fixed_price;
                    $bundleDiscountType = (int) $product->bundled_product_discount_type; // 0 = percentage, 1 = fixed
                    $bundleDiscountValue = (float) $product->bundled_product_discount;
                    
                    $basePrice = $bundlePriceType == 1 ? $bundleFixedPrice : $bundleSubtotal;
                    
                    if ($bundleDiscountType == 0) { // Percentage
                        $discountPerUnit = $basePrice * ($bundleDiscountValue / 100);
                    } else { // Fixed
                        $discountPerUnit = $bundleDiscountValue;
                    }
                    
                    $pricePerUnit = max(0, $basePrice - $discountPerUnit);
                } else {
                    // Regular tier pricing for simple/variable products
                    $pricingQuery = ProductTierPricing::where('product_id', $product->id)
                        ->where('unit_type', $unitType)
                        ->where('product_additional_unit_id', $unitId);

                    if ($variant) {
                        $pricingQuery->where('product_variant_id', $variant->id);
                    } else {
                        $pricingQuery->whereNull('product_variant_id');
                    }

                    $pricingRecords = $pricingQuery->orderBy('min_qty')->get();

                    if ($pricingRecords->count() > 0) {
                        $applicablePricing = null;
                        foreach ($pricingRecords as $pricing) {
                            if ($pricing->pricing_type == 1) {
                                $applicablePricing = $pricing;
                                break;
                            } else {
                                if ($quantity >= $pricing->min_qty && ($pricing->max_qty == 0 || $quantity <= $pricing->max_qty)) {
                                    $applicablePricing = $pricing;
                                }
                            }
                        }

                        if (!$applicablePricing && $pricingRecords->first()) {
                            $applicablePricing = $pricingRecords->first();
                        }

                        if ($applicablePricing) {
                            $mrp = (float) $applicablePricing->price_per_unit;
                            if ($applicablePricing->discount_type == 1) {
                                $discountPerUnit = $mrp * ($applicablePricing->discount_amount / 100);
                            } else {
                                $discountPerUnit = (float) $applicablePricing->discount_amount;
                            }
                            $pricePerUnit = max(0, $mrp - $discountPerUnit);
                        }
                    }

                    if ($pricePerUnit == 0) {
                        $pricePerUnit = (float) $product->single_product_price ?? 0;
                    }
                }

                $itemSubtotal = $pricePerUnit * $quantity;
                $itemDiscount = $discountPerUnit * $quantity;
                $itemTax = $itemSubtotal * 0.1;
                $itemTotal = $itemSubtotal + $itemTax;

                $subtotal += $itemSubtotal;
                $discountAmount += $itemDiscount;

                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant ? $variant->id : null,
                    'product_name' => $variant ? $variant->name : $product->name,
                    'product_sku' => $variant ? ($variant->sku ?? $product->sku) : $product->sku,
                    'variant_name' => $variant ? $variant->short_url : null,
                    'unit_type' => $unitType,
                    'unit_id' => $unitId,
                    'unit_name' => $unitName,
                    'product_type' => $product->type,
                    'quantity' => $quantity,
                    'price_per_unit' => $pricePerUnit,
                    'discount_amount' => $itemDiscount,
                    'tax_amount' => $itemTax,
                    'subtotal' => $itemSubtotal,
                    'total' => $itemTotal,
                ]);
            }

            $shippingAmount = 0;
            $taxAmount = $subtotal * 0.1;
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $shippingAmount;

            $order->subtotal = $subtotal;
            $order->discount_amount = $discountAmount;
            $order->tax_amount = $taxAmount;
            $order->shipping_amount = $shippingAmount;
            $order->total_amount = $totalAmount;
            $order->due_amount = $totalAmount;
            $order->save();

            $paymentData = [];
            if ($request->payment_method === 'credit_debit_card') {
                $paymentData = [
                    'card_number' => str_replace(' ', '', $request->card_number),
                    'expiry_date' => $request->expiry_date,
                    'cvv' => $request->cvv,
                    'cardholder_name' => $request->cardholder_name,
                ];
            }

            $paymentService = new PaymentService();
            $paymentResult = $paymentService->processPayment($order, $request->payment_method, $paymentData);

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message'] ?? 'Payment processing failed');
            }

            $order->status = 'confirmed';
            $order->confirmed_at = now();
            $order->save();

            // Mark cart as converted
            $cart->converted_to_order_id = $order->id;
            $cart->save();
            
            // Delete cart items to ensure cart appears empty
            $cart->items()->delete();
            
            // Delete any other unconverted carts for this user/session
            if ($loggedIn) {
                Cart::where('customer_id', $customerId)
                    ->where('id', '!=', $cart->id)
                    ->whereNull('converted_to_order_id')
                    ->delete();
            } else {
                Cart::where('session_id', $sessionId)
                    ->whereNull('customer_id')
                    ->where('id', '!=', $cart->id)
                    ->whereNull('converted_to_order_id')
                    ->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'redirect_url' => route('order.confirmation', ['order_number' => $order->order_number])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function orderConfirmation(Request $request, $order_number)
    {
        $order = Order::with([
            'items.product.primaryImage',
            'items.variant.variantImage',
            'customer',
            'location.country',
            'location.state',
            'location.city',
            'warehouse'
        ])->where('order_number', $order_number)->firstOrFail();

        $loggedIn = auth()->guard('customer')->check();
        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            if ($order->customer_id != $customerId) {
                abort(403, 'Unauthorized access to this order');
            }
        }

        $payment = \App\Models\Payment::where('order_id', $order->id)->latest()->first();

        return view('front.order-confirmation', compact('order', 'payment'));
    }

    public function downloadInvoice(Request $request, $order_number)
    {
        $order = Order::with([
            'items.product.primaryImage',
            'items.variant.variantImage',
            'customer',
            'location.country',
            'location.state',
            'location.city',
            'warehouse'
        ])->where('order_number', $order_number)->firstOrFail();

        $loggedIn = auth()->guard('customer')->check();
        if ($loggedIn) {
            $customerId = auth()->guard('customer')->id();
            if ($order->customer_id != $customerId) {
                abort(403, 'Unauthorized access to this order');
            }
        }

        $payment = \App\Models\Payment::where('order_id', $order->id)->latest()->first();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('front.invoice-pdf', compact('order', 'payment'));
        
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    public function orders(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return redirect()->route('login');
        }

        $customerId = auth()->guard('customer')->id();

        $query = Order::with(['items'])
            ->where('customer_id', $customerId)
            ->orderBy('order_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', '%' . $search . '%');
            });
        }

        $orders = $query->paginate(10)->withQueryString();

        $allOrders = Order::where('customer_id', $customerId)->get();

        $statistics = [
            'total_orders' => $allOrders->count(),
            'total_spent' => $allOrders->sum('total_amount'),
            'average_order' => $allOrders->count() > 0 ? $allOrders->sum('total_amount') / $allOrders->count() : 0,
            'in_transit' => $allOrders->whereIn('status', ['shipped', 'processing', 'packed'])->count(),
        ];

        return view('front.panel.orders', compact('orders', 'statistics'));
    }

    public function reorder(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Please login to reorder'
            ], 401);
        }

        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $customerId = auth()->guard('customer')->id();
        $order = Order::with('items.product', 'items.variant')->findOrFail($request->order_id);

        if ($order->customer_id != $customerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $sessionId = $request->session()->getId();
            $cart = Cart::where('customer_id', $customerId)
                ->whereNull('converted_to_order_id')
                ->latest('id')
                ->first();

            if (!$cart) {
                $cart = Cart::create([
                    'customer_id' => $customerId,
                    'session_id' => $sessionId,
                ]);
            }

            foreach ($order->items as $orderItem) {
                $product = $orderItem->product;
                $variant = $orderItem->variant;

                if (!$product) {
                    continue;
                }

                $existingItem = CartItem::where('cart_id', $cart->id)
                    ->where('product_id', $product->id)
                    ->where('product_variant_id', $variant ? $variant->id : null)
                    ->where('unit_type', $orderItem->unit_type)
                    ->where('unit_id', $orderItem->unit_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->quantity = (float) $existingItem->quantity + (float) $orderItem->quantity;
                    $existingItem->save();
                } else {
                    CartItem::create([
                        'cart_id' => $cart->id,
                        'product_id' => $product->id,
                        'product_variant_id' => $variant ? $variant->id : null,
                        'unit_type' => $orderItem->unit_type,
                        'unit_id' => $orderItem->unit_id,
                        'quantity' => $orderItem->quantity,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Items added to cart successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding items to cart: ' . $e->getMessage()
            ], 500);
        }
    }

    public function exportOrders(Request $request)
    {
        if (!auth()->guard('customer')->check()) {
            return redirect()->route('login');
        }

        $customerId = auth()->guard('customer')->id();

        $query = Order::with(['items', 'location.country', 'location.state', 'location.city'])
            ->where('customer_id', $customerId)
            ->orderBy('order_date', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->get();

        $filename = 'orders-export-' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'Order Number',
                'Order Date',
                'Status',
                'Payment Status',
                'Items Count',
                'Subtotal',
                'Discount',
                'Tax',
                'Shipping',
                'Total Amount',
                'Shipping Address',
                'Shipping City',
                'Shipping State',
                'Shipping Zipcode',
                'Shipping Phone',
                'Shipping Email'
            ]);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_number,
                    $order->order_date->format('Y-m-d'),
                    $order->status,
                    $order->payment_status,
                    $order->items->sum('quantity'),
                    $order->subtotal,
                    $order->discount_amount,
                    $order->tax_amount,
                    $order->shipping_amount,
                    $order->total_amount,
                    $order->shipping_address_line_1,
                    $order->shipping_city_id ? \App\Models\City::find($order->shipping_city_id)->name ?? '' : '',
                    $order->shipping_state_id ? \App\Models\State::find($order->shipping_state_id)->name ?? '' : '',
                    $order->shipping_zipcode,
                    $order->shipping_phone,
                    $order->shipping_email,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
