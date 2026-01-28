@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
    .info-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        margin-bottom: 20px;
        height: 100%;
    }

    .info-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #e9ecef;
    }

    .info-card-title {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        color: #333;
    }

    .muted-small { color:#6c757d; font-size: 13px; }
</style>
@endpush

@section('product-content')
@php
    $bundle = $bundle ?? $product->bundle;
    $bundleItems = $bundle?->items ?? collect();
@endphp

<div class="container-fluid">
    <div class="row">
        <div class="col-md-4">
            <div class="info-card">
                <div class="info-card-header">
                    <h4 class="info-card-title">Basic Information</h4>
                    <a href="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(1), 'id' => encrypt($product->id)]) }}" class="btn btn-primary btn-sm">Edit</a>
                </div>

                <div class="mb-2"><strong>Name:</strong> {{ $product->name }}</div>
                <div class="mb-2"><strong>Brand:</strong> {{ $product->brand->name ?? 'N/A' }}</div>
                <div class="mb-2"><strong>Type:</strong> {{ ucfirst($product->product_type) }}</div>
                <div class="mb-0"><strong>Status:</strong> {{ ucfirst($product->status) }}</div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="info-card">
                <div class="info-card-header">
                    <h4 class="info-card-title">Bundle Items</h4>
                    <a href="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(2), 'id' => encrypt($product->id)]) }}" class="btn btn-primary btn-sm">Edit</a>
                </div>

                @if($bundleItems->count() < 1)
                    <div class="alert alert-warning mb-0">No bundle items added.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th width="140">Variant</th>
                                    <th width="140">Unit</th>
                                    <th width="120" class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bundleItems as $item)
                                    <tr>
                                        <td class="fw-bold">{{ $item->product?->name ?? 'Product' }}</td>
                                        <td>{{ $item->variant_id ? ($item->variant?->name ?? 'Variant') : '—' }}</td>
                                        <td>{{ $units->firstWhere('id', $item->unit_id)?->name ?? 'Unit' }}</td>
                                        <td class="text-end">{{ (int)$item->quantity }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="info-card">
                <div class="info-card-header">
                    <h4 class="info-card-title">Pricing Configuration</h4>
                    <a href="{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(3), 'id' => encrypt($product->id)]) }}" class="btn btn-primary btn-sm">Edit</a>
                </div>

                @if(!$bundle)
                    <div class="alert alert-warning mb-0">No pricing configuration saved yet.</div>
                @else
                    <div class="mb-2"><strong>Pricing Mode:</strong> {{ $bundle->pricing_mode === 'sum_discount' ? 'Discount-based' : 'Fixed (Sum of items)' }}</div>
                    @if($bundle->pricing_mode === 'sum_discount')
                        <div class="mb-2"><strong>Discount Type:</strong> {{ ucfirst($bundle->discount_type ?? '-') }}</div>
                        <div class="mb-0"><strong>Discount Value:</strong> {{ number_format((float)($bundle->discount_value ?? 0), 2) }}</div>
                    @else
                        <div class="muted-small mb-0">Total bundle price is computed as the sum of bundle items.</div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-md-4">
            <div class="info-card">
                <div class="info-card-header">
                    <h4 class="info-card-title">Finalize</h4>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input" name="status" type="checkbox" role="switch" id="bundleStatus" value="1" @if ($product->status == 'active') checked @endif>
                    <label class="form-check-label" for="bundleStatus">Active</label>
                </div>
                <div class="muted-small mt-2">
                    Saving this step will publish the bundle product (or keep it inactive if toggle is off).
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('product-js')
<script>

</script>
@endpush