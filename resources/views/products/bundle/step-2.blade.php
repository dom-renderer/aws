@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@push('product-css')
<style>
    .bundle-item-row {
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: 10px;
        padding: 16px;
        margin-bottom: 12px;
        position: relative;
    }
    .bundle-item-row .remove-item {
        position: absolute;
        right: 12px;
        top: 12px;
    }
    .bundle-help {
        font-size: 13px;
        color: #6c757d;
    }
</style>
@endpush

@section('product-content')
@php
    $existingItems = $bundle?->items ?? collect();
@endphp

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0 fw-bold">Step 2: Bundle Item Selection</h5>
            <div class="bundle-help mt-1">Add one or more products. Variable products require selecting a specific variant.</div>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="add-bundle-item">
            <i class="fa fa-plus me-1"></i> Add Item
        </button>
    </div>
    <div class="card-body">
        <div id="bundle-duplicate-alert" class="alert alert-danger d-none"></div>

        <div id="bundle-items-wrapper">
            @forelse($existingItems as $i => $item)
                <div class="bundle-item-row" data-index="{{ $i }}">
                    <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                        <i class="fa fa-trash"></i>
                    </button>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Product <span class="text-danger">*</span></label>
                            <select name="bundle_items[{{ $i }}][product_id]" class="form-control select2 bundle-product" required>
                                <option value="{{ $item->product_id }}" selected>
                                    {{ $item->product?->name ?? 'Selected Product' }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3 variant-col {{ $item->variant_id ? '' : 'd-none' }}">
                            <label class="form-label fw-bold">Variant <span class="text-danger">*</span></label>
                            <select name="bundle_items[{{ $i }}][variant_id]" class="form-control select2 bundle-variant">
                                @if($item->variant_id)
                                    <option value="{{ $item->variant_id }}" selected>
                                        {{ $item->variant?->name ?? 'Selected Variant' }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Unit <span class="text-danger">*</span></label>
                            <select name="bundle_items[{{ $i }}][unit_id]" class="form-control select2 bundle-unit" required>
                                @php $unitName = $units->firstWhere('id', $item->unit_id)?->name; @endphp
                                <option value="{{ $item->unit_id }}" selected>{{ $unitName ?? 'Selected Unit' }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="bundle_items[{{ $i }}][quantity]" class="form-control bundle-qty" min="1" value="{{ $item->quantity }}" required>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-muted">No bundle items yet. Click <strong>Add Item</strong> to start building this bundle.</div>
            @endforelse
        </div>
    </div>
</div>

@endsection

@push('product-js')
<script>
$(document).ready(function() {
    const excludeId = {{ (int) $product->id }};

    function initSelect2() {
        $('.select2').select2({ width: '100%' });
    }

    function buildEmptyRow(index) {
        return `
            <div class="bundle-item-row" data-index="${index}">
                <button type="button" class="btn btn-outline-danger btn-sm remove-item">
                    <i class="fa fa-trash"></i>
                </button>
                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Product <span class="text-danger">*</span></label>
                        <select name="bundle_items[${index}][product_id]" class="form-control select2 bundle-product" required></select>
                    </div>
                    <div class="col-md-3 variant-col d-none">
                        <label class="form-label fw-bold">Variant <span class="text-danger">*</span></label>
                        <select name="bundle_items[${index}][variant_id]" class="form-control select2 bundle-variant"></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Unit <span class="text-danger">*</span></label>
                        <select name="bundle_items[${index}][unit_id]" class="form-control select2 bundle-unit" required></select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="bundle_items[${index}][quantity]" class="form-control bundle-qty" min="1" value="1" required>
                    </div>
                </div>
            </div>
        `;
    }

    function initProductSelect($el) {
        $el.select2({
            width: '100%',
            placeholder: 'Search product...',
            ajax: {
                url: "{{ route('bundle-products.search') }}",
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { q: params.term, exclude: excludeId };
                },
                processResults: function(data) {
                    return data;
                }
            }
        });
    }

    function setVariants($row, variants) {
        const $variantCol = $row.find('.variant-col');
        const $variant = $row.find('.bundle-variant');
        $variant.empty().trigger('change');

        if (!variants || variants.length === 0) {
            $variantCol.addClass('d-none');
            $variant.prop('required', false);
            $variant.val(null).trigger('change');
            return;
        }

        $variantCol.removeClass('d-none');
        $variant.prop('required', true);
        $variant.append('<option value="">Select Variant</option>');
        variants.forEach(v => $variant.append(`<option value="${v.id}">${v.text}</option>`));
        $variant.trigger('change');
    }

    function setUnits($row, units) {
        const $unit = $row.find('.bundle-unit');
        $unit.empty().trigger('change');
        $unit.append('<option value="">Select Unit</option>');
        (units || []).forEach(u => {
            const badge = u.is_default_selling ? ' (Default)' : (u.is_base ? ' (Base)' : '');
            $unit.append(`<option value="${u.id}">${u.text}${badge}</option>`);
        });
        $unit.trigger('change');
    }

    async function loadVariants(productId) {
        const res = await fetch(`{{ url('admin/bundle-products') }}/${productId}/variants`);
        return await res.json();
    }

    async function loadUnits(productId, variantId) {
        const params = variantId ? `?variant_id=${variantId}` : '';
        const res = await fetch(`{{ url('admin/bundle-products') }}/${productId}/units${params}`);
        return await res.json();
    }

    function showDuplicateError(message) {
        $('#bundle-duplicate-alert').removeClass('d-none').text(message);
    }

    function hideDuplicateError() {
        $('#bundle-duplicate-alert').addClass('d-none').text('');
    }

    function validateDuplicates() {
        hideDuplicateError();
        let seen = {};
        let hasDup = false;

        $('#bundle-items-wrapper .bundle-item-row').each(function() {
            const $row = $(this);
            const productId = $row.find('.bundle-product').val();
            const variantVisible = !$row.find('.variant-col').hasClass('d-none');
            const variantId = variantVisible ? ($row.find('.bundle-variant').val() || 'null') : 'null';

            if (!productId) return;

            const key = `${productId}:${variantId}`;
            if (seen[key]) {
                hasDup = true;
            } else {
                seen[key] = true;
            }
        });

        if (hasDup) {
            showDuplicateError('Duplicate product/variant combination detected. Please ensure each product/variant appears only once.');
        }
        return !hasDup;
    }

    // Init existing rows
    initSelect2();
    $('#bundle-items-wrapper .bundle-item-row').each(function() {
        initProductSelect($(this).find('.bundle-product'));
    });

    $('#add-bundle-item').on('click', function() {
        const index = $('#bundle-items-wrapper .bundle-item-row').length;
        $('#bundle-items-wrapper').append(buildEmptyRow(index));
        const $newRow = $('#bundle-items-wrapper .bundle-item-row').last();
        initSelect2();
        initProductSelect($newRow.find('.bundle-product'));
    });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('.bundle-item-row').remove();
        validateDuplicates();
    });

    $(document).on('change', '.bundle-product', async function() {
        const $row = $(this).closest('.bundle-item-row');
        const productId = $(this).val();

        $row.find('.bundle-variant').val(null).trigger('change');
        $row.find('.bundle-unit').val(null).trigger('change');

        if (!productId) {
            setVariants($row, []);
            setUnits($row, []);
            validateDuplicates();
            return;
        }

        try {
            const variantsRes = await loadVariants(productId);
            setVariants($row, variantsRes.results || []);

            if (!variantsRes.results || variantsRes.results.length === 0) {
                const unitsRes = await loadUnits(productId, null);
                setUnits($row, unitsRes.results || []);
            }
        } catch (e) {
            console.error(e);
            setVariants($row, []);
            setUnits($row, []);
        }

        validateDuplicates();
    });

    $(document).on('change', '.bundle-variant', async function() {
        const $row = $(this).closest('.bundle-item-row');
        const productId = $row.find('.bundle-product').val();
        const variantId = $(this).val();

        $row.find('.bundle-unit').val(null).trigger('change');
        if (!productId || !variantId) {
            setUnits($row, []);
            validateDuplicates();
            return;
        }

        try {
            const unitsRes = await loadUnits(productId, variantId);
            setUnits($row, unitsRes.results || []);
        } catch (e) {
            console.error(e);
            setUnits($row, []);
        }

        validateDuplicates();
    });

    $(document).on('change keyup', '.bundle-unit, .bundle-qty', function() {
        validateDuplicates();
    });

    $('#productForm').on('submit', function(e) {
        if (!validateDuplicates()) {
            e.preventDefault();
            return false;
        }

        if ($('#bundle-items-wrapper .bundle-item-row').length < 1) {
            e.preventDefault();
            alert('Please add at least one bundle item.');
            return false;
        }
        return true;
    });
});

</script>
@endpush