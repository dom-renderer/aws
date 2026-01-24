@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
<div class="row">
    <div class="col-md-7">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Primary Category <span class="text-danger">*</span></h6>
            </div>
            <div class="card-body">
                <div class="category-tree-search mb-3">
                    <input type="text" id="catSearch" class="form-control" placeholder="Search categories...">
                </div>
                <div class="category-tree-container border rounded p-3" style="max-height: 350px; overflow-y: auto;">
                    @include('products.category-tree', [
                        'categories' => $allCategories, 
                        'primaryId' => $currentPrimaryId
                    ])
                </div>
                <small class="text-muted mt-2 d-block">Select the most specific category that applies to your product.</small>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold">Additional Categories</h6>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($allCategoriesAddtionalList as $cat)
                        <div class="col-md-6">
                            <div class="form-check border rounded p-2 px-3">
                                <input class="form-check-input" type="checkbox" name="additional_categories[]" 
                                       value="{{ $cat->id }}" id="cat-{{ $cat->id }}"
                                       {{ in_array($cat->id, $currentAdditionalIds) ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="cat-{{ $cat->id }}">
                                    {{ $cat->name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5">
        <div class="card shadow-sm border-primary border-top border-4">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-bold"><i class="fa fa-search text-primary me-2"></i> SEO Settings</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-bold">Meta Title</label>
                    <input type="text" name="meta_title" class="form-control" 
                           placeholder="Custom SEO title" value="{{ $product->meta_title }}">
                    <small class="text-muted">Leave blank to use product name.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Meta Description</label>
                    <textarea name="meta_description" class="form-control" rows="5" 
                              placeholder="SEO description for search engines">{{ $product->meta_description }}</textarea>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-muted">Recommended: 150-160 characters</small>
                        <small id="charCount" class="fw-bold">0</small>
                    </div>
                </div>
                
                <div class="seo-preview bg-light p-3 border rounded">
                    <div class="text-primary fs-5 mb-1 preview-title" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $product->meta_title ?: $product->name }}
                    </div>
                    <div class="text-success small mb-1">{{ url('/product/' . $product->slug) }}</div>
                    <div class="text-muted small preview-desc">
                        {{ $product->meta_content ?: 'No description provided.' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('product-js')
<script>
$(document).ready(function() {
    $('input[name="meta_title"], textarea[name="meta_description"]').on('input', function() {
        let title = $('input[name="meta_title"]').val() || '{{ $product->name }}';
        let desc = $('textarea[name="meta_description"]').val() || 'No description provided.';
        
        $('.preview-title').text(title);
        $('.preview-desc').text(desc);
        $('#charCount').text(desc.length);
    });

    $('#catSearch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('.category-tree-container .form-check').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    $(document).on('change', 'input[name="primary_category_id"]', function() {
        let selectedId = $(this).val();
        $(`#cat-${selectedId}`).prop('checked', false);
    });

    $(document).on('click', '.toggle-children', function(e) {
        e.preventDefault();
        $(this).toggleClass('fa-chevron-right fa-chevron-down');
        $(this).closest('.category-item').find('.children-wrapper').first().slideToggle(200);
    });

    $(document).ready(function() {
        $('.primary-cat-radio:checked').parents('.children-wrapper').show();
        $('.primary-cat-radio:checked').parents('.category-item').find('> .form-check .toggle-children').addClass('fa-chevron-down').removeClass('fa-chevron-right');
    });
});
</script>
@endpush