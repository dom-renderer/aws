@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
    .price-card {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
    }
    .price-summary {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
    }
    .muted-small { color:#6c757d; font-size: 13px; }
</style>
@endpush

@section('product-content')
@php
    $bundle = $bundle ?? $product->bundle;
    $bundleItems = $bundle?->items ?? collect();
    $pricingMode = old('pricing_mode', $bundle?->pricing_mode ?? 'fixed');
    $discountType = old('discount_type', $bundle?->discount_type ?? 'percentage');
    $discountValue = old('discount_value', $bundle?->discount_value ?? 0);
@endphp

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold">Step 3: Pricing Configuration</h5>
    </div>
    <div class="card-body">
        @if($bundleItems->count() < 1)
            <div class="alert alert-warning mb-0">
                Please add bundle items in Step 2 before configuring pricing.
            </div>
        @else
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="price-card">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Pricing Mode <span class="text-danger">*</span></label>
                            <select name="pricing_mode" id="pricing_mode" class="form-control select2" required>
                                <option value="fixed" {{ $pricingMode === 'fixed' ? 'selected' : '' }}>Fixed (Sum of items)</option>
                                <option value="sum_discount" {{ $pricingMode === 'sum_discount' ? 'selected' : '' }}>Discount-based (Sum - Discount)</option>
                            </select>
                            <div class="muted-small mt-1">“Fixed” uses the sum of item prices; “Discount-based” applies a discount over the sum.</div>
                        </div>

                        <div id="discount-section" class="{{ $pricingMode === 'sum_discount' ? '' : 'd-none' }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Discount Type <span class="text-danger">*</span></label>
                                    <select name="discount_type" id="discount_type" class="form-control select2">
                                        <option value="percentage" {{ $discountType === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                                        <option value="fixed" {{ $discountType === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Discount Value <span class="text-danger">*</span></label>
                                    <input type="number" name="discount_value" id="discount_value" class="form-control" step="0.01" min="0" value="{{ $discountValue }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="price-summary">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="fw-bold">Bundle Pricing Summary</div>
                            <div class="muted-small">Live</div>
                        </div>
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <div>Subtotal (Sum of items)</div>
                                <div class="fw-bold">$<span id="bundle-subtotal">0.00</span></div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <div>Discount</div>
                                <div class="fw-bold text-danger">- $<span id="bundle-discount-amount">0.00</span></div>
                            </div>
                            <div class="d-flex justify-content-between border-top pt-2">
                                <div class="fw-bold">Total Bundle Price</div>
                                <div class="fw-bold text-primary">$<span id="bundle-total">0.00</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div class="fw-bold">Bundle Items (for pricing)</div>
                        <div class="muted-small">Unit prices are derived from the selected unit pricing rules</div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th width="120" class="text-end">Qty</th>
                                        <th width="160" class="text-end">Unit Price</th>
                                        <th width="180" class="text-end">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody id="bundle-price-lines">
                                    @foreach($bundleItems as $item)
                                        <tr class="bundle-line"
                                            data-product-id="{{ (int)$item->product_id }}"
                                            data-variant-id="{{ $item->variant_id ? (int)$item->variant_id : '' }}"
                                            data-unit-id="{{ (int)$item->unit_id }}"
                                            data-qty="{{ (int)$item->quantity }}">
                                            <td>
                                                <div class="fw-bold">{{ $item->product?->name ?? 'Product' }}</div>
                                                <div class="muted-small">
                                                    @if($item->variant_id)
                                                        Variant: {{ $item->variant?->name ?? 'Variant' }}
                                                    @else
                                                        Simple product
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-end">{{ (int)$item->quantity }}</td>
                                            <td class="text-end">$<span class="unit-price">0.00</span></td>
                                            <td class="text-end fw-bold">$<span class="line-total">0.00</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@endsection

@push('product-js')
<script>
$(document).ready(function() {
    $('.select2').select2({ width: '100%' });

    function money(v) {
        const n = (parseFloat(v) || 0);
        return n.toFixed(2);
    }

    function computeTotals() {
        let subtotal = 0;
        $('#bundle-price-lines .bundle-line').each(function() {
            const lineTotal = parseFloat($(this).find('.line-total').text()) || 0;
            subtotal += lineTotal;
        });

        const mode = $('#pricing_mode').val();
        const discType = $('#discount_type').val();
        const discVal = parseFloat($('#discount_value').val()) || 0;

        let discountAmount = 0;
        if (mode === 'sum_discount') {
            if (discType === 'percentage') {
                discountAmount = subtotal * (Math.min(Math.max(discVal, 0), 100) / 100);
            } else {
                discountAmount = Math.min(Math.max(discVal, 0), subtotal);
            }
        }

        const total = Math.max(subtotal - discountAmount, 0);

        $('#bundle-subtotal').text(money(subtotal));
        $('#bundle-discount-amount').text(money(discountAmount));
        $('#bundle-total').text(money(total));
    }

    async function loadLinePrices() {
        const token = $('meta[name="csrf-token"]').attr('content');

        const requests = [];
        $('#bundle-price-lines .bundle-line').each(function() {
            const $line = $(this);
            const payload = {
                product_id: $line.data('product-id'),
                variant_id: $line.data('variant-id') || null,
                unit_id: $line.data('unit-id'),
                quantity: $line.data('qty'),
            };

            requests.push(
                $.ajax({
                    url: "{{ route('bundle-products.item-price') }}",
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': token },
                    data: payload,
                }).then(function(res) {
                    $line.find('.unit-price').text(money(res.unit_price));
                    $line.find('.line-total').text(money(res.line_total));
                }).catch(function() {
                    $line.find('.unit-price').text('0.00');
                    $line.find('.line-total').text('0.00');
                })
            );
        });

        await Promise.all(requests);
        computeTotals();
    }

    $('#pricing_mode').on('change', function() {
        const isDiscount = $(this).val() === 'sum_discount';
        $('#discount-section').toggleClass('d-none', !isDiscount);
        computeTotals();
    });

    $('#discount_type, #discount_value').on('change keyup', function() {
        computeTotals();
    });

    loadLinePrices();
});

</script>
@endpush