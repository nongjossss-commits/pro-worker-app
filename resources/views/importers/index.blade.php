@extends('layouts.app')

@section('title', 'ข้อมูลบริษัทนำเข้า')

@section('content')
<div class="content-section">
    @if ($message = Session::get('success'))
        <div class="alert alert-success mb-4" role="alert">
            {{ $message }}
        </div>
    @endif
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">รายการข้อมูลบริษัทนำเข้า</h2>
         <div class="d-flex gap-2">
            <a href="{{ route('importers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>ชื่อบริษัทนำเข้า (ไทย)</th>
                    <th>รหัสบริษัทนำเข้า</th>
                    <th>เลขที่ใบอนุญาต</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($importers as $importer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $importer->importerNameTh }}</td>
                        <td>{{ $importer->importerId }}</td>
                        <td>{{ $importer->importerLicenseNo }}</td>
                        <td class="text-center">
                            <a href="{{ route('importers.edit', $importer->id) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="{{ route('importers.destroy', $importer->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?')">ลบ</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลบริษัทนำเข้า</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
