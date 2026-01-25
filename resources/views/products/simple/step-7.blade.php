@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
<div class="row g-4">
    <div class="col-12 d-flex justify-content-between align-items-center mb-2">
        <div>
            <h4 class="fw-bold mb-0">Final Review & Publish</h4>
            <p class="text-muted">Double-check all configurations before taking the product live.</p>
        </div>
        <div class="d-flex gap-2">
            <div class="form-check form-switch">
                <input class="form-check-input" name="status" type="checkbox" role="switch" id="flexSwitchCheckChecked" value="1" @if ($product->status == 'active') checked @endif>
                <label class="form-check-label" for="flexSwitchCheckChecked">Active</label>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-info-circle text-primary me-2"></i> Basic Information</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(1), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ asset('storage/'.$product->images->where('position', 0)->first()->image_path ?? 'default.png') }}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                    <div>
                        <div class="fw-bold text-dark">{{ $product->name }}</div>
                        <div class="small text-muted">SKU: {{ $product->variants->first()->sku ?? 'N/A' }}</div>
                    </div>
                </div>
                <div class="row small text-muted">
                    <div class="col-6">Brand: <strong class="text-dark">{{ $product->brand->name ?? 'N/A' }}</strong></div>
                    <div class="col-6 text-end">Status: 
                        @if( $product->status == 'active' )
                            <span class="badge bg-success text-white text-capitalize">Active</span>
                        @else
                            <span class="badge bg-secondary text-white text-capitalize">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-boxes text-primary me-2"></i> Pack Sizes / Hierarchy</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(2), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="card-body">
                @foreach($product->units->sortBy('conversion_factor') as $pUnit)
                    <div class="d-flex justify-content-between mb-1 small">
                        <span>{{ $pUnit->unit->name }}</span>
                        <span class="text-muted">x{{ number_format($pUnit->conversion_factor, 0) }} Base Units</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-tags text-primary me-2"></i> Pricing Overview</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(3), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-3">Unit</th>
                            <th>Mode</th>
                            <th>Base Price</th>
                            <th>Best Price (Max Tier)</th>
                            <th class="pe-3 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->units as $pUnit)
                            <tr>
                                <td class="ps-3 fw-bold">{{ $pUnit->unit->name }}</td>
                                <td><span class="badge bg-info-light text-white text-capitalize">{{ $pUnit->price->pricing_type ?? 'N/A' }}</span></td>
                                <td>${{ number_format($pUnit->price->base_price ?? 0, 2) }}</td>
                                <td>${{ number_format($pUnit->price->tiers->min('price') ?? ($pUnit->price->base_price ?? 0), 2) }}</td>
                                <td class="pe-3 text-end"><i class="fa fa-check-circle text-success"></i></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-warehouse text-primary me-2"></i> Inventory & Supplier Map</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(4), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($product->supplierWarehouseProducts as $inv)
                        <div class="col-md-6">
                            <div class="p-2 border rounded bg-light small">
                                <strong>{{ $inv->warehouse->name }}</strong><br>
                                <span class="text-muted">Supplier: {{ $inv->supplier->name }}</span><br>
                                <span class="fw-bold text-primary">{{ $inv->quantity }} {{ $inv->unit->name }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-search text-primary me-2"></i> SEO Status</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(5), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="card-body p-3">
                <div class="small fw-bold text-truncate">{{ $product->meta_title ?: $product->name }}</div>
                <div class="small text-muted text-truncate mt-1">{{ $product->meta_content ?: 'No meta description set.' }}</div>
            </div>
        </div>
        
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <span class="fw-bold"><i class="fa fa-link text-primary me-2"></i> Substitutes</span>
                <a href="{{ route('product-management', ['type'=>encrypt($type), 'step'=>encrypt(6), 'id'=>encrypt($product->id)]) }}" class="btn btn-sm btn-light border">Edit</a>
            </div>
            <div class="card-body py-2">
                <div class="small fw-bold text-primary">{{ $product->substitutes->count() }} Linked Alternatives</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('product-js')
    <script>
        $(document).ready(function() {
            $('.select2-ajax-variants').select2({
                width: '100%',
                placeholder: $(this).data('placeholder'),
                closeOnSelect: false,
                allowClear: true,
                ajax: {
                    url: '{{ route('product-management', ['type' => encrypt($type), 'step' => encrypt(7), 'id' => encrypt($product->id)]) }}',
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            _token: '{{ csrf_token() }}',
                            op: 'search-variants',
                            term: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                }
            });
        });
    </script>
@endpush