@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h4>Unit Hierarchy / Pack Sizes</h4>
        <button type="button" class="btn btn-primary btn-sm" id="add-unit-btn">+ Add Unit</button>
    </div>
    <div class="card-body">
        <div class="base-unit-container border p-3 mb-3 bg-light rounded">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="fw-bold">Base Unit <span class="text-danger">*</span></label>
                    <select name="base_unit_id" id="base_unit_id" class="form-control select2-unit" required>
                        <option value="">Select Base Unit</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" data-name="{{ $unit->name }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Smallest unit of measure</small>
                </div>
                <div class="col-md-4 text-center">
                    <div class="form-check form-switch d-inline-block">
                        <input class="form-check-input" type="radio" name="default_selling_unit" value="base" checked>
                        <label class="form-check-label">Default Selling Unit</label>
                    </div>
                </div>
            </div>
        </div>

        <div id="additional-units-wrapper">
            </div>
        
        <div id="unit-validation-error" class="alert alert-danger d-none mt-2"></div>
    </div>
</div>
@endsection

@push('product-css')

@endpush

@push('product-js')
<script>
$(document).ready(function() {
    let unitsData = @json($units);
    
    $('#add-unit-btn').on('click', function() {
        const rowCount = $('.unit-row').length;
        const prevUnitName = rowCount === 0 
            ? $('#base_unit_id option:selected').data('name') 
            : $('.unit-row').last().find('.unit-select option:selected').data('name');

        if (!$('#base_unit_id').val()) {
            alert("Please select a Base Unit first.");
            return;
        }

        const html = `
            <div class="unit-row border p-3 mb-2 rounded position-relative" data-index="${rowCount}">
                <button type="button" class="btn btn-danger btn-sm position-absolute end-0 top-0 m-2 remove-row"><i class="fa fa-trash"></i></button>
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label>Unit Name</label>
                        <select name="units[${rowCount}][unit_id]" class="form-control select2-unit unit-select" required>
                            <option value="">Select Unit</option>
                            ${unitsData.map(u => `<option value="${u.id}" data-name="${u.name}">${u.name}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Quantity</label>
                        <input type="number" name="units[${rowCount}][quantity]" class="form-control unit-qty" min="1" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label>Per Parent Unit</label>
                        <input type="text" class="form-control bg-light parent-unit-display" value="${prevUnitName}" readonly>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="radio" name="default_selling_unit" value="${rowCount}">
                            <label class="form-check-label">Default Selling</label>
                        </div>
                    </div>
                </div>
                <div class="mt-2 small text-primary hierarchy-summary">
                    1 <span class="curr-name">?</span> = <span class="curr-qty">1</span> ${prevUnitName}
                </div>
            </div>
        `;
        $('#additional-units-wrapper').append(html);
        $('.select2-unit').select2();
    });

    // Validation: Prevent duplicate units in the chain
    $(document).on('change', '.select2-unit', function() {
        let selectedValues = $('.select2-unit').map(function() { return $(this).val(); }).get().filter(v => v !== "");
        let hasDuplicates = new Set(selectedValues).size !== selectedValues.length;
        
        if (hasDuplicates) {
            alert("This unit is already used in the hierarchy. Please select a unique unit.");
            $(this).val(null).trigger('change');
        }
        
        // Update summary text
        let row = $(this).closest('.unit-row');
        row.find('.curr-name').text($(this).find('option:selected').data('name') || '?');
    });

    $(document).on('click', '.remove-row', function() {
        $(this).closest('.unit-row').remove();
        // Re-calculate the "Per Parent" labels for all subsequent rows
        updateParentLabels();
    });

    function updateParentLabels() {
        let currentParent = $('#base_unit_id option:selected').data('name');
        $('.unit-row').each(function() {
            $(this).find('.parent-unit-display').val(currentParent);
            $(this).find('.hierarchy-summary').find('span:last').text(currentParent);
            currentParent = $(this).find('.unit-select option:selected').data('name') || '?';
        });
    }
});
</script>
@endpush