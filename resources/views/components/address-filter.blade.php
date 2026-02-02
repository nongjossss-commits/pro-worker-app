@props(['provinces' => [], 'districts' => [], 'subDistricts' => []])

<div class="card mb-4 shadow-sm border-0 bg-light">
    <div class="card-body p-3">
        <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                <i class="bi bi-geo-alt-fill text-primary"></i>
            </div>
            <h6 class="mb-0 fw-bold">{{ __('คัดกรองตามที่อยู่') }}</h6>
            <span class="ms-2 badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 small fw-normal">{{ __('Dynamic Filter') }}</span>
        </div>
        <form action="{{ url()->current() }}" method="GET" id="address-filter-form" class="row g-3">
            {{-- Keep existing query params except address filters and page --}}
            @foreach(request()->except(['addrProvince', 'addrDistrict', 'addrSubDistrict', 'page']) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $v)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ __('จังหวัด') }}</label>
                <select name="addrProvince" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()">
                    <option value="">-- {{ __('ทุกจังหวัด') }} --</option>
                    @foreach($provinces as $province)
                        <option value="{{ $province }}" @selected(request('addrProvince') == $province)>{{ $province }}</option>
                    @endforeach
                </select>
            </div>

            @php
                $isBkk = request('addrProvince') === 'กรุงเทพมหานคร';
                $districtLabel = $isBkk ? __('เขต') : __('อำเภอ');
                $subDistrictLabel = $isBkk ? __('แขวง') : __('ตำบล');
            @endphp

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ $districtLabel }}</label>
                <select name="addrDistrict" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()" @disabled(empty($districts) || count($districts) == 0)>
                    <option value="">-- {{ __('ทุก' . $districtLabel) }} --</option>
                    @if(!empty($districts))
                        @foreach($districts as $district)
                            <option value="{{ $district }}" @selected(request('addrDistrict') == $district)>{{ $district }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ $subDistrictLabel }}</label>
                <select name="addrSubDistrict" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()" @disabled(empty($subDistricts) || count($subDistricts) == 0)>
                    <option value="">-- {{ __('ทุก' . $subDistrictLabel) }} --</option>
                    @if(!empty($subDistricts))
                        @foreach($subDistricts as $subDistrict)
                            <option value="{{ $subDistrict }}" @selected(request('addrSubDistrict') == $subDistrict)>{{ $subDistrict }}</option>
                        @endforeach
                    @endif
                </select>
            </div>

            @if(request('addrProvince') || request('addrDistrict') || request('addrSubDistrict'))
                <div class="col-12 mt-2">
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('กำลังแสดงผลตามที่อยู่:') }}
                            <strong>{{ request('addrProvince') }}</strong>
                            @if(request('addrDistrict')) > <strong>{{ request('addrDistrict') }}</strong> @endif
                            @if(request('addrSubDistrict')) > <strong>{{ request('addrSubDistrict') }}</strong> @endif
                        </div>
                        <a href="{{ url()->current() . '?' . http_build_query(request()->except(['addrProvince', 'addrDistrict', 'addrSubDistrict', 'page'])) }}" class="btn btn-outline-danger btn-sm border-0">
                            <i class="bi bi-x-lg me-1"></i> {{ __('ล้างตัวกรอง') }}
                        </a>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>
