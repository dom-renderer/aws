@foreach($categories as $category)
    <div class="category-item ms-{{ $category->parent_id ? '3' : '0' }} mt-1">
        <div class="form-check">
            <input class="form-check-input primary-cat-radio" 
                   type="radio" 
                   name="primary_category_id" 
                   id="primary-{{ $category->id }}" 
                   value="{{ $category->id }}"
                   {{ $primaryId == $category->id ? 'checked' : '' }} 
                   required>
            
            <label class="form-check-label d-flex align-items-center" for="primary-{{ $category->id }}">
                @if($category->children->count() > 0)
                    <i class="fa fa-chevron-right me-2 text-muted small toggle-children" style="cursor: pointer;"></i>
                @else
                    <i class="fa fa-minus me-2 text-muted small" style="opacity: 0.3;"></i>
                @endif
                
                @if($category->logo)
                    <img src="{{ asset('storage/' . $category->logo) }}" class="rounded me-2" style="width: 20px; height: 20px; object-fit: cover;">
                @endif
                
                {{ $category->name }}
            </label>
        </div>

        @if($category->children->count() > 0)
            <div class="children-wrapper" style="display: none;">
                @include('products.category-tree', [
                    'categories' => $category->children, 
                    'primaryId' => $primaryId
                ])
            </div>
        @endif
    </div>
@endforeach