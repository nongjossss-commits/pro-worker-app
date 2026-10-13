@props(['provinces' => [], 'districts' => [], 'subDistricts' => []])

@php
    // Values stay the Thai names stored in addresses.addrProvince etc. (that's
    // what the filter matches on); labels follow the interface language.
    $provinceLabels = \App\Models\Address::localizedNames('addrProvince', $provinces);
    $districtLabels = \App\Models\Address::localizedNames('addrDistrict', $districts);
    $subDistrictLabels = \App\Models\Address::localizedNames('addrSubDistrict', $subDistricts);

    $isBkk = request('addrProvince') === 'กรุงเทพมหานคร';
    $districtLabel = $isBkk ? __('Khet (District)') : __('District (Amphoe)');
    $subDistrictLabel = $isBkk ? __('Khwaeng (Sub-district)') : __('Sub-district (Tambon)');
    $allDistricts = $isBkk ? __('All khet') : __('All districts');
    $allSubDistricts = $isBkk ? __('All khwaeng') : __('All sub-districts');
@endphp

<div class="card mb-4 shadow-sm border-0 bg-light">
    <div class="card-body p-3">
        <div class="d-flex align-items-center mb-3">
            <div class="bg-primary bg-opacity-10 p-2 rounded me-2">
                <i class="bi bi-geo-alt-fill text-primary"></i>
            </div>
            <h6 class="mb-0 fw-bold">{{ __('Filter by Address') }}</h6>
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

            {{-- Changing a level clears the levels under it: the old district
                 doesn't exist in a new province, so keeping it always gave an
                 empty result. --}}
            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ __('Province') }}</label>
                <select name="addrProvince" class="form-select form-select-sm border-0 shadow-sm"
                        onchange="this.form.addrDistrict.value=''; this.form.addrSubDistrict.value=''; this.form.submit()">
                    <option value="">-- {{ __('All provinces') }} --</option>
                    @foreach($provinceLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('addrProvince') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ $districtLabel }}</label>
                <select name="addrDistrict" class="form-select form-select-sm border-0 shadow-sm"
                        onchange="this.form.addrSubDistrict.value=''; this.form.submit()" @disabled(count($districtLabels) == 0)>
                    <option value="">-- {{ $allDistricts }} --</option>
                    @foreach($districtLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('addrDistrict') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">{{ $subDistrictLabel }}</label>
                <select name="addrSubDistrict" class="form-select form-select-sm border-0 shadow-sm" onchange="this.form.submit()" @disabled(count($subDistrictLabels) == 0)>
                    <option value="">-- {{ $allSubDistricts }} --</option>
                    @foreach($subDistrictLabels as $value => $label)
                        <option value="{{ $value }}" @selected(request('addrSubDistrict') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            @if(request('addrProvince') || request('addrDistrict') || request('addrSubDistrict'))
                <div class="col-12 mt-2">
                    <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded shadow-sm">
                        <div class="small text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            {{ __('Showing results for address:') }}
                            <strong>{{ $provinceLabels[request('addrProvince')] ?? request('addrProvince') }}</strong>
                            @if(request('addrDistrict')) > <strong>{{ $districtLabels[request('addrDistrict')] ?? request('addrDistrict') }}</strong> @endif
                            @if(request('addrSubDistrict')) > <strong>{{ $subDistrictLabels[request('addrSubDistrict')] ?? request('addrSubDistrict') }}</strong> @endif
                        </div>
                        <a href="{{ url()->current() . '?' . http_build_query(request()->except(['addrProvince', 'addrDistrict', 'addrSubDistrict', 'page'])) }}" class="btn btn-outline-danger btn-sm border-0">
                            <i class="bi bi-x-lg me-1"></i> {{ __('Clear address filter') }}
                        </a>
                    </div>
                </div>
            @endif
        </form>
    </div>
</div>

@if(request('addrProvince') || request('addrDistrict') || request('addrSubDistrict'))
<script>
    // The page's other GET forms (search, nationality, status…) don't know
    // about the address filter — without this, searching silently dropped it.
    document.addEventListener('DOMContentLoaded', function () {
        const params = @json(request()->only(['addrProvince', 'addrDistrict', 'addrSubDistrict']));
        const here = location.pathname;
        document.querySelectorAll('form').forEach(function (form) {
            if (form.id === 'address-filter-form') return;
            if ((form.getAttribute('method') || 'get').toLowerCase() !== 'get') return;
            let target;
            try { target = new URL(form.getAttribute('action') || location.href, location.href).pathname; } catch (e) { return; }
            if (target !== here) return;
            Object.keys(params).forEach(function (name) {
                if (!params[name] || form.querySelector('[name="' + name + '"]')) return;
                const input = document.createElement('input');
                input.type = 'hidden'; input.name = name; input.value = params[name];
                form.appendChild(input);
            });
        });
    });
</script>
@endif
