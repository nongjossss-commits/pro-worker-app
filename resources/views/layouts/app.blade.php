<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Company Records')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @php $brand = \App\Services\BrandService::current(); @endphp

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="{{ $brand['primary_color'] }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="PWL System">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <!-- Tailwind CSS (Scoped/Utility) for Components using it -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            prefix: '', // No prefix to match component code
            corePlugins: {
               preflight: false, // Disable preflight to avoid conflict with Bootstrap
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Brand color overrides — set per-installation via Super Admin → Branding. --}}
    <style>
        :root {
            --bs-primary: {{ $brand['primary_color'] }};
            --bs-primary-rgb: {{ \App\Services\BrandService::hexToRgb($brand['primary_color']) }};
            --bs-primary-dark: {{ \App\Services\BrandService::darken($brand['primary_color'], 0.12) }};
            --bs-primary-light: {{ $brand['accent_color'] }};
            --brand-sidebar-bg: {{ $brand['sidebar_color'] }};
            --brand-accent: {{ $brand['accent_color'] }};
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc; /* Fallback color */
            --bs-border-color: #e2e8f0;
        }
        /* Apply sidebar background from brand setting. The sidebar uses
           Bootstrap's offcanvas — overriding --bs-offcanvas-bg keeps the
           drawer animations intact while picking up the chosen color. */
        #sidebar.offcanvas { --bs-offcanvas-bg: var(--brand-sidebar-bg); }

        body {
            font-size: 1rem;
            line-height: 1.6;
            background-color: var(--bs-body-bg);
            background-image: radial-gradient(var(--bs-border-color) 1px, transparent 1px);
            background-size: 1.5rem 1.5rem;
            background-attachment: fixed;
        }

        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: 260px;
            background-color: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,.05);
            /* padding: 1.5rem; <-- REMOVED */
            --bs-offcanvas-width: 260px; /* <-- ADDED */
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        #sidebar .navbar-brand {
            font-weight: 700;
            color: var(--bs-primary);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.5rem;
            text-decoration: none;
        }

        #sidebar .list-group-item {
            border: none;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            color: #475569;
            transition: background-color 0.2s ease-in-out, color 0.2s ease-in-out;
            font-size: 0.95rem;
        }

        #sidebar .list-group-item.active {
            background-color: var(--bs-primary-light);
            color: #ffffff;
            background-image: linear-gradient(to right, var(--bs-primary-light), var(--bs-primary));
            box-shadow: 0 4px 6px -1px rgba(249, 115, 22, 0.2), 0 2px 4px -2px rgba(249, 115, 22, 0.2);
        }

        #sidebar .list-group-item:hover:not(.active) {
            background-color: #f1f5f9;
            color: #1e293b;
        }

        #main-content {
            flex-grow: 1;
            padding: 2.5rem;
            overflow-y: auto;
        }

        .content-section {
            background-color: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid var(--bs-border-color);
            box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.07), 0 1px 2px -1px rgb(0 0 0 / 0.07);
            padding: 2rem;
        }
        .table thead {
            --bs-table-bg: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            font-weight: 600;
        }
        /* Highlight Animation for Employee Card */
        @keyframes highlightPulse {
            0% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0.7);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
            50% {
                box-shadow: 0 0 0 10px rgba(249, 115, 22, 0);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(249, 115, 22, 0);
                border-color: #f97316;
                background-color: #fff7ed;
                transform: scale(1.02);
            }
        }

        .employee-card.highlight {
            animation: highlightPulse 2s ease-out infinite;
            border: 2px solid #f97316 !important;
            background-color: #fff7ed !important;
            z-index: 10;
        }

        /* Drag-and-drop reorder in the Selected Items modal */
        .selected-item-card { transition: box-shadow 0.15s, transform 0.15s; }
        .selected-item-card:active { cursor: grabbing; }
        .selected-item-card.drag-over-before {
            box-shadow: 0 -4px 0 0 #0dcaf0, 0 0.125rem 0.25rem rgba(0,0,0,.075) !important;
        }
        .selected-item-card.drag-over-after {
            box-shadow: 0 4px 0 0 #0dcaf0, 0 0.125rem 0.25rem rgba(0,0,0,.075) !important;
        }
        .modal-backdrop.fade.show{
            display: none;
        }
        .modal.fade.show{
            background: rgba(0, 0, 0, 0.6);
        }
        /* Restore padding to the new offcanvas body */
        #sidebar .offcanvas-body {
            padding: 1.5rem;
        }
        /* Reduce main content padding on mobile view */
        @media (max-width: 991.98px) {
            #main-content {
                padding: 1.5rem;
            }
        }

        .scroll-to-top {
            position: fixed;
            bottom: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--bs-primary);
            color: #ffffff;
            text-align: center;
            line-height: 50px;
            font-size: 24px;
            cursor: pointer;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s, visibility 0.3s, transform 0.3s;
            transform: translateY(20px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .scroll-to-top.left {
            left: 20px;
        }
        .scroll-to-top.right {
            right: 20px;
        }
        .scroll-to-top.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        .scroll-to-top:hover {
            background-color: var(--bs-primary-dark);
        }

        /* Drawer Handle */
        .drawer-handle {
            position: fixed;
            left: 0;
            top: 50%;
            transform: translateY(-50%) translateX(-100%); /* Initially hidden off-screen */
            width: 12px;
            height: 50vh;
            background-color: var(--bs-primary);
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
            cursor: pointer;
            z-index: 999;
            transition: transform 0.3s ease, width 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            opacity: 0.8;
        }
        .drawer-handle.show {
            transform: translateY(-50%) translateX(0); /* Slide in */
        }
        .drawer-handle:hover {
            width: 16px;
            opacity: 1;
        }
        .drawer-handle i {
            color: white;
            font-size: 10px;
        }

        .form-check-input {
            border: 2px solid #0d6efd !important;
        }

        /* ─── Pulse animation (GPU-accelerated, replaces animate__pulse) ─── */
        @keyframes calBadgePulse {
            0%, 100% { transform: scale(1); }
            50%       { transform: scale(1.25); }
        }
        .cal-badge-pulse {
            display: inline-block;
            animation: calBadgePulse 2s ease-in-out infinite;
            will-change: transform;
        }

        /* ═══════════════════════════════════════════════════════════
           Responsive — Mobile & Tablet
           รองรับการแสดงผลบนมือถือและแท็บเล็ตทุกขนาด
           ปรับปรุง: 2026 | ยึดหลักนี้สำหรับฟีเจอร์ใหม่ทุกชิ้น
           ═══════════════════════════════════════════════════════════ */

        /* ─── ป้องกัน horizontal scroll ทั้งหน้า ─── */
        @media (max-width: 767.98px) {
            body, .main-layout { overflow-x: hidden; }
            #main-content { max-width: 100%; overflow-x: hidden; }
        }

        /* ─── Main content: ลด padding บนมือถือขนาดเล็ก ─── */
        @media (max-width: 575.98px) {
            #main-content { padding: 0.6rem; }
        }

        /* ─── Typography: ตัวอักษรเล็กลงบนมือถือ ─── */
        @media (max-width: 575.98px) {
            h1, h2, .h1, .h2 { font-size: 1.25rem !important; }
            h3, .h3           { font-size: 1.1rem  !important; }
            .display-4        { font-size: 2rem    !important; }
            .fs-5             { font-size: 1rem    !important; }
        }

        /* ─── Employee Card (partials/_employee_card): mobile layout ─── */
        /* บัตรลูกจ้างบน mobile: ปุ่มจะย้ายไปอยู่แถวใหม่ใต้ข้อมูล */
        @media (max-width: 767.98px) {
            .employee-card .card-body {
                flex-wrap: wrap;
                align-items: flex-start !important;
                gap: 0.25rem;
            }
            .employee-card .employee-info {
                flex: 1 1 0;
                min-width: 0;
                font-size: 0.85rem;
            }
            .employee-card .employee-info .employee-name-en {
                font-size: 0.9rem;
                font-weight: 600;
                display: block;
                word-break: break-word;
            }
            .employee-card .employee-info .employer-name { font-size: 0.78rem; }
            .employee-card .employee-info .document-details { font-size: 0.72rem; line-height: 1.45; }
            /* ทีม tag: เปลี่ยนจาก position-absolute เป็น relative บน mobile */
            .employee-card .employee-info > .position-absolute {
                position: relative !important;
                top: auto !important; right: auto !important;
                max-width: 100%; margin-bottom: 0.25rem;
                flex-wrap: wrap;
            }
            /* ปุ่ม action: ย้ายมาแถวใหม่ชิดขวา + ลอยเหนือ overlay */
            .employee-card .employee-actions {
                width: 100%;
                margin-top: 0.25rem;
                display: flex !important;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: 0.3rem !important;
                padding-left: 3.5rem; /* เยื้องผ่านรูปภาพ */
                position: relative;   /* สร้าง stacking context */
                z-index: 15;          /* เหนือ overlay (z-index: 10) */
            }
            .employee-card .employee-actions .btn { padding: 0.2rem 0.45rem; font-size: 0.78rem; }

            /* overlay badge: ย้ายไปด้านล่างการ์ด ไม่บดบังชื่อลูกจ้าง ไม่บดบังปุ่ม action */
            .employee-card .position-absolute.top-0.start-0.w-100.h-100 {
                align-items: flex-end !important;
                padding-bottom: 3rem;  /* เว้นพื้นที่ให้แถวปุ่ม action */
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            /* overlay badge text: เล็กลงบน mobile */
            .employee-card .position-absolute.top-0.start-0.w-100.h-100 a.badge {
                font-size: 0.72rem !important;
                padding: 0.25em 0.5em !important;
                max-width: 85% !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* ─── Modal: ขยายเต็มจอบน mobile ─── */
        @media (max-width: 767.98px) {
            .modal-dialog.modal-xl,
            .modal-dialog.modal-lg { margin: 0.4rem; max-width: calc(100vw - 0.8rem); }
            .modal-xl .modal-body, .modal-lg .modal-body { padding: 0.6rem; }
        }
        @media (max-width: 575.98px) {
            .modal-dialog { margin: 0.25rem; max-width: calc(100vw - 0.5rem); }
        }

        /* ─── Bulk Action Bar: ปรับขนาดบน mobile ─── */
        @media (max-width: 767.98px) {
            #bulkActionBar,
            #bulk-action-bar-notifications,
            .bulk-action-bar[style*="position: fixed"] {
                width: calc(100vw - 1.5rem) !important;
                min-width: unset !important;
                left: 50% !important;
                transform: translateX(-50%) !important;
                flex-wrap: wrap;
                gap: 0.4rem !important;
                padding: 0.5rem 0.75rem !important;
            }
        }

        /* ─── Workflow Appointment Popup Cards: mobile ─── */
        @media (max-width: 575.98px) {
            /* Toolbar: wrap ปุ่มลงบรรทัดใหม่ */
            #wf-appt-toolbar {
                flex-wrap: wrap !important;
                gap: 0.3rem !important;
                padding: 0.5rem !important;
            }
            #wf-appt-toolbar .btn { font-size: 0.78rem; padding: 0.25rem 0.5rem; }
            #wf-appt-toolbar .input-group { flex: 1 1 100% !important; min-width: 0 !important; }

            /* การ์ดนัดหมาย: ปุ่มย้ายมาแถวใหม่ */
            .wf-appt-card-item .card-body { padding: 0.5rem 0.6rem 0.5rem 1.2rem !important; }
            .wf-appt-card-item .d-flex.align-items-start.gap-3 { flex-wrap: wrap; gap: 0.4rem !important; }
            .wf-appt-card-item .flex-grow-1 { min-width: 0; font-size: 0.82rem; }
            /* ปุ่มขวาของการ์ด → เปลี่ยนเป็นแถวแนวนอน */
            .wf-appt-card-item .flex-shrink-0.d-flex.flex-column.gap-2.align-items-end {
                width: 100%;
                flex-direction: row !important;
                justify-content: flex-end;
                flex-wrap: wrap;
                gap: 0.3rem !important;
                margin-top: 0.25rem;
            }
            .wf-appt-card-item .btn { font-size: 0.78rem; padding: 0.2rem 0.5rem; }
        }

        /* ─── Production / Workflow Index: Employer Card Header ─── */
        @media (max-width: 991.98px) {
            .production-order-card .card-header .row.align-items-xl-center { gap: 0.5rem; }
            /* ปุ่ม action ด้านขวา: wrap บน tablet/mobile */
            .production-order-card .card-header .col-12.col-xl { text-align: left !important; }
            .production-order-card .card-header .d-flex.align-items-center.justify-content-xl-end {
                justify-content: flex-start !important;
                flex-wrap: wrap;
            }
            /* Stats badges container: wrap ได้บน tablet/mobile */
            .production-order-card .card-header .d-flex.align-items-center.gap-2.me-xl-3 {
                flex-wrap: wrap;
            }
            /* แต่ละ badge: ลด min-width ให้หดได้ */
            .production-order-card .card-header [style*="min-width: 8"],
            .production-order-card .card-header [style*="min-width: 9"] {
                min-width: 60px !important;
                padding-left: 0.4rem !important;
                padding-right: 0.4rem !important;
            }
        }
        @media (max-width: 575.98px) {
            .production-order-card .card-header { padding: 0.6rem 0.75rem; }
            .production-order-card .card-header h5 { font-size: 0.95rem; }
            /* ลำดับที่ด้านซ้าย: เล็กลง */
            .employer-sequence-number { font-size: 1.8rem !important; min-width: 36px !important; }
        }

        /* ═══════════════════════════════════════════════════════════
           Workflow Item Cards + Registration/Renewal Operations Cards
           — Mobile & Tablet Layout Fix (< 1200px) —
           Root cause: ข้อมูลส่วนต่างๆ (appointment, credentials, remarks)
           อยู่ใน d-flex ไม่มี flex-wrap → ล้นหน้าจอบน mobile/tablet
           ═══════════════════════════════════════════════════════════ */

        /* ── ใช้กับทั้ง mobile + tablet (< 1200px) ── */
        @media (max-width: 1199.98px) {

            /* 1. container หลักที่เก็บทุกส่วน: ให้ wrap ได้ */
            .item-card-outer .card-body .d-flex.w-100,
            .employee-card-wrapper .card-body .d-flex.w-100 {
                flex-wrap: wrap !important;
                align-items: flex-start !important;
            }

            /* 2. ส่วน appointment / credentials / remarks:
                  ให้แต่ละส่วนขึ้นบรรทัดใหม่ (full width ภายใน container) */
            .item-card-outer .ms-md-4,
            .item-card-outer .ms-md-2,
            .employee-card-wrapper .ms-md-2,
            .employee-card-wrapper .ms-md-4 {
                flex-basis: 100% !important;
                width: 100% !important;
                min-width: 0 !important;
                max-width: 100% !important;
                margin-left: 0 !important;
            }

            /* 3. panels ที่มี min-width inline (เฉพาะใน .card): ให้หดได้ */
            .item-card-outer .card [style*="min-width:"],
            .employee-card-wrapper .card [style*="min-width:"] {
                min-width: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            /* 4. step pills: wrap ได้ */
            [id^="steps-container-"] .d-flex { flex-wrap: wrap; gap: 0.25rem !important; }

            /* 5. floating edit popup: ป้องกันหลุดขอบจอ */
            .item-card-outer .position-absolute[style*="z-index: 1060"],
            .employee-card-wrapper .position-absolute[style*="z-index: 1060"] {
                position: fixed !important;
                left: 0.5rem !important;
                right: 0.5rem !important;
                min-width: 0 !important;
                max-width: calc(100vw - 1rem) !important;
            }
        }

        /* ── เฉพาะ tablet: ปรับ layout action buttons + info ── */
        @media (min-width: 768px) and (max-width: 1199.98px) {
            /* action buttons column: ไม่เล็กเกินไปบน tablet */
            .item-card-outer .d-flex.gap-2.flex-wrap.justify-content-end.align-items-center,
            .employee-card-wrapper .d-flex.gap-2.flex-wrap.justify-content-end {
                justify-content: flex-start !important;
                margin-top: 0.5rem;
            }
            /* step pills: เล็กลงนิดหน่อยบน tablet */
            [id^="steps-container-"] .btn.btn-sm { font-size: 0.75rem !important; padding: 0.2rem 0.5rem !important; }
            /* ปุ่มกลุ่ม: wrap ได้ */
            .item-card-outer .d-flex.gap-1,
            .employee-card-wrapper .d-flex.gap-1 { flex-wrap: wrap; }
        }

        /* ── เฉพาะ mobile (< 768px): ลดขนาดเพิ่มเติม ── */
        @media (max-width: 767.98px) {
            /* ลด padding การ์ด */
            .item-card-outer .card-body,
            .employee-card-wrapper .card-body { padding: 0.6rem 0.75rem !important; }

            /* sequence number: เล็กลง */
            .item-sequence-number,
            .employee-card-outer > .employee-sequence-number {
                min-width: 20px !important;
                font-size: 1rem !important;
            }

            /* ปุ่ม action: เล็กลงบน mobile */
            .item-card-outer .btn,
            .employee-card-wrapper .btn { font-size: 0.78rem; padding: 0.25rem 0.5rem; }
            .item-card-outer .btn-group .btn,
            .employee-card-wrapper .btn-group .btn { font-size: 0.78rem; padding: 0.25rem 0.5rem; }

            /* step pills: เล็กลง */
            [id^="steps-container-"] .btn.btn-sm {
                font-size: 0.72rem !important;
                padding: 0.2rem 0.5rem !important;
            }

            /* ปุ่มกลุ่มย่อย: wrap ได้ */
            .item-card-outer .d-flex.gap-1,
            .employee-card-wrapper .d-flex.gap-1 { flex-wrap: wrap; }

            /* badge text: เล็กลง */
            .item-card-outer .badge,
            .employee-card-wrapper .badge { font-size: 0.7rem; }
        }

        /* ─── Scoreboard Cards: 2 คอลัมน์บน mobile ─── */
        @media (max-width: 575.98px) {
            .row-cols-1.row-cols-md-3.row-cols-xl-5 > *,
            .row-cols-1.row-cols-md-3.row-cols-xl-6 > * { flex: 0 0 50%; max-width: 50%; }
        }

        /* ─── Input groups & filters: full width บน mobile ─── */
        @media (max-width: 575.98px) {
            .input-group { min-width: 0 !important; width: 100% !important; }
            /* ช่องค้นหาใน header */
            form .input-group[style*="max-width"] { max-width: 100% !important; }
        }

        /* ─── Navigation Tabs: scroll ─── */
        .nav.nav-pills.overflow-auto { scrollbar-width: thin; }
        .nav.nav-pills.overflow-auto::-webkit-scrollbar { height: 4px; }
        .nav.nav-pills.overflow-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 2px; }

        /* ─── Sticky toolbar (production operations): mobile ─── */
        @media (max-width: 575.98px) {
            .sticky-top.bg-white.border-bottom { flex-wrap: wrap; gap: 0.3rem; padding: 0.4rem !important; }
        }

        /* ─── Workflow Kanban Board: horizontal scroll บน mobile ─── */
        @media (max-width: 767.98px) {
            /* ถ้าหน้า workflow มี container แนวนอน ให้ scroll ได้ */
            .kanban-board, [class*="kanban"] {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }

        /* ─── Tables: เพิ่ม horizontal scroll อัตโนมัติ ─── */
        @media (max-width: 767.98px) {
            .table-responsive { -webkit-overflow-scrolling: touch; }
        }

        /* ─── Button groups in cards: wrap ─── */
        @media (max-width: 575.98px) {
            .d-flex.gap-2.flex-wrap { gap: 0.35rem !important; }
            .btn-group .btn, .d-flex.gap-2 .btn { white-space: nowrap; }
        }

        /* ─── Finance Modal: scroll on mobile ─── */
        @media (max-width: 767.98px) {
            [id^="finance-modal-body-"] { overflow-x: auto; }
        }

        /* ─── Overlay badges บน employee card: แท็บเล็ต (768-1199px) ย้ายลงด้านล่าง ─── */
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .employee-card .position-absolute.top-0.start-0.w-100.h-100 {
                align-items: flex-end !important;
                padding-bottom: 0.75rem;
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .employee-card .position-absolute.top-0.start-0.w-100.h-100 a.badge {
                font-size: 0.78rem !important;
                max-width: 90% !important;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        }

        /* ─── Overlay badges บน employee card: เล็กลงบน mobile เล็ก ─── */
        @media (max-width: 575.98px) {
            .employee-card .position-absolute.top-0.start-0.w-100.h-100 a.badge {
                font-size: 0.72rem !important;
                padding: 0.3em 0.5em !important;
                max-width: 95% !important;
            }
        }

        /* ─── Highlight animation เมื่อนำทาง GPS ไปยังการ์ดลูกจ้าง ─── */
        @keyframes highlightNavigatePulse {
            0%   { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.8); background-color: rgba(59, 130, 246, 0.12); }
            60%  { box-shadow: 0 0 0 16px rgba(59, 130, 246, 0); background-color: rgba(59, 130, 246, 0.06); }
            100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0);   background-color: transparent; }
        }
        .highlight-navigate {
            animation: highlightNavigatePulse 1.2s ease-out 3 !important;
            border: 2px solid #3b82f6 !important;
            border-radius: 0.5rem;
        }

        /* ─── Registration/Renewal employee card: mobile — รูปบน ชื่อล่าง ─── */
        @media (max-width: 767.98px) {
            /* avatar + info: เรียงจากบนลงล่าง */
            .employee-card-wrapper [id^="info-container-"] {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center;
                width: 100%;
            }
            /* info text: เต็มความกว้าง */
            .employee-card-wrapper [id^="info-container-"] > div:not(.avatar-container) {
                width: 100%;
            }
            /* sections ต่างๆ (outsource, appointment ฯลฯ): เต็มความกว้าง */
            .employee-card-wrapper .card-body .d-flex.flex-column.flex-md-row > .d-flex.align-items-start.gap-3.w-100 {
                flex-direction: column !important;
            }
            .employee-card-wrapper .ms-md-2,
            .employee-card-wrapper .ms-md-4 {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
        /* tablet: เหมือน mobile */
        @media (min-width: 768px) and (max-width: 1199.98px) {
            .employee-card-wrapper [id^="info-container-"] {
                flex-direction: column !important;
                align-items: center !important;
                text-align: center;
                width: 100%;
            }
            .employee-card-wrapper [id^="info-container-"] > div:not(.avatar-container) {
                width: 100%;
            }
        }

        /* ─── Dashboard appointment card: mobile — text ใช้พื้นที่เต็ม ─── */
        @media (max-width: 767.98px) {
            .appt-card-item .card-body > .d-flex.align-items-start.gap-3 {
                flex-wrap: wrap !important;
            }
            /* info text: เต็มความกว้าง */
            .appt-card-item .card-body > .d-flex > .flex-grow-1 {
                flex-basis: 100% !important;
                width: 100% !important;
                order: 2;
                margin-top: 0.5rem;
            }
            /* action buttons: เต็มความกว้าง ไปอยู่ด้านล่าง */
            .appt-card-item .card-body > .d-flex > .flex-shrink-0.d-flex.flex-column {
                flex-basis: 100% !important;
                flex-direction: row !important;
                justify-content: flex-end;
                order: 3;
                margin-top: 0.5rem;
            }
        }

        /* ─── Workflow Progress card header: ปุ่มตั้งค่า wrap บน tablet/mobile ─── */
        @media (max-width: 991.98px) {
            .card-body.p-4 > .d-flex.justify-content-between.align-items-center.mb-4 {
                flex-wrap: wrap;
                row-gap: 0.5rem;
            }
            .card-body.p-4 > .d-flex.justify-content-between.align-items-center.mb-4 .card-title {
                width: 100%;
            }
            .card-body.p-4 > .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2 {
                flex-wrap: wrap;
                gap: 0.35rem !important;
            }
            .card-body.p-4 > .d-flex.justify-content-between.align-items-center.mb-4 > .d-flex.gap-2 .btn {
                font-size: 0.78rem;
                padding: 0.2rem 0.5rem;
            }
        }

        /* ─── Registration/Renewal employer card: stats badges + ปุ่ม wrap บน mobile/tablet ─── */
        @media (max-width: 991.98px) {
            .employer-card-container .card-header .d-flex.align-items-center.gap-2.me-xl-3 {
                flex-wrap: wrap;
                gap: 0.25rem !important;
            }
            .employer-card-container .card-header .d-flex.align-items-center.gap-2.me-xl-3 [style*="min-width:"] {
                min-width: 0 !important;
                flex: 1 1 auto;
            }
            .employer-card-container .card-header .d-flex.align-items-center.justify-content-xl-end {
                flex-wrap: wrap;
                gap: 0.3rem !important;
            }
            .employer-card-container .card-header .d-flex.align-items-center.justify-content-xl-end .btn {
                font-size: 0.78rem;
                padding: 0.2rem 0.45rem;
            }
        }

        /* ═══ Resolution / Workflow Badges — uniform size with auto-shrink text ═══ */
        .resolution-badge {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            text-align: center;
            width: 240px;
            min-height: 42px;
            max-height: 42px;
            line-height: 1.15;
            padding: 4px 10px;
            font-size: 13px;
            white-space: normal !important;
            overflow: hidden;
            word-break: break-word;
            box-sizing: border-box;
        }
        .resolution-badge > i {
            flex-shrink: 0;
            margin-right: 4px;
        }
        .resolution-badge .rb-text {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.15;
        }
        /* Mobile */
        @media (max-width: 576px) {
            .resolution-badge {
                width: 200px;
                min-height: 38px;
                max-height: 38px;
                font-size: 12px;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>

    <div class="main-layout">
        <aside id="sidebar" class="offcanvas offcanvas-start" tabindex="-1" aria-labelledby="sidebarLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="sidebarLabel">
                    <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-reset">
                        <img src="{{ \App\Services\BrandService::logoUrl() }}" alt="Logo" style="height: 40px; width: auto;"> {{ $brand['short_name'] }}
                    </a>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
            <a class="navbar-brand d-flex flex-column align-items-center mb-4 mt-3" href="{{ route('dashboard') }}">
                <img src="{{ \App\Services\BrandService::logoUrl() }}" alt="{{ $brand['app_name'] }}" class="mb-2" style="height: 130px; width: auto; max-width: 100%; border: none;">
                <span style="line-height: 1.2;">{{ $brand['app_name'] }}</span>
            </a>
            <div class="list-group" id="main-nav">
                @include('partials.sidebar-menu')
            </div>
            </div>
        </aside>

        <main id="main-content" style="position: relative; z-index: 1;">
            {{-- Contract status banner: shown when read-only, in grace period, or approaching expiry --}}
            <x-contract-status-banner />

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
                        <i class="bi bi-list"></i>
                    </button>

                    {{-- Download Center Button (Fixed for better visibility) --}}
                    <button class="btn btn-outline-secondary d-none d-md-block" onclick="openDownloadCenter()">
                        <i class="bi bi-cloud-download-fill me-1"></i> Download Center
                    </button>

                    {{-- Drag to Split Button --}}
                    <a href="{{ request()->fullUrl() }}"
                       class="btn btn-outline-secondary d-none d-md-block ms-2"
                       draggable="true"
                       ondragstart="event.dataTransfer.setData('text/plain', this.href); event.dataTransfer.setData('text/uri-list', this.href);"
                       title="ลากเพื่อแยกหน้าจอ (Drag to Split)">
                        <i class="bi bi-grid-3x2-gap-fill me-1"></i> ลากเพื่อแยก
                    </a>

                    <x-job-check-widget />
                    @if(auth()->check() && auth()->user()->hasRole('super-admin'))
                        <x-bulk-move-attachments-modal />
                    @endif
                </div>

                <div class="d-flex align-items-center ms-auto gap-2">
                    {{-- Per-page User Manual button — pages render <x-help-button>
                         which pushes its trigger into this stack so the button
                         lives in the navbar next to the language switcher. --}}
                    @stack('navbar-help')

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            @switch(app()->getLocale())
                                @case('th') 🇹🇭 ไทย @break
                                @case('zh') 🇨🇳 中文 @break
                                @case('my') 🇲🇲 မြန်မာ @break
                                @default 🇺🇸 English
                            @endswitch
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">🇹🇭 ไทย (Thai)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'my') }}">🇲🇲 မြန်မာ (Myanmar)</a></li>
                        </ul>
                    </div>

                    <!-- User Dropdown (Top Right) -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i>
                            <span class="d-none d-md-inline">{{ Auth::user()->name ?? __('User') }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <form method="POST" action="{{ route('logout') }}" class="d-inline" id="logout-form">
                                    @csrf
                                    <a href="{{ route('logout') }}" class="dropdown-item text-danger" id="btn-logout">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
                                    </a>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            @yield('debug-tracker')
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('danger'))
                <div class="alert alert-danger">
                    {{ session('danger') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning">
                    {{ session('warning') }}
                </div>
            @endif

            @if (session('duplicate_error'))
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: '{{ __("Duplicate Employee") }}',
                            text: "{!! session('duplicate_error') !!}",
                            icon: 'warning',
                            confirmButtonText: '{{ __("OK") }}',
                            confirmButtonColor: '#F97316'
                        });
                    });
                </script>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>

        {{-- Page-level modals (render นอก <main> เพื่อหลีกเลี่ยง overflow/z-index issues) --}}
        @stack('page-modals')

        {{-- Notification Modals --}}
        <div class="modal fade" id="renewNotificationModal" tabindex="-1" aria-labelledby="renewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="renew-form" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="renewModalLabel">{{ __('Renew Notification') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            {{-- Dynamic Content Area --}}

                            {{-- Field for Pink Card Number (Only for pink_card_missing) --}}
                            <div class="mb-3 d-none" id="pink_card_number_group">
                                <label for="pink_card_number" class="form-label">เลขบัตรชมพู <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="pink_card_number" name="pink_card_number">
                            </div>

                            {{-- Field for Expiry Date (Hidden for missing data types) --}}
                            <div class="mb-3" id="new_due_date_group">
                                <label for="new_due_date" class="form-label">{{ __('Select new expiry date') }}: <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="new_due_date" name="new_due_date">
                            </div>

                            {{-- File Attachment --}}
                            <div class="mb-3">
                                <label for="attachment" class="form-label" id="attachment_label">{{ __('Attach document (if any)') }}:</label>
                                <input type="file" class="form-control" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png" multiple onchange="if(window.interceptFileSelect) window.interceptFileSelect(event)">
                                <div class="form-text text-muted" id="attachment_help"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="cancelNotificationModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="cancel-form" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelModalLabel">{{ __('Confirm Cancellation') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>{{ __('Are you sure you want to cancel this notification?') }}</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                            <button type="submit" class="btn btn-danger">{{ __('Confirm Cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Job Owner Management Modal --}}
    <div class="modal fade" id="jobOwnerModal" tabindex="-1" aria-labelledby="jobOwnerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobOwnerModalLabel">{{ __('Manage Job Owners') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{-- List of current owners --}}
                    <h5>{{ __('All Job Owners') }}</h5>
                    <ul class="list-group mb-3" id="jobOwnerList">
                        {{-- Owners will be loaded here via JS --}}
                        <li class="list-group-item text-muted">{{ __('Loading...') }}</li>
                    </ul>

                    <hr>

                    {{-- Add new owner form --}}
                    <h6>{{ __('Add New Job Owner') }}</h6>
                    <div class="input-group">
                        <input type="text" id="newJobOwnerName" class="form-control" placeholder="{{ __('Job Owner Name') }}">
                        <button class="btn btn-primary" type="button" id="saveNewJobOwnerBtn">{{ __('Save') }}</button>
                    </div>
                    <div id="jobOwnerError" class="text-danger small mt-1" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- START: Delete Address Confirmation Modal --}}
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">{{ __('Confirm Deletion') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{ __('Are you sure you want to delete this address?') }}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">{{ __('Delete') }}</button>
            </div>
        </div>
    </div>
</div>
{{-- END: Delete Address Confirmation Modal --}}

    @can('edit-employees')
    {{-- Combined Appointment Reminder Calendar — pops up once right after
         login (see AuthenticatedSessionController + the trigger script
         below). Same hand-rolled Alpine calendar-grid pattern as the
         per-module ones in production/registration|renewal/index.blade.php
         and workflow/index.blade.php, just pointed at the combined
         appointments.calendar/appointments.by-date endpoints instead of a
         single module's. --}}
    <div class="modal fade" id="appointmentReminderModal" tabindex="-1" aria-labelledby="appointmentReminderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable" style="max-width: 68%;">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title fw-bold" id="appointmentReminderModalLabel">
                        <i class="bi bi-calendar-event me-2"></i>{{ __('Appointment Calendar (All Menus)') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" x-data="combinedCalendarApp()">
                    <div class="row g-4 h-100">
                        {{-- Left: Monthly Calendar --}}
                        <div class="col-lg-5 col-xl-4 h-100 d-flex flex-column">
                            <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column">
                                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-month me-2"></i>{{ __('Monthly Overview') }}</h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="btn btn-sm btn-light border" @click="prevMonth()"><i class="bi bi-chevron-left"></i></button>
                                        <span class="fw-bold text-uppercase" style="min-width: 120px; text-align: center;" x-text="monthNames[month] + ' ' + year"></span>
                                        <button class="btn btn-sm btn-light border" @click="nextMonth()"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>
                                <div class="card-body p-3 flex-grow-1 d-flex flex-column">
                                    <div class="d-grid text-center mb-2" style="grid-template-columns: repeat(7, 1fr); font-size: 0.8rem; font-weight: bold; color: #6c757d;">
                                        <div>Sun</div><div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div>
                                    </div>
                                    <div class="d-grid flex-grow-1" style="grid-template-columns: repeat(7, 1fr); gap: 5px; min-height: 0;">
                                        <template x-for="day in days" :key="day.dateStr">
                                            <div
                                                class="border rounded p-2 d-flex flex-column align-items-center justify-content-between position-relative cursor-pointer h-100"
                                                :class="{
                                                    'bg-light text-muted': !day.isCurrentMonth,
                                                    'bg-white': day.isCurrentMonth,
                                                    'border-primary bg-primary bg-opacity-10 shadow-sm': day.dateStr === selectedDate,
                                                    'border-info bg-info bg-opacity-10': day.isToday && day.dateStr !== selectedDate
                                                }"
                                                @click="openDay(day.dateStr)"
                                            >
                                                <span class="fw-bold" style="font-size: 1.1rem;" x-text="day.dayNum"></span>
                                                <template x-if="counts[day.dateStr]">
                                                    <span class="badge bg-danger rounded-pill mt-1" style="font-size: 0.75rem;" x-text="counts[day.dateStr]"></span>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Appointments List --}}
                        <div class="col-lg-7 col-xl-8 h-100 d-flex flex-column">
                            <div class="card border-0 shadow-sm flex-grow-1 d-flex flex-column">
                                <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                                    <h6 class="fw-bold text-primary mb-0">
                                        <i class="bi bi-list-check me-2"></i>{{ __('Appointments for') }}: <span class="text-dark" x-text="selectedDateFormatted"></span>
                                    </h6>
                                    <div class="input-group" style="max-width: 260px;">
                                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                                        <input type="text" class="form-control border-start-0 bg-light" placeholder="{{ __('Search names, employer...') }}" x-model="searchQuery">
                                    </div>
                                </div>
                                {{-- Selection toolbar — static markup (not part of the
                                     AJAX-swapped day list), so its listeners are wired
                                     once at page load and don't depend on the fetched
                                     partial's own <script> executing (innerHTML-inserted
                                     <script> tags don't auto-run). The checkboxes/preview
                                     buttons inside the swapped content still work because
                                     they're handled by the existing document.body-level
                                     delegated listeners (see layouts/_app_scripts.blade.php). --}}
                                <div class="px-3 py-2 bg-white border-bottom d-flex align-items-center gap-2 flex-wrap">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="reminderSelectAllCb">
                                        <label class="form-check-label fw-bold small" for="reminderSelectAllCb">
                                            {{ __('Select All') }} (<span id="reminderSelectedCount">0</span>)
                                        </label>
                                    </div>
                                    <div class="vr"></div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-secondary dropdown-toggle" id="reminderBulkBtn" type="button" data-bs-toggle="dropdown" disabled>
                                            <i class="bi bi-lightning-fill me-1"></i>{{ __('Actions') }}
                                        </button>
                                        <ul class="dropdown-menu shadow">
                                            <li><a class="dropdown-item reminder-action" data-action="generate-pdf" href="#">
                                                <i class="bi bi-file-earmark-pdf me-2 text-danger"></i>{{ __('Automated PDF') }}</a></li>
                                            <li><a class="dropdown-item reminder-action" data-action="advanced-export" href="#">
                                                <i class="bi bi-file-earmark-spreadsheet me-2 text-success"></i>{{ __('Advanced Export') }}</a></li>
                                        </ul>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="reminderClearBtn" disabled>
                                        <i class="bi bi-x-circle me-1"></i>{{ __('Clear Selection') }}
                                    </button>
                                </div>
                                <div class="card-body p-0 overflow-auto bg-light position-relative" style="min-height: 300px;">
                                    <div x-show="isLoading" class="position-absolute w-100 h-100 bg-white bg-opacity-75" style="z-index: 10;">
                                        <div class="w-100 h-100 d-flex justify-content-center align-items-center">
                                            <div class="spinner-border text-primary" role="status"></div>
                                        </div>
                                    </div>
                                    <div id="reminderDayAppointmentsContent" class="p-3">
                                        <div x-show="!isLoading && !appointmentsLoaded" class="text-center py-5 text-muted">
                                            <i class="bi bi-calendar-x fs-1 opacity-25"></i>
                                            <p class="mt-2">{{ __('Select a date to view appointments.') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Self-contained modal + its own @push('scripts') for the column
         picker/submit logic — reused as-is, no extra variables needed. --}}
    @include('employees.modals.advanced_export')

    @push('scripts')
    <script>
        // ── Reminder modal selection toolbar (static — see comment above
        //    the toolbar markup for why this isn't embedded in the AJAX
        //    day-list partial). Mirrors the Automated PDF / Advanced Export
        //    bulk actions already used in production/registration's own
        //    appointment day-list (day_appointments_list.blade.php). ──
        document.addEventListener('DOMContentLoaded', function () {
            const modalEl = document.getElementById('appointmentReminderModal');
            if (!modalEl) return;

            const selectAllCb = document.getElementById('reminderSelectAllCb');
            const countSpan = document.getElementById('reminderSelectedCount');
            const bulkBtn = document.getElementById('reminderBulkBtn');
            const clearBtn = document.getElementById('reminderClearBtn');
            const listContainer = document.getElementById('reminderDayAppointmentsContent');
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            function syncToolbar() {
                const ids = window.getGlobalSelectedIds ? window.getGlobalSelectedIds() : [];
                countSpan.textContent = ids.length;
                bulkBtn.disabled = ids.length === 0;
                clearBtn.disabled = ids.length === 0;

                const visibleBoxes = listContainer.querySelectorAll('.employee-checkbox');
                const visibleChecked = listContainer.querySelectorAll('.employee-checkbox:checked');
                selectAllCb.checked = visibleBoxes.length > 0 && visibleBoxes.length === visibleChecked.length;
            }

            // Re-sync whenever any checkbox changes anywhere (covers both
            // this modal's own list and the global sessionStorage-backed
            // selection state changing from elsewhere).
            document.body.addEventListener('change', function (e) {
                if (e.target.classList && e.target.classList.contains('employee-checkbox')) {
                    syncToolbar();
                }
            });
            modalEl.addEventListener('shown.bs.modal', syncToolbar);

            selectAllCb.addEventListener('change', function () {
                const boxes = listContainer.querySelectorAll('.employee-checkbox');
                boxes.forEach(function (box) {
                    if (box.checked !== selectAllCb.checked) {
                        box.checked = selectAllCb.checked;
                        box.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            });

            clearBtn.addEventListener('click', function () {
                if (window.clearGlobalSelection) window.clearGlobalSelection();
                listContainer.querySelectorAll('.employee-checkbox').forEach(function (box) { box.checked = false; });
                syncToolbar();
            });

            document.querySelectorAll('.reminder-action').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const action = this.dataset.action;
                    const ids = window.getGlobalSelectedIds ? window.getGlobalSelectedIds() : [];
                    if (!ids.length) return;

                    if (action === 'generate-pdf') {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = '{{ route('admin.pdf-templates.generate.modal', [], false) }}';
                        [['_token', csrfToken], ['redirect_url', window.location.href]].forEach(function ([n, v]) {
                            const i = document.createElement('input'); i.type = 'hidden'; i.name = n; i.value = v; form.appendChild(i);
                        });
                        ids.forEach(function (id) {
                            const i = document.createElement('input'); i.type = 'hidden'; i.name = 'employees[]'; i.value = id; form.appendChild(i);
                        });
                        document.body.appendChild(form);
                        form.submit();
                    } else if (action === 'advanced-export') {
                        const el = document.getElementById('export_employee_ids');
                        const src = document.getElementById('export_source_menu');
                        if (el) el.value = JSON.stringify(ids);
                        if (src) src.value = 'appointment_reminder';
                        const exportModal = document.getElementById('advancedExportModal');
                        if (exportModal) bootstrap.Modal.getOrCreateInstance(exportModal).show();
                    }
                });
            });
        });

        function combinedCalendarApp() {
            return {
                month: new Date().getMonth(),
                year: new Date().getFullYear(),
                monthNames: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                days: [],
                counts: {},
                selectedDate: null,
                selectedDateFormatted: '',
                isLoading: false,
                appointmentsLoaded: false,
                searchQuery: '',

                init() {
                    this.generateCalendar();
                    this.fetchCounts();

                    this.$watch('searchQuery', (value) => {
                        const cards = document.querySelectorAll('#reminderDayAppointmentsContent .appointment-card');
                        const query = value.toLowerCase();
                        cards.forEach(card => {
                            const nameTh = card.dataset.employeeNameTh || '';
                            const employer = card.dataset.employerName || '';
                            card.style.display = (nameTh.includes(query) || employer.includes(query)) ? 'block' : 'none';
                        });
                    });
                },

                prevMonth() {
                    if (this.month === 0) { this.month = 11; this.year--; } else { this.month--; }
                    this.generateCalendar();
                    this.fetchCounts();
                },

                nextMonth() {
                    if (this.month === 11) { this.month = 0; this.year++; } else { this.month++; }
                    this.generateCalendar();
                    this.fetchCounts();
                },

                generateCalendar() {
                    const firstDay = new Date(this.year, this.month, 1);
                    const lastDay = new Date(this.year, this.month + 1, 0);
                    const daysInMonth = lastDay.getDate();
                    const startingDay = firstDay.getDay();

                    let calendarDays = [];

                    const prevMonthLastDay = new Date(this.year, this.month, 0).getDate();
                    for (let i = startingDay - 1; i >= 0; i--) {
                        let d = prevMonthLastDay - i;
                        let pm = this.month - 1, py = this.year;
                        if (pm < 0) { pm = 11; py--; }
                        calendarDays.push({ dayNum: d, isCurrentMonth: false, isToday: false, dateStr: `${py}-${String(pm+1).padStart(2,'0')}-${String(d).padStart(2,'0')}` });
                    }

                    const today = new Date();
                    for (let i = 1; i <= daysInMonth; i++) {
                        const isToday = (i === today.getDate() && this.month === today.getMonth() && this.year === today.getFullYear());
                        calendarDays.push({ dayNum: i, isCurrentMonth: true, isToday, dateStr: `${this.year}-${String(this.month+1).padStart(2,'0')}-${String(i).padStart(2,'0')}` });
                    }

                    const remaining = 42 - calendarDays.length;
                    for (let i = 1; i <= remaining; i++) {
                        let nm = this.month + 1, ny = this.year;
                        if (nm > 11) { nm = 0; ny++; }
                        calendarDays.push({ dayNum: i, isCurrentMonth: false, isToday: false, dateStr: `${ny}-${String(nm+1).padStart(2,'0')}-${String(i).padStart(2,'0')}` });
                    }

                    this.days = calendarDays;
                },

                fetchCounts() {
                    fetch(`{{ route('appointments.calendar') }}?month=${this.month + 1}&year=${this.year}`)
                        .then(res => res.json())
                        .then(data => { this.counts = data; });
                },

                openDay(dateStr) {
                    this.selectedDate = dateStr;
                    const d = new Date(dateStr);
                    this.selectedDateFormatted = d.toLocaleDateString('{{ app()->getLocale() }}', { day: 'numeric', month: 'short', year: 'numeric' });
                    this.isLoading = true;
                    this.appointmentsLoaded = true;

                    fetch(`{{ route('appointments.by-date') }}?date=${dateStr}`)
                        .then(res => res.json())
                        .then(data => {
                            document.getElementById('reminderDayAppointmentsContent').innerHTML = data.html;
                            this.isLoading = false;
                            this.searchQuery = '';
                        })
                        .catch(() => { this.isLoading = false; });
                }
            };
        }
    </script>
    @endpush
    @endcan

    {{-- Notification Summary Modal — pops up on the Welcome page right
         after the appointment calendar modal above closes (see the
         chaining script in resources/views/index.blade.php). Body is
         filled by JS via notifications.popup-summary (NotificationHelper::
         getPopupSummary()); gated the same double condition (SuperAdmin
         toggle + permission) as the sidebar's own Notifications link. --}}
    @if(\App\Facades\SuperAdmin::isVisible('notifications'))
    @can('view-notifications')
    <div class="modal fade" id="notificationSummaryModal" tabindex="-1" aria-labelledby="notificationSummaryModalLabel" aria-hidden="true"
        data-summary-url="{{ route('notifications.popup-summary') }}"
        data-label-overdue="{{ __('Overdue by :n days') }}"
        data-label-remaining="{{ __(':n days left') }}"
        data-label-empty="{{ __('No notifications right now.') }}">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title fw-bold" id="notificationSummaryModalLabel">
                        <i class="bi bi-bell-fill me-2"></i>{{ __('Notifications requiring action') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="notificationSummaryModalBody">
                    <div class="d-flex justify-content-center align-items-center py-5">
                        <div class="spinner-border text-danger" role="status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('notifications.index') }}" class="btn btn-danger">{{ __('View All') }}</a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>
    </div>
    @endcan
    @endif

    {{-- Toast Notification Container --}}
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <i class="bi bi-check-circle-fill rounded me-2 text-success"></i>
                <strong class="me-auto">{{ __('Notification') }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="toast-body-content">
                </div>
        </div>
    </div>

    {{-- Chat Widget Component --}}
    @include('components.chat-widget')

    {{-- Document Scanner Component (Global) --}}
    @include('components.document-scanner')

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/central-delete-handler.js', 'resources/js/duplicate-check.js'])
    <script src="{{ asset('js/financial-security.js') }}"></script>

    <script>
    // Global Drag Helper
    window.startDragGlobal = function(e, type, data) {
        const payload = {
            type: type,
            title: data.name || data.title || 'Item',
            subtitle: data.subtitle || data.code || '',
            url: data.url || window.location.href,
            source_menu: data.source_menu || document.title, // Default to page title if not provided
            ...data
        };
        e.dataTransfer.effectAllowed = 'copy';
        const jsonPayload = JSON.stringify(payload);
        e.dataTransfer.setData('application/json', jsonPayload);
        e.dataTransfer.setData('text/plain', jsonPayload); // Fallback for broader compatibility
    }

    // Register PWA Service Worker & Push Subscription
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(registration => {
                    console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    initializePushSubscription(registration);
                })
                .catch(err => {
                    console.log('ServiceWorker registration failed: ', err);
                });
        });
    }

    // Helper to convert VAPID key
    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    function initializePushSubscription(registration) {
        // Check if push is supported
        if (!('PushManager' in window)) {
            console.log('Push messaging isn\'t supported.');
            return;
        }

        // Check permission state
        if (Notification.permission === 'denied') {
            console.log('The user has blocked notifications.');
            return;
        }

        // We can request permission here, or let the user click a button.
        // For PWA experience, we often ask on load or interaction.
        // Let's ask on load for now as per requirement "like a modern app".

        // Use a dummy VAPID public key if we don't have a real one yet from .env
        // In a real scenario, this comes from config.
        // I will use a placeholder or check if one is injected.
        const vapidPublicKey = '{{ config('services.webpush.public_key', 'BCmti7ScwxxVAlB7WAzMMSiwV4-D1_5z509i546e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k7e4k4') }}';

        // If key is invalid placeholder, we can't really subscribe securely, but we'll try standard subscribe
        // For local testing without VAPID, push might not work fully in all browsers.

        // Ideally:
        const convertedVapidKey = urlBase64ToUint8Array(vapidPublicKey);

        // Check if already subscribed
        registration.pushManager.getSubscription()
            .then(function(subscription) {
                if (subscription) {
                    console.log('User is already subscribed:', subscription);
                    sendSubscriptionToBackEnd(subscription);
                    return subscription;
                }

                return registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: convertedVapidKey
                });
            })
            .then(function(subscription) {
                if (subscription) {
                    console.log('User is subscribed:', subscription);
                    sendSubscriptionToBackEnd(subscription);
                }
            })
            .catch(function(err) {
                console.log('Failed to subscribe the user: ', err);
            });
    }

    function sendSubscriptionToBackEnd(subscription) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ route('push.subscribe') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(subscription)
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('Bad status code from server.');
            }
            return response.json();
        })
        .then(function(responseData) {
            if (!responseData.success) {
                throw new Error('Bad response from server.');
            }
        });
    }
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const renewModal = document.getElementById('renewNotificationModal');
            if (renewModal) {
                renewModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const notificationType = button.getAttribute('data-notification-type');

                    const form = document.getElementById('renew-form');
                    const modalTitle = document.getElementById('renewModalLabel');
                    const pinkCardGroup = document.getElementById('pink_card_number_group');
                    const dueDateGroup = document.getElementById('new_due_date_group');
                    const dueDateInput = document.getElementById('new_due_date');
                    const attachmentLabel = document.getElementById('attachment_label');
                    const attachmentInput = document.getElementById('attachment');
                    const pinkCardInput = document.getElementById('pink_card_number');

                    // Reset Fields
                    pinkCardGroup.classList.add('d-none');
                    dueDateGroup.classList.remove('d-none');
                    dueDateInput.required = true;
                    pinkCardInput.required = false;
                    attachmentInput.required = false; // Default
                    attachmentLabel.textContent = '{{ __('Attach document (if any)') }}:';

                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/renew`;
                    }

                    // Adjust modal based on type
                    if (notificationType === 'pink_card_missing') {
                        modalTitle.textContent = 'อัพเดตข้อมูลบัตรชมพู';
                        pinkCardGroup.classList.remove('d-none');
                        pinkCardInput.required = true;
                        dueDateGroup.classList.add('d-none');
                        dueDateInput.required = false;
                        attachmentLabel.innerHTML = 'แนบไฟล์บัตรชมพู <span class="text-danger">*</span>';
                        attachmentInput.required = true;
                    } else if (notificationType === 'residence_permit_missing') {
                        modalTitle.textContent = 'อัพเดตใบแจ้งที่พักอาศัย';
                        dueDateGroup.classList.add('d-none');
                        dueDateInput.required = false;
                        attachmentLabel.innerHTML = 'แนบไฟล์ใบแจ้งที่พักอาศัย <span class="text-danger">*</span>';
                        attachmentInput.required = true;
                    } else {
                        modalTitle.textContent = '{{ __('Renew Notification') }}';
                    }
                });
            }

            const cancelModal = document.getElementById('cancelNotificationModal');
            if (cancelModal) {
                cancelModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const notificationId = button.getAttribute('data-notification-id');
                    const form = document.getElementById('cancel-form');
                    if(notificationId) {
                        form.action = `/notifications/${notificationId}/cancel`;
                    }
                });
            }
        });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const jobOwnerModalEl = document.getElementById('jobOwnerModal');
        if (!jobOwnerModalEl) return;

        const jobOwnerModal = new bootstrap.Modal(jobOwnerModalEl);
        const jobOwnerList = document.getElementById('jobOwnerList');
        const newJobOwnerNameInput = document.getElementById('newJobOwnerName');
        const saveNewJobOwnerBtn = document.getElementById('saveNewJobOwnerBtn');
        const jobOwnerError = document.getElementById('jobOwnerError');
        const mainJobOwnerSelect = document.getElementById('job_owner_id');
        const deleteJobOwnerBtn = document.getElementById('deleteJobOwnerBtn');

        // --- Main Logic ---

        // 1. Open Modal: Fetch and display all current job owners
        jobOwnerModalEl.addEventListener('show.bs.modal', function () {
            fetchJobOwners();
        });

        // 2. Add Owner: Handle click on "Save New Owner" button
        saveNewJobOwnerBtn.addEventListener('click', function () {
            const name = newJobOwnerNameInput.value.trim();
            if (!name) {
                showError('{{ __('Please enter job owner name') }}');
                return;
            }

            fetch('{{ route('job-owners.store') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ name: name })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => { throw err; });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Add to modal list
                    appendOwnerToList(data.jobOwner);
                    // Add to main dropdown and select it
                    if (mainJobOwnerSelect) {
                        const newOption = new Option(data.jobOwner.name, data.jobOwner.id, true, true);
                        mainJobOwnerSelect.add(newOption, null);
                    }
                    newJobOwnerNameInput.value = '';
                    hideError();
                }
            })
            .catch(error => {
                if (error.errors && error.errors.name) {
                    showError(error.errors.name[0]);
                } else {
                    showError('{{ __('Error saving') }}');
                }
            });
        });

        // 3. Delete Owner: Handle clicks on delete icons in the modal
        jobOwnerList.addEventListener('click', function(e) {
            if (e.target.classList.contains('delete-job-owner-icon')) {
                const ownerId = e.target.dataset.id;
                if (confirm('{{ __('Are you sure you want to delete this job owner?') }}')) {
                    deleteOwner(ownerId);
                }
            }
        });

        // 4. Delete selected owner from the main form
        if (deleteJobOwnerBtn && mainJobOwnerSelect) {
            deleteJobOwnerBtn.addEventListener('click', function() {
                const selectedOwnerId = mainJobOwnerSelect.value;
                if (selectedOwnerId && selectedOwnerId !== '--- เลือกเจ้าของงาน ---') { // Note: This check might be fragile if translation changes, using ID is better but logic is based on value
                     if (confirm('{{ __('Are you sure you want to delete this job owner?') }}')) {
                        deleteOwner(selectedOwnerId);
                    }
                } else {
                    alert('{{ __('Please select a job owner to delete') }}');
                }
            });
        }


        // --- Helper Functions ---

        function fetchJobOwners() {
            jobOwnerList.innerHTML = '<li class="list-group-item text-muted">{{ __('Loading...') }}</li>';
            fetch('{{ route('job-owners.index') }}')
                .then(response => response.json())
                .then(data => {
                    jobOwnerList.innerHTML = '';
                    if (data.length === 0) {
                        jobOwnerList.innerHTML = '<li class="list-group-item text-muted">{{ __('No job owner data') }}</li>';
                    } else {
                        data.forEach(owner => appendOwnerToList(owner));
                    }
                });
        }

        function appendOwnerToList(owner) {
             // Check if "no data" placeholder exists and remove it
            const placeholder = jobOwnerList.querySelector('.text-muted');
            if (placeholder) {
                placeholder.parentElement.innerHTML = '';
            }
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.id = `job-owner-${owner.id}`;
            li.textContent = owner.name;
            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'bi bi-trash-fill text-danger delete-job-owner-icon';
            deleteIcon.style.cursor = 'pointer';
            deleteIcon.dataset.id = owner.id;
            li.appendChild(deleteIcon);
            jobOwnerList.appendChild(li);
        }

        function deleteOwner(ownerId) {
            fetch(`/job-owners/${ownerId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from modal list
                    const listItem = document.getElementById(`job-owner-${ownerId}`);
                    if (listItem) listItem.remove();

                    // Remove from main dropdown
                    if (mainJobOwnerSelect) {
                        const optionToRemove = mainJobOwnerSelect.querySelector(`option[value='${ownerId}']`);
                        if (optionToRemove) optionToRemove.remove();
                    }
                } else {
                    alert(data.message || '{{ __('Cannot delete') }}');
                }
            });
        }

        function showError(message) {
            jobOwnerError.textContent = message;
            jobOwnerError.style.display = 'block';
            newJobOwnerNameInput.classList.add('is-invalid');
        }

        function hideError() {
            jobOwnerError.textContent = '';
            jobOwnerError.style.display = 'none';
            newJobOwnerNameInput.classList.remove('is-invalid');
        }
    });
    </script>
    <script>
    function showToast(message, type = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toast-body-content');
        const toastIcon = toastEl.querySelector('.toast-header i');

        // Reset classes
        toastIcon.className = 'rounded me-2';

        if (type === 'success') {
            toastIcon.classList.add('bi', 'bi-check-circle-fill', 'text-success');
        } else if (type === 'danger') {
            toastIcon.classList.add('bi', 'bi-exclamation-triangle-fill', 'text-danger');
        }

        toastBody.textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://npmcdn.com/flatpickr/dist/l10n/th.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Function to initialize Flatpickr on a given element
        function initFlatpickr(element) {
            if (element._flatpickr) return; // Already initialized

            // Check if element is readonly or disabled, if so, we might want to respect that
            // Flatpickr handles this automatically usually.

            flatpickr(element, {
                locale: 'th',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd/m/Y',
                allowInput: true,
                disableMobile: true, // Force Flatpickr UI on mobile for consistency
                onChange: function(selectedDates, dateStr, instance) {
                    // Dispatch native input event for frameworks like Alpine.js or Vue
                    // and 'change' event for standard listeners
                    instance.element.dispatchEvent(new Event('input', {bubbles: true}));
                    instance.element.dispatchEvent(new Event('change', {bubbles: true}));
                }
            });
        }

        // 1. Initialize on existing elements
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => initFlatpickr(input));

        // 2. Observe for new elements (e.g. via Alpine or AJAX)
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'INPUT' && node.type === 'date') {
                            initFlatpickr(node);
                        } else if (node.querySelectorAll) {
                             // Check children
                            const inputs = node.querySelectorAll('input[type="date"]');
                            inputs.forEach(input => initFlatpickr(input));
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
    </script>

    @include('components.download-modals')
    @include('partials.background-removal-scripts')
    @include('partials.photo-editor-tools')
    @include('partials.view_selected_modal')
    @stack('scripts')

{{-- Global: Clean up stale Bootstrap modal backdrops that get stuck due to CSS display:none hack --}}
@include('layouts._app_scripts')

</body>
</html>
