{{--
    Shared field inputs for the issuance form (create.blade.php) and the
    correction form (edit.blade.php) — kept as one partial so the two forms
    can never drift out of sync on which field types render as what.

    Expects: $template, $addressGroups (from LaborContractController's
    addressGroups() helper). Optional: $values (assoc array of previously
    submitted values, e.g. $contract->field_values on the edit form —
    defaults to old()-only prefill for a fresh issuance).
--}}
@php($values = $values ?? [])

{{--
    $seenKeys dedupes by field key — the Template Builder lets an admin
    "copy" a field so the same key is drawn at multiple positions on the
    PDF (see builder.blade.php's copyItem()), meaning field_mapping can
    legitimately contain two items with an identical key. Without this,
    the form would render two <input name="fields[key]"> boxes for the
    same key; only the LAST one's value would actually survive to the
    server on submit (duplicate form field names silently overwrite), so
    the first box would look like it worked but its value would be
    discarded — showing one input per unique key is what makes "fill it
    in once, it shows up everywhere it's mapped to" true instead of a trap.
--}}
@php($seenKeys = [])
@foreach($template->field_mapping ?? [] as $item)
    @continue(!in_array($item['type'] ?? null, ['text', 'worker_count']))
    @continue(in_array($item['key'] ?? null, $seenKeys, true))
    @php($seenKeys[] = $item['key'] ?? null)
    <div class="mb-3">
        <label class="form-label">{{ $item['label'] }} *</label>
        @if(($item['type'] ?? null) === 'worker_count')
            <input type="number" min="0" name="fields[{{ $item['key'] }}]" class="form-control"
                   value="{{ old('fields.' . $item['key'], $values[$item['key']] ?? '') }}" required>
        @else
            <input type="text" name="fields[{{ $item['key'] }}]" class="form-control"
                   value="{{ old('fields.' . $item['key'], $values[$item['key']] ?? '') }}" required>
        @endif
    </div>
@endforeach

@foreach($addressGroups as $groupId => $group)
    <label class="form-label fw-bold">{{ $group['labelTh'] ?? __('Address') }}</label>
    @include('labor._address_group', [
        'groupId' => $groupId,
        'keyTh' => $group['keyTh'],
        'keyEn' => $group['keyEn'],
        'labelTh' => $group['labelTh'] ?? '',
        'labelEn' => $group['labelEn'] ?? '',
        'prefill' => empty($values) ? [] : [
            'province' => $values["{$groupId}_province"] ?? '',
            'district' => $values["{$groupId}_district"] ?? '',
            'subdistrict' => $values["{$groupId}_subdistrict"] ?? '',
            'no' => $values["{$groupId}_no"] ?? '',
            'moo' => $values["{$groupId}_moo"] ?? '',
            'soi' => $values["{$groupId}_soi"] ?? '',
            'road' => $values["{$groupId}_road"] ?? '',
            'soi_en' => $values["{$groupId}_soi_en"] ?? '',
            'road_en' => $values["{$groupId}_road_en"] ?? '',
        ],
    ])
@endforeach
