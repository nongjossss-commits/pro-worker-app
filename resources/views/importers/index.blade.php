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
         <h2 class="mb-3 mb-md-0">{{ __('รายการข้อมูลบริษัทนำเข้า') }}</h2>
         <div class="d-flex flex-column flex-md-row gap-2">
            <input type="text" id="importer-search-input" class="form-control form-control-sm" placeholder="{{ __('ค้นหา...') }}">
            <a href="{{ route('importers.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i>{{ __('เพิ่มข้อมูลใหม่') }}</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>{{ __('ชื่อบริษัทนำเข้า (ไทย)') }}</th>
                    <th>{{ __('รหัสบริษัทนำเข้า') }}</th>
                    <th>{{ __('เลขที่ใบอนุญาต') }}</th>
                    <th class="text-center">{{ __('จัดการ') }}</th>
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
                            <div class="d-flex flex-column flex-md-row justify-content-center gap-2">
                            @can('edit-importers')
                            <a href="{{ route('importers.edit', $importer->id) }}" class="btn btn-sm btn-outline-primary">{{ __('แก้ไข') }}</a>
                            @endcan
                            @can('delete-importers')
                            <form action="{{ route('importers.destroy', $importer->id) }}" method="POST" class="d-grid d-md-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('ลบ') }}</button>
                            </form>
                            @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">{{ __('ไม่พบข้อมูลบริษัทนำเข้า') }}</td>
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

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: '{{ __('คุณแน่ใจหรือไม่?') }}',
                text: "{{ __('คุณจะไม่สามารถย้อนกลับสิ่งนี้ได้!') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: '{{ __('ใช่, ลบเลย!') }}',
                cancelButtonText: '{{ __('ยกเลิก') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'ลบแล้ว!',
                                'ข้อมูลของคุณถูกลบแล้ว',
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                'เกิดข้อผิดพลาด!',
                                data.error || 'ไม่สามารถลบข้อมูลได้',
                                'error'
                            );
                        }
                    })
                    .catch(error => {
                        Swal.fire(
                            'เกิดข้อผิดพลาด!',
                            'เกิดข้อผิดพลาดในการส่งข้อมูล',
                            'error'
                        );
                    });
                }
            });
        });
    });
</script>
@endpush
