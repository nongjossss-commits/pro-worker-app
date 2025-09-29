{{-- Registered Address Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ที่อยู่ตามทะเบียน</h5>
        <button type="button" class="btn btn-sm btn-outline-primary add-address-btn" data-type="registered" data-addressable-id="{{ $employer->id }}" data-addressable-type="employer" data-bs-toggle="modal" data-bs-target="#addressModal">
            <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
        </button>
    </div>
    <div id="registeredAddressList" class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'registered') as $address)
            <div class="address-card d-flex justify-content-between align-items-start p-3 border rounded" id="address-card-{{$address->id}}">
                <div>
                    <p class="mb-0">
                        เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                        แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                        {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                    </p>
                    <p class="mb-0 text-muted small">
                        Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                        {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                        {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                    </p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary edit-address-btn"
                            data-id="{{ $address->id }}"
                            data-addressable-id="{{ $employer->id }}"
                            data-addressable-type="employer"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>

{{-- Workplace Address Section --}}
<div class="content-section mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">ที่อยู่สถานที่ทำงาน</h5>
        <button type="button" class="btn btn-sm btn-outline-primary add-address-btn" data-type="workplace" data-addressable-id="{{ $employer->id }}" data-addressable-type="employer" data-bs-toggle="modal" data-bs-target="#addressModal">
            <i class="bi bi-plus-lg"></i> เพิ่มที่อยู่
        </button>
    </div>
    <div id="workplaceAddressList" class="vstack gap-3">
        @forelse ($employer->addresses->where('type', 'workplace') as $address)
            <div class="address-card d-flex justify-content-between align-items-start p-3 border rounded" id="address-card-{{$address->id}}">
                <div>
                    <p class="mb-0">
                        เลขที่ {{ $address->addrNo ?? '' }} หมู่ {{ $address->addrMoo ?? '' }} ซอย{{ $address->addrSoi ?? '' }} ถนน{{ $address->addrRoad ?? '' }}
                        แขวง/ตำบล {{ $address->addrSubDistrict ?? '' }} เขต/อำเภอ {{ $address->addrDistrict ?? '' }}
                        {{ $address->addrProvince ?? '' }} {{ $address->addrZipCode ?? '' }}
                    </p>
                    <p class="mb-0 text-muted small">
                        Addr: {{ $address->addrNoEn ?? '' }}, Moo: {{ $address->addrMooEn ?? '' }}, Soi: {{ $address->addrSoiEn ?? '' }}, Road: {{ $address->addrRoadEn ?? '' }},
                        {{ $address->addrSubDistrictEn ?? '' }}, {{ $address->addrDistrictEn ?? '' }},
                        {{ $address->addrProvinceEn ?? '' }} {{ $address->addrZipCodeEn ?? '' }}
                    </p>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary edit-address-btn"
                            data-id="{{ $address->id }}"
                            data-addressable-id="{{ $employer->id }}"
                            data-addressable-type="employer"
                            data-bs-toggle="modal"
                            data-bs-target="#addressModal">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger delete-address-btn" data-id="{{ $address->id }}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        @empty
            <p class="text-muted">ยังไม่มีที่อยู่</p>
        @endforelse
    </div>
</div>