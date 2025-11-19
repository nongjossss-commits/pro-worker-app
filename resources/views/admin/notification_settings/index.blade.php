@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h1>ตั้งค่าการแจ้งเตือน</h1>
            <p>กำหนดจำนวนวันล่วงหน้าก่อนถึงวันหมดอายุสำหรับเอกสารประเภทต่างๆ</p>

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
                                    <th>ประเภทการแจ้งเตือน</th>
                                    <th>แจ้งเตือนล่วงหน้า (วัน)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($settings as $setting)
                                    <tr>
                                        <td>{{ $typeLabels[$setting->notification_type] ?? $setting->notification_type }}</td>
                                        <td>
                                            <input type="number"
                                                   name="settings[{{ $setting->notification_type }}][days_before_expiry]"
                                                   class="form-control"
                                                   value="{{ old('settings.'.$setting->notification_type.'.days_before_expiry', $setting->days_before_expiry) }}"
                                                   min="0"
                                                   required>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
