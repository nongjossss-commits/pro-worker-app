@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>{{ __('ตั้งค่าการแจ้งเตือน') }}</h1>
            <p>{{ __('กำหนดจำนวนวันล่วงหน้าก่อนถึงวันหมดอายุสำหรับเอกสารประเภทต่างๆ') }}</p>

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.notification_settings.update') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('ประเภทการแจ้งเตือน') }}</th>
                                    <th>{{ __('การตั้งค่า') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($typeLabels as $type => $label)
                                    @php
                                        $setting = $settings[$type] ?? new \App\Models\NotificationSetting(['days_before_expiry' => 30, 'is_enabled' => true]);
                                        $isMissingDataType = in_array($type, ['pink_card_missing', 'residence_permit_missing']);
                                        $isEnabled = old('settings.'.$type.'.is_enabled', $setting->is_enabled);
                                    @endphp
                                    <tr x-data="{ enabled: {{ $isEnabled ? 'true' : 'false' }} }">
                                        <td>{{ $label }}</td>
                                        <td>
                                            <div class="d-flex flex-column gap-2">
                                                {{-- Toggle Switch for ALL Types --}}
                                                <div class="form-check form-switch">
                                                    {{-- Hidden input for unchecked state --}}
                                                    <input type="hidden" name="settings[{{ $type }}][is_enabled]" value="0">

                                                    <input class="form-check-input" type="checkbox"
                                                           id="switch_{{ $type }}"
                                                           name="settings[{{ $type }}][is_enabled]"
                                                           value="1"
                                                           x-model="enabled">
                                                    <label class="form-check-label" for="switch_{{ $type }}">{{ __('เปิดใช้งานการแจ้งเตือน') }}</label>
                                                </div>

                                                @if (!$isMissingDataType)
                                                    {{-- Input for Days --}}
                                                    <div class="input-group" style="max-width: 200px;">
                                                        <input type="number"
                                                               name="settings[{{ $type }}][days_before_expiry]"
                                                               class="form-control"
                                                               value="{{ old('settings.'.$type.'.days_before_expiry', $setting->days_before_expiry) }}"
                                                               min="0"
                                                               required
                                                               :disabled="!enabled">
                                                        <span class="input-group-text">{{ __('วัน') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">{{ __('บันทึกการตั้งค่า') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
