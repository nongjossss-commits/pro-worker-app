@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold mb-3">Activity Logs (บันทึกการปฏิบัติงาน)</h2>
            <p class="text-muted">เลือกปีเพื่อดูบันทึกข้อมูลการทำงาน หรือค้นหาวันที่โดยตรง</p>
        </div>
    </div>

    {{-- ═══ ค้นหาประวัติลูกจ้าง / นายจ้าง ═══ --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-primary border-opacity-25">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0 text-primary"><i class="bi bi-person-lines-fill me-2"></i>ค้นหาประวัติลูกจ้าง / นายจ้าง</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">พิมพ์ชื่อลูกจ้าง, ชื่อนายจ้าง, เลข Passport, เลข Reference, เลข RA, หรือ ID เพื่อค้นหาและดูประวัติการแก้ไขทั้งหมด</p>
                    <div class="position-relative">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-primary"></i></span>
                            <input type="text"
                                   id="subject-search-input"
                                   class="form-control"
                                   placeholder="พิมพ์เพื่อค้นหา... ชื่อ, Passport, Reference ID, เลข RA, ID..."
                                   autocomplete="off">
                            <button class="btn btn-outline-secondary d-none" type="button" id="subject-search-clear">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                        {{-- Dropdown ผลลัพธ์ --}}
                        <div id="subject-search-results"
                             class="position-absolute w-100 bg-white border rounded-bottom shadow-lg d-none"
                             style="z-index: 1050; max-height: 400px; overflow-y: auto; top: 100%;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Date Search Card -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-search me-2 text-primary"></i>ค้นหาตามวันที่</h5>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <form action="{{ route('admin.activity-logs.search') }}" method="GET">
                        <div class="mb-3">
                            <label for="date" class="form-label">เลือกวันที่ต้องการดู</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            ไปที่วันที่เลือก
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Year Selection -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-folder me-2 text-warning"></i>เลือกปี (Year)</h5>
                </div>
                <div class="card-body">
                    @if($years->count() > 0)
                        <div class="row g-3">
                            @foreach($years as $year)
                            <div class="col-6 col-md-4 col-lg-3">
                                <a href="{{ route('admin.activity-logs.year', $year) }}" class="text-decoration-none">
                                    <div class="card bg-light hover-shadow transition h-100 text-center py-4">
                                        <div class="card-body">
                                            <i class="bi bi-folder-fill text-warning display-4 mb-2"></i>
                                            <h4 class="mb-0 text-dark fw-bold">{{ $year }}</h4>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox text-muted display-1"></i>
                            <p class="mt-3 text-muted">ยังไม่มีบันทึกกิจกรรม</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const input      = document.getElementById('subject-search-input');
    const resultsBox = document.getElementById('subject-search-results');
    const clearBtn   = document.getElementById('subject-search-clear');
    let debounceTimer = null;

    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        clearBtn.classList.toggle('d-none', !q);

        if (q.length < 2) {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => {
            fetch(`{{ route('admin.activity-logs.search-subject') }}?q=${encodeURIComponent(q)}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.length) {
                        resultsBox.innerHTML = '<div class="p-3 text-center text-muted"><i class="bi bi-search me-1"></i>ไม่พบข้อมูลที่ตรงกัน</div>';
                        resultsBox.classList.remove('d-none');
                        return;
                    }

                    let html = '';
                    data.forEach(item => {
                        const typeBadge = item.type === 'employee'
                            ? '<span class="badge bg-info me-2">ลูกจ้าง</span>'
                            : '<span class="badge bg-warning text-dark me-2">นายจ้าง</span>';

                        html += `<a href="{{ route('admin.activity-logs.subject-history') }}?subject_type=${encodeURIComponent(item.subject_type)}&subject_id=${item.id}"
                                    class="d-block text-decoration-none text-dark px-3 py-2 border-bottom subject-result-item"
                                    style="transition: background 0.15s;">
                                    <div class="d-flex align-items-center">
                                        ${typeBadge}
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="fw-bold">${item.name}</div>
                                            <div class="small text-muted text-truncate">
                                                ${item.name_sub ? item.name_sub + ' ' : ''}
                                                ${item.detail ? '| ' + item.detail + ' ' : ''}
                                                ${item.employer ? '| <i class="bi bi-building"></i> ' + item.employer : ''}
                                            </div>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                 </a>`;
                    });

                    resultsBox.innerHTML = html;
                    resultsBox.classList.remove('d-none');
                })
                .catch(() => {
                    resultsBox.innerHTML = '<div class="p-3 text-center text-danger">เกิดข้อผิดพลาด</div>';
                    resultsBox.classList.remove('d-none');
                });
        }, 300);
    });

    clearBtn.addEventListener('click', function() {
        input.value = '';
        resultsBox.classList.add('d-none');
        resultsBox.innerHTML = '';
        clearBtn.classList.add('d-none');
        input.focus();
    });

    // ปิด dropdown เมื่อคลิกที่อื่น
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#subject-search-input') && !e.target.closest('#subject-search-results')) {
            resultsBox.classList.add('d-none');
        }
    });

    // แสดง dropdown อีกครั้งเมื่อ focus กลับมา
    input.addEventListener('focus', function() {
        if (resultsBox.innerHTML && this.value.trim().length >= 2) {
            resultsBox.classList.remove('d-none');
        }
    });
})();
</script>
@endpush

<style>
    .hover-shadow:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        transform: translateY(-2px);
        transition: all 0.2s;
    }
    .subject-result-item:hover {
        background-color: #f0f4ff !important;
    }
</style>
@endsection
