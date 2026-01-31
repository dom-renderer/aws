@extends('layouts.app', ['title' => $title, 'subTitle' => $subTitle])

@push('css')
<style>
    div.iti--inline-dropdown {
        min-width: 100%!important;
    }
    .iti__selected-flag {
        height: 32px!important;
    }
    .iti--show-flags {
        width: 100%!important;
    }  
    label.error {
        color: red;
    }
    #phone_number{
        font-family: "Hind Vadodara",-apple-system,BlinkMacSystemFont,"Segoe UI","Helvetica Neue",Arial,sans-serif;
        font-size: 15px;
    }
    #map {
        height: 400px;
        width: 100%;
        border-radius: 8px;
        border: 1px solid #ddd;
    }
    .map-container {
        margin-top: 15px;
    }
    .search-container {
        margin-bottom: 15px;
    }
    .search-container input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">Edit Customer</div>
            <div class="card-body">
                <form id="customerForm" method="POST" action="{{ route('customers.update', encrypt($customer->id)) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="customer-tab" data-bs-toggle="tab" data-bs-target="#customer" type="button" role="tab" aria-controls="customer" aria-selected="true">Customer Details</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locations" type="button" role="tab" aria-controls="locations" aria-selected="false">Locations</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="myTabContent">
                        <div class="tab-pane fade show active" id="customer" role="tabpanel" aria-labelledby="customer-tab">
                            <div class="mb-3">
                                <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $customer->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $customer->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3 row">
                                <div class="col-12">
                                    <label for="phone_number" class="form-label">Phone Number <span class="text-danger">*</span></label> <br>
                                    <input type="hidden" name="dial_code" id="dial_code" value="{{ $customer->dial_code }}">
                                    <input type="tel" class="form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}" required>
                                    @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="country_id" class="form-label">Country <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('country_id') is-invalid @enderror" id="country_id" name="country_id" required>
                                    <option value="">Select Country</option>
                                    @foreach($countries as $id => $name)
                                        <option value="{{ $id }}" {{ old('country_id', $customer->country_id) == $id ? 'selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                                @error('country_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="state_id" class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('state_id') is-invalid @enderror" id="state_id" name="state_id" required>
                                    <option value="">Select State</option>
                                    @if($customer->state)
                                        <option value="{{ $customer->state_id }}" selected>{{ $customer->state->name }}</option>
                                    @endif
                                </select>
                                @error('state_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="city_id" class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-select select2 @error('city_id') is-invalid @enderror" id="city_id" name="city_id" required>
                                    <option value="">Select City</option>
                                    @if($customer->city)
                                        <option value="{{ $customer->city_id }}" selected>{{ $customer->city->name }}</option>
                                    @endif
                                </select>
                                @error('city_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                    <option value="1" {{ old('status', $customer->status) == '1' ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ old('status', $customer->status) == '0' ? 'selected' : '' }}>Inactive</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">Update Customer</button>
                                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="locations" role="tabpanel" aria-labelledby="locations-tab">
                            <div class="d-flex justify-content-end mb-3">
                                <button type="button" class="btn btn-primary" onclick="openLocationModal()">Add Location</button>
                            </div>
                            <table class="table table-bordered" id="locationsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Code</th>
                                        <th>Address</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Location Modal -->
<div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="locationModalLabel">Add Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="locationForm">
                    <input type="hidden" id="loc_id">
                    <input type="hidden" id="loc_customer_id" name="customer_id" value="{{ $customer->id }}">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_name" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="loc_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_code" class="form-label">Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="loc_code" name="code" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="loc_email" name="email" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="loc_address_line_1" class="form-label">Address Line 1 <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="loc_address_line_1" name="address_line_1" required>
                    </div>

                    <div class="mb-3">
                        <label for="loc_address_line_2" class="form-label">Address Line 2</label>
                        <input type="text" class="form-control" id="loc_address_line_2" name="address_line_2">
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_zipcode" class="form-label">Zipcode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="loc_zipcode" name="zipcode" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="loc_contact_number" name="contact_number" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="loc_fax" class="form-label">Fax</label>
                                <input type="text" class="form-control" id="loc_fax" name="fax">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Map Location</label>
                        <div class="search-container">
                            <input type="text" id="address_search" placeholder="Search for address..." class="form-control">
                        </div>
                        <div class="map-container">
                            <div id="map"></div>
                        </div>
                        <input type="hidden" id="latitude" name="latitude">
                        <input type="hidden" id="longitude" name="longitude">
                        <small class="form-text text-muted">Click on the map to set the exact location coordinates.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveLocation()">Save Location</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('assets/css/intel-tel.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/select2.min.css') }}">
@endpush

@push('js')
<script src="{{ asset('assets/js/jquery-validate.min.js') }}"></script>
<script src="{{ asset('assets/js/intel-tel.js') }}"></script>
<script src="{{ asset('assets/js/select2.min.js') }}"></script>
<script>
let map;
let marker;
let geocoder;
let locationsTable;

const carribianCountries = @json(\App\Helpers\Helper::$carribianCountries);

function toggleCarribianLogic(countryId, isModal = false) {
    const isCarribian = carribianCountries.includes(parseInt(countryId));
    const label = isModal ? $('label[for="loc_state_id"]') : $('label[for="state_id"]');
    const cityDiv = isModal ? $('#loc_city_id').parent().parent() : $('#city_id').parent();
    const cityInput = isModal ? $('#loc_city_id') : $('#city_id');

    if (isCarribian) {
        label.html('Parish <span class="text-danger">*</span>');
        cityDiv.hide();
        cityInput.prop('required', false);
    } else {
        label.html('State <span class="text-danger">*</span>');
        cityDiv.show();
        cityInput.prop('required', true);
    }
}

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: 40.7128, lng: -74.0060 },
        zoom: 10,
    });

    geocoder = new google.maps.Geocoder();
    marker = new google.maps.Marker({
        map: map,
        draggable: true,
    });

    map.addListener("click", (e) => {
        placeMarkerAndPanTo(e.latLng, map);
    });

    marker.addListener("dragend", () => {
        const position = marker.getPosition();
        document.getElementById("latitude").value = position.lat();
        document.getElementById("longitude").value = position.lng();
    });
}

function placeMarkerAndPanTo(latLng, map) {
    marker.setPosition(latLng);
    map.panTo(latLng);
    document.getElementById("latitude").value = latLng.lat();
    document.getElementById("longitude").value = latLng.lng();
}

function openLocationModal(id = null) {
    $('#locationForm')[0].reset();
    $('#loc_id').val('');
    $('#loc_country_id').val('').trigger('change');
    $('#loc_state_id').empty().trigger('change');
    $('#loc_city_id').empty().trigger('change');
    
    if (id) {
        $.ajax({
            url: "{{ route('customer-locations.index') }}/" + id + "/edit",
            url: "{{ url('customer-locations') }}/" + id,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                const loc = response.location || response;
                
                $('#loc_id').val(loc.id);
                
                $('#loc_name').val(loc.name);
                $('#loc_code').val(loc.code);
                $('#loc_email').val(loc.email);
                $('#loc_address_line_1').val(loc.address_line_1);
                $('#loc_address_line_2').val(loc.address_line_2);
                $('#loc_zipcode').val(loc.zipcode);
                $('#loc_contact_number').val(loc.contact_number);
                $('#loc_fax').val(loc.fax);
                $('#latitude').val(loc.latitude);
                $('#longitude').val(loc.longitude);
                
                $('#loc_country_id').val(loc.country_id).trigger('change');

                loadStates(loc.country_id, loc.state_id, function() {
                    loadCities(loc.state_id, loc.city_id);
                });

                if (loc.latitude && loc.longitude) {
                    const latLng = new google.maps.LatLng(parseFloat(loc.latitude), parseFloat(loc.longitude));
                    placeMarkerAndPanTo(latLng, map);
                }

                function openLocationModal(index = null) {
                    toggleCarribianLogic($('#loc_country_id').val(), true);
                }
                
                $('#locationModal').modal('show');
            }
        });
    } else {
        $('#locationModal').modal('show');
    }
    
    setTimeout(() => {
        google.maps.event.trigger(map, "resize");
        if (!id) {
            map.setCenter({ lat: 40.7128, lng: -74.0060 });
        }
    }, 500);
}

function loadStates(countryId, selectedStateId, callback) {
    $.ajax({
        url: "{{ route('state-list') }}",
        type: "POST",
        data: {
            country_id: countryId,
            _token: "{{ csrf_token() }}"
        },
        success: function(data) {
            var $state = $('#loc_state_id');
            $state.empty();
            $state.append('<option value="">Select State</option>');
            $.each(data.items, function(index, item) {
                $state.append(new Option(item.text, item.id, false, item.id == selectedStateId));
            });
            $state.trigger('change');
            if (callback) callback();
        }
    });
}

function loadCities(stateId, selectedCityId) {
    $.ajax({
        url: "{{ route('city-list') }}",
        type: "POST",
        data: {
            state_id: stateId,
            _token: "{{ csrf_token() }}"
        },
        success: function(data) {
            var $city = $('#loc_city_id');
            $city.empty();
            $city.append('<option value="">Select City</option>');
            $.each(data.items, function(index, item) {
                $city.append(new Option(item.text, item.id, false, item.id == selectedCityId));
            });
            $city.trigger('change');
        }
    });
}

function saveLocation() {
    if (!$('#locationForm').valid()) return;

    const id = $('#loc_id').val();
    const url = id ? "{{ url('customer-locations') }}/" + id : "{{ route('customer-locations.store') }}";
    
    const method = id ? 'PUT' : 'POST';
    const data = $('#locationForm').serialize();

    $.ajax({
        url: url,
        type: method,
        data: data + "&_token={{ csrf_token() }}",
        success: function(response) {
            $('#locationModal').modal('hide');
            loadLocations();
        },
        error: function(xhr) {
            alert('Error saving location');
        }
    });
}

function deleteLocation(id) {
    if (confirm('Are you sure you want to remove this location?')) {
        $.ajax({
            url: "{{ url('customer-locations') }}/" + id,
            type: 'DELETE',
            data: { _token: "{{ csrf_token() }}" },
            success: function(response) {
                loadLocations();
            },
            error: function(xhr) {
                alert('Error deleting location');
            }
        });
    }
}

function loadLocations() {
    
    if ($.fn.DataTable.isDataTable('#locationsTable')) {
        $('#locationsTable').DataTable().destroy();
    }

    $('#locationsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('customer-locations.index') }}",
            data: function(d) {
                d.customer_id = "{{ $customer->id }}";
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'code', name: 'code' },
            { data: 'location', name: 'location' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        createdRow: function(row, data, dataIndex) {
        },
        drawCallback: function(settings) {
            $('.btn-primary').off('click').on('click', function(e) {
                e.preventDefault();
                const url = $(this).attr('href');
                const id = url.split('/').pop();
                openLocationModal(id);
            });
            
        }
    });
}

$(document).ready(function() {
    $('#country_id').select2({
        allowClear: true,
        placeholder: 'Select country',
        width: '100%'
    });

    const input = document.querySelector('#phone_number');
    const iti = window.intlTelInput(input, {
        initialCountry: "in",
        separateDialCode:true,
        nationalMode:false,
        preferredCountries: @json(\App\Models\Country::select('iso2')->pluck('iso2')->toArray()),
        utilsScript: "{{ asset('assets/js/intel-tel-2.min.js') }}"
    });
    input.addEventListener("countrychange", function() {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });
    input.addEventListener('keyup', () => {
        if (iti.isValidNumber()) {
            $('#dial_code').val(iti.s.dialCode);
        }
    });

    $('#country_id').on('change', function() {
        const countryId = $(this).val();
        if (countryId == 20) {
            $('label[for="state_id"]').text('Parish');
            $('#city_id').parent().hide();
        } else {
            $('label[for="state_id"]').text('State');
            $('#city_id').parent().show();
        }
        toggleCarribianLogic($(this).val(), false);
    });

    if ($('#country_id').val() == 20) {
        $('label[for="state_id"]').text('Parish');
        $('#city_id').parent().hide();
    }

    $('#state_id').select2({
        allowClear: true,
        placeholder: 'Select state',
        width: '100%',
        ajax: {
            url: "{{ route('state-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    country_id: $('#country_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    $('#city_id').select2({
        allowClear: true,
        placeholder: 'Select city',
        width: '100%',
        ajax: {
            url: "{{ route('city-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    state_id: $('#state_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    $('#loc_country_id').select2({
        dropdownParent: $('#locationModal'),
        allowClear: true,
        placeholder: 'Select country',
        width: '100%'
    });

    $('#loc_country_id').on('change', function() {
        const countryId = $(this).val();
        if (countryId == 20) {
            $('label[for="loc_state_id"]').text('Parish');
            $('#loc_city_id').parent().parent().hide();
        } else {
            $('label[for="loc_state_id"]').text('State');
            $('#loc_city_id').parent().parent().show();
        }
        toggleCarribianLogic($(this).val(), true);
    });
    
    $('#loc_state_id').select2({
        dropdownParent: $('#locationModal'),
        allowClear: true,
        placeholder: 'Select state',
        width: '100%',

        ajax: {
            url: "{{ route('state-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    country_id: $('#loc_country_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    $('#loc_city_id').select2({
        dropdownParent: $('#locationModal'),
        allowClear: true,
        placeholder: 'Select city',
        width: '100%',

        ajax: {
            url: "{{ route('city-list') }}",
            type: "POST",
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    searchQuery: params.term,
                    page: params.page || 1,
                    state_id: $('#loc_state_id').val(),
                    _token: "{{ csrf_token() }}"
                };
            },
            processResults: function(data, params) {
                params.page = params.page || 1;
                return {
                    results: $.map(data.items, function(item) {
                        return {
                            id: item.id,
                            text: item.text
                        };
                    }),
                    pagination: {
                        more: data.pagination.more
                    }
                };
            },
            cache: true
        }
    });

    $('#address_search').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            const address = $(this).val();
            if (address) {
                geocoder.geocode({ address: address }, (results, status) => {
                    if (status === "OK") {
                        const location = results[0].geometry.location;
                        placeMarkerAndPanTo(location, map);
                        map.setZoom(15);
                    } else {
                        alert("Geocode was not successful for the following reason: " + status);
                    }
                });
            }
        }
    });

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (e.target.id === 'locations-tab') {
            loadLocations();
        }
    });
    
    if ($('#locations-tab').hasClass('active')) {
        loadLocations();
    }
});

window.initMap = initMap;
</script>
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ config('app.google_maps_api_key', 'YOUR_API_KEY') }}&callback=initMap"></script>
@endpush
