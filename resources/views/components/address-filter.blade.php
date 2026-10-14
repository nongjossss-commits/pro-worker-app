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

<div class="card mb-3 shadow-sm border-0 bg-light">
    <div class="card-body p-2 px-3">
        <form action="{{ url()->current() }}" method="GET" id="address-filter-form" class="d-flex flex-wrap align-items-center gap-2">
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

            <span class="text-secondary small fw-bold text-nowrap d-flex align-items-center">
                <i class="bi bi-geo-alt-fill text-primary me-1"></i>{{ __('Filter by Address') }}
            </span>

            {{-- Changing a level clears the levels under it: the old district
                 doesn't exist in a new province, so keeping it always gave an
                 empty result. --}}
            <select name="addrProvince" class="form-select form-select-sm border-0 shadow-sm flex-grow-1" style="min-width: 160px; width: auto;"
                    onchange="this.form.addrDistrict.value=''; this.form.addrSubDistrict.value=''; this.form.submit()">
                <option value="">-- {{ __('All provinces') }} --</option>
                @foreach($provinceLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('addrProvince') == $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="addrDistrict" class="form-select form-select-sm border-0 shadow-sm flex-grow-1" style="min-width: 160px; width: auto;" title="{{ $districtLabel }}"
                    onchange="this.form.addrSubDistrict.value=''; this.form.submit()" @disabled(count($districtLabels) == 0)>
                <option value="">-- {{ $allDistricts }} --</option>
                @foreach($districtLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('addrDistrict') == $value)>{{ $label }}</option>
                @endforeach
            </select>

            <select name="addrSubDistrict" class="form-select form-select-sm border-0 shadow-sm flex-grow-1" style="min-width: 160px; width: auto;" title="{{ $subDistrictLabel }}" onchange="this.form.submit()" @disabled(count($subDistrictLabels) == 0)>
                <option value="">-- {{ $allSubDistricts }} --</option>
                @foreach($subDistrictLabels as $value => $label)
                    <option value="{{ $value }}" @selected(request('addrSubDistrict') == $value)>{{ $label }}</option>
                @endforeach
            </select>

            @if(request('addrProvince') || request('addrDistrict') || request('addrSubDistrict'))
                <span class="small text-muted text-nowrap">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>{{ $provinceLabels[request('addrProvince')] ?? request('addrProvince') }}</strong>
                    @if(request('addrDistrict')) > <strong>{{ $districtLabels[request('addrDistrict')] ?? request('addrDistrict') }}</strong> @endif
                    @if(request('addrSubDistrict')) > <strong>{{ $subDistrictLabels[request('addrSubDistrict')] ?? request('addrSubDistrict') }}</strong> @endif
                </span>
                <a href="{{ url()->current() . '?' . http_build_query(request()->except(['addrProvince', 'addrDistrict', 'addrSubDistrict', 'page'])) }}" class="btn btn-outline-danger btn-sm border-0 py-0">
                    <i class="bi bi-x-lg me-1"></i> {{ __('Clear address filter') }}
                </a>
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
