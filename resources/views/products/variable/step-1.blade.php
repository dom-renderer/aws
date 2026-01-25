@extends('products.layout', ['step' => $step, 'type' => $type, 'product' => $product])

@section('product-content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><h4>Product Basics</h4></div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" class="form-control select2" required>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ $product->brand_id == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Tags</label>
                                <select name="tags[]" class="form-control select2-tags" multiple="multiple">
                                    @foreach($allTags as $tag)
                                        <option value="{{ $tag->name }}" {{ in_array($tag->id, $productTagIds) ? 'selected' : '' }}>{{ $tag->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <div class="mb-3">
                                <label class="form-label">Product Type <span class="text-danger">*</span></label>
                                <div class="">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type_switch" id="typeSimple" value="simple" {{ $product->product_type === 'simple' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeSimple">Simple - Single product with no variations </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type_switch" id="typeVariable" value="variable" {{ $product->product_type === 'variable' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeVariable">Variable - Product with attributes & variants</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="type_switch" id="typeBundled" value="bundle" {{ $product->product_type === 'bundled' ? 'checked' : '' }}>
                                        <label class="form-check-label" for="typeBundled">Bundled - Multiple products sold together</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Short Description (Min 100 chars) <span class="text-danger">*</span></label>
                            <div class="summernote-wrapper">
                                <textarea name="short_description" id="short_desc" class="summernote">
                                    {{ old('short_description', $product->short_description) }}
                                </textarea>
                                <span class="summernote-error"></span>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label>Long Description (Min 200 chars) <span class="text-danger">*</span></label>

                            <div class="summernote-wrapper">
                                <textarea name="long_description" id="long_desc" class="summernote">
                                    {{ old('long_description', $product->long_description) }}
                                </textarea>
                                <span class="summernote-error"></span>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <label>Product Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" name="status" type="checkbox" role="switch" id="flexSwitchCheckChecked" value="1" @if ($product->status == 'active') checked @endif>
                                <label class="form-check-label" for="flexSwitchCheckChecked">Active</label>
                            </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">

                <div class="card mb-3">
                    <div class="card-header">Main Image (800x800)</div>
                    <div class="card-body">
                        <input type="file" name="main_image" id="mainImage" @if($mainImage) data-default-file="{{ asset('storage/' . $mainImage->image_path) }}" @endif>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Gallery (Max 5 Media)</div>
                    <div class="card-body">
                        <input type="file" name="secondary_media[]" id="galleryMedia" multiple>
                        <input type="hidden" name="existing_gallery_ids" id="existing_gallery_ids">
                    </div>
                </div>

            </div>
        </div>
</div>
@endsection

@push('product-js')
<script>
    $(document).ready(function() {
        $('.select2').select2();
        $('.select2-tags').select2({ tags: true, tokenSeparators: [',', ' '] });

        $('.summernote').summernote({
            height: 200,
            placeholder: 'Write here...',
            toolbar: [
                ['style', ['bold', 'italic', 'underline']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture']],
                ['view', ['codeview']]
            ]
        });

        const mainPond = FilePond.create(document.querySelector('#mainImage'), {
            instantUpload: false,
            storeAsFile: true,
            acceptedFileTypes: ['image/png', 'image/jpeg', 'image/webp'],
            maxFileSize: '3MB',
            files: [
                @if($mainImage)
                {
                    source: '{{ asset("storage/" . $mainImage->image_path) }}',
                    options: { 
                        type: 'local',
                        metadata: { poster: '{{ asset("storage/" . $mainImage->image_path) }}' } 
                    }
                }
                @endif
            ],
            server: {
                load: (source, load, error, progress, abort, headers) => {
                    fetch(source).then(res => res.blob()).then(load);
                }
            }
        });

        const galleryPond = FilePond.create(document.querySelector('#galleryMedia'), {
            instantUpload: false,
            storeAsFile: true,
            allowMultiple: true,
            maxFiles: 5,
            allowReorder: true,
            maxFileSize: '5MB',
            files: [
                @foreach($gallery as $img)
                {
                    source: '{{ asset("storage/" . $img->image_path) }}',
                    options: { 
                        type: 'local',
                        metadata: { id: '{{ $img->id }}' }
                    }
                },
                @endforeach
            ],
            server: {
                load: (source, load, error, progress, abort, headers) => {
                    fetch(source).then(res => res.blob()).then(load);
                }
            }
        });

        $('#productForm').on('submit', function() {
            const existingIds = galleryPond.getFiles().map(f => f.getMetadata('id'));
            $('#existing_gallery_ids').val(JSON.stringify(existingIds));
        });

        function getSummernoteTextLength(selector) {
            let content = $(selector).summernote('code');
            return $('<div>').html(content).text().trim().length;
        }

        $.validator.addMethod('minSummernote', function (value, element, params) {
            return getSummernoteTextLength(element) >= params;
        });

        $('#productForm').validate({
            ignore: [],
            rules: {
                short_description: {
                    required: true,
                    minSummernote: 100
                },
                long_description: {
                    required: true,
                    minSummernote: 200
                }
            },
            messages: {
                short_description: {
                    required: 'Short Description is required.',
                    minSummernote: 'Short Description must be at least 100 characters.'
                },
                long_description: {
                    required: 'Long Description is required.',
                    minSummernote: 'Long Description must be at least 200 characters.'
                }
            },
            errorElement: 'span',
            errorClass: 'text-danger d-block mt-1',

            errorPlacement: function (error, element) {
                if ($(element).hasClass('summernote')) {
                    element
                        .closest('.summernote-wrapper')
                        .find('.summernote-error')
                        .html(error);
                } else {
                    error.insertAfter(element);
                }
            },

            highlight: function (element) {
                if ($(element).hasClass('summernote')) {
                    $(element).next('.note-editor').addClass('border border-danger');
                }
            },

            unhighlight: function (element) {
                if ($(element).hasClass('summernote')) {
                    $(element).next('.note-editor').removeClass('border border-danger');
                    $(element)
                        .closest('.summernote-wrapper')
                        .find('.summernote-error')
                        .empty();
                }
            },

            submitHandler: function (form) {
                form.submit();
            }
        });
        
    });
</script>
@endpush