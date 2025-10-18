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
            <input type="text" id="importer-search-input" class="form-control form-control-sm" placeholder="ค้นหา...">
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
            <tbody id="importer-table-body">
                @forelse ($importers as $importer)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $importer->importerNameTh }}</td>
                        <td>{{ $importer->importerId }}</td>
                        <td>{{ $importer->importerLicenseNo }}</td>
                        <td class="text-center">
                            @can('edit-importers')
                            <a href="{{ route('importers.edit', $importer->id) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            @endcan
                            @can('delete-importers')
                            <button type="button" class="btn btn-sm btn-outline-danger btn-trigger-delete-modal"
                                    data-bs-toggle="modal"
                                    data-bs-target="#centralDeleteConfirmationModal"
                                    data-action="{{ route('importers.destroy', $importer->id) }}"
                                    data-message="คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูลบริษัทนำเข้า '{{ $importer->importerNameTh }}'?">
                                ลบ
                            </button>
                            @endcan
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

@push('scripts')
<script>
    // Live Search for Importers Table
    const searchInput_importer = document.getElementById('importer-search-input');
    const tableBody_importer = document.getElementById('importer-table-body');
    const tableRows_importer = tableBody_importer.getElementsByTagName('tr');

    searchInput_importer.addEventListener('keyup', function() {
        const searchTerm = searchInput_importer.value.toLowerCase();
        for (let row of tableRows_importer) {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        }
    });
</script>
@endpush
