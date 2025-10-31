@extends('layouts.app')

@section('title', 'ข้อมูลเอเจนซี่')

@section('content')
<div class="content-section">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4">
         <h2 class="mb-3 mb-md-0">รายการข้อมูลเอเจนซี่</h2>
         <div class="d-flex gap-2">
            <input type="text" id="agent-search-input" class="form-control form-control-sm" placeholder="ค้นหา...">
            <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle me-1"></i> เพิ่มข้อมูลใหม่</a>
         </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>ชื่อเอเจนซี่</th>
                    <th>License</th>
                    <th>เบอร์โทรศัพท์</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody id="agent-table-body">
                @forelse ($agents as $agent)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $agent->agentNameEn }}</td>
                        <td>{{ $agent->agentLicense }}</td>
                        <td>{{ $agent->agentPhone }}</td>
                        <td class="text-center">
                            @can('edit-agents')
                            <a href="{{ route('agents.edit', $agent) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            @endcan
                            @can('delete-agents')
                            <form action="{{ route('agents.destroy', $agent) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">ลบ</button>
                            </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">ไม่พบข้อมูลเอเจนซี่</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Live Search for Agents Table
    const searchInput_agent = document.getElementById('agent-search-input');
    const tableBody_agent = document.getElementById('agent-table-body');
    const tableRows_agent = tableBody_agent.getElementsByTagName('tr');

    searchInput_agent.addEventListener('keyup', function() {
        const searchTerm = searchInput_agent.value.toLowerCase();
        for (let row of tableRows_agent) {
            row.style.display = row.textContent.toLowerCase().includes(searchTerm) ? "" : "none";
        }
    });

    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'คุณแน่ใจหรือไม่?',
                text: "คุณจะไม่สามารถย้อนกลับสิ่งนี้ได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'ใช่, ลบเลย!',
                cancelButtonText: 'ยกเลิก'
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
