@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-css')
<style>
    .bg-soft-primary { background: #eef2ff; color: #4f46e5; }
    .bg-soft-info { background: #e0f2fe; color: #0369a1; }
    .btn-xs { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
    .bg-light-white { background-color: #fcfcfc; }
</style>
@endsection

@section('product-content')
<div class="row g-4">
    <div class="col-12 d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="fw-bold mb-0 text-primary">Final Review: {{ $product->name }}</h4>
            <p class="text-muted">Variable Product Wizard • Step 8 of 8</p>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-info-circle text-primary me-2"></i> Basic & SEO</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(1), 'id'=>encrypt($product->id)]) }}" class="btn btn-xs btn-light border">Edit</a>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted d-block">Brand</small>
                    <strong>{{ $product->brand->name ?? 'N/A' }}</strong>
                </div>
                <div class="mb-3">
                    <small class="text-muted d-block">SEO Title</small>
                    <span class="small text-truncate d-block">{{ $product->meta_title ?: 'Using Product Name' }}</span>
                </div>
                <div>
                    <small class="text-muted d-block">Primary Category</small>
                    <span class="badge bg-soft-primary text-primary">{{ $product->categories()->where('is_primary', 1)->first()->name ?? 'Not Set' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-th-large text-primary me-2"></i> Variant Inventory Health</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(2), 'id'=>encrypt($product->id)]) }}" class="btn btn-xs btn-light border">Edit</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead class="bg-light">
                        <tr style="font-size: 11px;">
                            <th class="ps-3">Variant (SKU)</th>
                            <th>Attributes</th>
                            <th>Total Stock</th>
                            <th>Pricing</th>
                            <th class="pe-3 text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants as $variant)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $variant->images->where('position', 0)->first() ? asset('storage/'.$variant->images->where('position', 0)->first()->image_path) : asset('assets/img/placeholder.png') }}" 
                                             class="rounded me-2" style="width: 25px; height: 25px; object-fit: cover;">
                                        <span class="small fw-bold">{{ $variant->sku }}</span>
                                    </div>
                                </td>
                                <td>
                                    @foreach($variant->attributes as $val)
                                        <span class="badge bg-light text-dark border" style="font-size: 9px;">{{ $val->value }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <span class="fw-bold {{ $variant->supplierWarehouseProducts->sum('quantity') > 0 ? 'text-dark' : 'text-danger' }}">
                                        {{ $variant->supplierWarehouseProducts->sum('quantity') }} Units
                                    </span>
                                </td>
                                <td>
                                    @if($variant->prices->whereNotNull('price_id')->count() > 0)
                                        <span class="text-success small"><i class="fa fa-check-circle"></i> Configured</span>
                                    @else
                                        <span class="text-danger small"><i class="fa fa-times-circle"></i> Missing Price</span>
                                    @endif
                                </td>
                                <td class="pe-3 text-end">
                                    <span class="badge {{ $variant->status == 'active' ? 'bg-success' : 'bg-warning' }}">{{ $variant->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-tags text-primary me-2"></i> Master Pricing & Unit Overview</span>
                <div class="btn-group">
                    <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(3), 'id'=>encrypt($product->id)]) }}" class="btn btn-xs btn-outline-secondary">Units</a>
                    <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(4), 'id'=>encrypt($product->id)]) }}" class="btn btn-xs btn-outline-secondary border-start-0">Pricing</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr class="small">
                            <th class="ps-3">Variant Name</th>
                            <th>Unit Name</th>
                            <th>Factor</th>
                            <th>Base Price</th>
                            <th>Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->variants as $variant)
                            @foreach($variant->units as $uIndex => $pUnit)
                                <tr class="{{ $uIndex == 0 ? 'border-top' : '' }}">
                                    @if($uIndex == 0)
                                        <td rowspan="{{ $variant->units->count() }}" class="ps-3 align-middle bg-light-white">
                                            <div class="fw-bold small">{{ $variant->name }}</div>
                                        </td>
                                    @endif
                                    <td>{{ $pUnit->unit->name }} {!! $pUnit->is_base ? '<span class="badge bg-dark" style="font-size:8px">BASE</span>' : '' !!}</td>
                                    <td>x{{ number_format($pUnit->conversion_factor, 2) }}</td>
                                    <td class="fw-bold text-primary">${{ number_format($pUnit->price->base_price ?? 0, 2) }}</td>
                                    <td>
                                        <span class="text-capitalize small badge bg-soft-info text-info">
                                            {{ $pUnit->price->pricing_type ?? 'Fixed' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('product-js')
<script>

</script>
@endpush