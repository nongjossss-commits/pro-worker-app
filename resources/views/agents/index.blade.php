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
                            <a href="{{ route('agents.edit', $agent) }}" class="btn btn-sm btn-outline-primary">แก้ไข</a>
                            <form action="{{ route('agents.destroy', $agent) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบรายการนี้?')">ลบ</button>
                            </form>
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
</script>
@endpush
