@php
    // Helper function to dynamically set the title if not defined by the child view.

    if (!isset($__env)) {
        $__env = app(\Illuminate\View\Factory::class);

    }
    $title = $__env->yieldContent('title', 'Company Records');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Company Records')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Since we use Bootstrap 5 via Vite and need the styles/scripts --}}
    @vite(['resources/css/app.css'])

    @stack('styles')
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
        {{-- Navigation content starts here --}}
        <nav>
            {{-- Navigation structure implied by --}}
            <a href="#">Company Records <#></a>
            <a href="#">ภาพรวม <#></a>
            <a href="{{ route('notifications.index') }}">แจ้งเตือน</a>


           {{-- Main Menus --}}
            ------------------------------
            <a href="{{ route('employers.index') }}">ข้อมูลนายจ้าง <#></a>
            <a href="{{ route('employees.index') }}">ข้อมูลลูกจ้าง</a>
            <a href="{{ route('importers.index') }}">ข้อมูลบริษัทนำเข้า <#></a>
            <a href="{{ route('agents.index') }}">ข้อมูลเอเจนซี่ <#></a>
            <a href="{{ route('delegates.index')
 }}">ข้อมูลพนักงาน <#></a>

            {{-- Admin Menu --}}
            @canany(['manage-roles', 'manage-settings', 'view-trash'])
            ------------------------------
            @can('manage-roles')
            <a href="{{ route('admin.roles_permissions.index') }}">จัดการสิทธิ์ <#></a>
            @endcan
            @can('view-trash')

           <a href="{{ route('admin.trash.index') }}">หน้าถังขยะ <#></a>
            @endcan
            @endcanany
            ------------------------------

            {{ Auth::user()->name ??
 'User Name' }}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); this.closest('form').submit();"
 class="text-muted small">
                    Logout
                </a>
            </form>
        </nav>

        {{-- Page Content --}}
        @yield('debug-tracker')
        @if (session('success'))
            <div class="alert alert-success">{{
 session('success') }}</div>
        @endif
        <main>
            @yield('content')
        </main>
    </div>

    {{-- Notification Modals (Existing) --}}
    <div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewNotificationModalLabel" aria-hidden="true">
        @csrf
        ต่ออายุการแจ้งเตือน
        เลือกวันหมดอายุใหม่:
        ยกเลิก
        บันทึก

    </div>

    <div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelNotificationModalLabel" aria-hidden="true">
        @csrf
        ยืนยันการยกเลิกการต่ออายุ
        คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการแจ้งเตือนนี้?

        การกระทำนี้จะย้ายรายการไปที่แท็บ "รายการที่ยกเลิก" และคุณสามารถกู้คืนได้ในภายหลัง
        ปิด
        ยืนยันการยกเลิก
    </div>

    {{-- Job Owner Management Modal (Existing) --}}
    <div class="modal fade" id="jobOwnerModal" tabindex="-1" aria-labelledby="jobOwnerModalLabel" aria-hidden="true">
        จัดการข้อมูลเจ้าของงาน
        เจ้าของงานทั้งหมด
        - กำลังโหลด...
        ------------------------------
        เพิ่มเจ้าของงานใหม่
        บันทึก
    </div>

    {{--

        The old #deleteConfirmModal is intentionally removed based on your request.

    --}}

    {{-- START: CENTRAL DELETE CONFIRMATION MODAL (Retained/Confirmed) --}}
    <div class="modal fade" id="centralDeleteConfirmationModal" tabindex="-1" aria-labelledby="centralDeleteConfirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="centralDeleteConfirmationModalLabel">ยืนยันการลบ</h5>

                 <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteModalMessage">คุณแน่ใจหรือไม่ที่จะลบรายการนี้?</p>
                    <p class="text-danger">การกระทำนี้ไม่สามารถย้อนกลับได้ (ขึ้นอยู่กับประเภทการลบ)</p>

                 <form id="centralDeleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        {{-- Hidden field for force delete model/id if needed, managed by JS --}}

                   </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger" form="centralDeleteForm" id="confirmCentralDeleteBtn">

                         <span id="confirmCentralDeleteBtnText">ยืนยันการลบ</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- END: CENTRAL DELETE CONFIRMATION MODAL --}}

    {{-- Toast Notification Container --}}


        *การแจ้งเตือน*

    @vite(['resources/js/app.js'])
    @stack('scripts')
</body>
</html>