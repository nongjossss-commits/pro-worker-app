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

    <!-- PWA Manifest & Meta Tags -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#F97316">
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

    <style>
        :root {
            --bs-primary: #F97316;
            --bs-primary-rgb: 249, 115, 22;
            --bs-primary-dark: #EA580C;
            --bs-primary-light: #FB923C;
            --bs-body-font-family: 'Inter', 'Sarabun', sans-serif;
            --bs-body-bg: #f8fafc; /* Fallback color */
            --bs-border-color: #e2e8f0;
        }

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
                        <img src="{{ asset('images/logo_new.jpg') }}" alt="Logo" style="height: 40px; width: auto;"> Proworker labour
                    </a>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body d-flex flex-column p-0">
            <a class="navbar-brand d-flex flex-column align-items-center mb-4 mt-3" href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo_new.jpg') }}" alt="Proworker Logo" class="mb-2" style="height: 130px; width: auto; max-width: 100%; border: none;">
                <span style="line-height: 1.2;">Proworker labour</span>
            </a>
            <div class="list-group" id="main-nav">
                @include('partials.sidebar-menu')
            </div>
            </div>
        </aside>

        <main id="main-content" style="position: relative; z-index: 1;">
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
                </div>

                <div class="d-flex align-items-center ms-auto gap-2">
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            @switch(app()->getLocale())
                                @case('th') 🇹🇭 ไทย @break
                                @case('zh') 🇨🇳 中文 @break
                                @default 🇺🇸 English
                            @endswitch
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'th') }}">🇹🇭 ไทย (Thai)</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'en') }}">🇺🇸 English</a></li>
                            <li><a class="dropdown-item" href="{{ route('lang.switch', 'zh') }}">🇨🇳 中文 (Chinese)</a></li>
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

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/central-delete-handler.js'])
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
    @include('partials.view_selected_modal')
    @stack('scripts')

{{-- Global: Clean up stale Bootstrap modal backdrops that get stuck due to CSS display:none hack --}}
<script>
document.addEventListener('hidden.bs.modal', function() {
    // If no modal is currently shown, clean up all orphaned backdrops
    if (!document.querySelector('.modal.show')) {
        document.querySelectorAll('.modal-backdrop').forEach(function(el) { el.remove(); });
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }
});
</script>

<!-- Universal Preview Modal -->
<div class="modal fade" id="universalPreviewModal" tabindex="-1" aria-labelledby="universalPreviewModalLabel" aria-hidden="true" style="z-index: 1090;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="universalPreviewModalLabel">{{ __('Preview Data') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ __('Loading...') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- PDF Preview Modal -->
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="height: 90vh;">
        <div class="modal-content h-100">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfPreviewModalLabel">{{ __('PDF Preview') }}</h5>
                <div class="d-flex align-items-center gap-2">
                    <!-- Image Zoom Controls (Initially Hidden) -->
                    <div id="imageZoomControls" class="d-none btn-group btn-group-sm me-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(-0.1)" title="Zoom Out"><i class="bi bi-dash-lg"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="resetZoom()" title="Reset Zoom"><i class="bi bi-arrows-fullscreen"></i></button>
                        <button type="button" class="btn btn-outline-secondary" onclick="zoomImage(0.1)" title="Zoom In"><i class="bi bi-plus-lg"></i></button>
                    </div>

                    <a href="" id="pdfDownloadBtn" class="btn btn-sm btn-primary" download>
                        <i class="bi bi-download"></i> {{ __('Download') }}
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 h-100 overflow-hidden bg-dark position-relative">
                <iframe id="pdfPreviewFrame" src="" class="w-100 h-100" style="border: none;"></iframe>

                <!-- Image Preview Container -->
                <div id="imagePreviewContainer" class="d-none w-100 h-100 bg-dark" style="position: relative; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                    <div id="imageLoadingSpinner" class="spinner-border text-light position-absolute top-50 start-50 translate-middle" role="status" style="z-index: 5;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <!-- Image will be manipulated via CSS transform -->
                    <img id="imagePreview" src="" alt="Preview" style="max-width: 100%; max-height: 100%; object-fit: contain; box-shadow: 0 0 20px rgba(0,0,0,0.5); transform-origin: 0 0; transition: transform 0.1s ease-out;">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let imageZoomLevel = 1.0;
let isImageFitToScreen = true;

window.viewPDF = function(url, title = 'PDF Preview') {
    // Check if URL is valid
    if (!url) {
        console.error('viewPDF called with empty URL');
        return;
    }

    // Fallback if bootstrap is not loaded yet
    if (typeof bootstrap === 'undefined') {
        console.warn('Bootstrap not loaded, opening in new tab');
        window.open(url, '_blank');
        return;
    }

    const modalEl = document.getElementById('pdfPreviewModal');
    if (!modalEl) {
        console.error('PDF Preview Modal element not found');
        window.open(url, '_blank');
        return;
    }

    const iframe = document.getElementById('pdfPreviewFrame');
    const imageContainer = document.getElementById('imagePreviewContainer');
    const imagePreview = document.getElementById('imagePreview');
    const imageSpinner = document.getElementById('imageLoadingSpinner');
    const zoomControls = document.getElementById('imageZoomControls');
    const downloadBtn = document.getElementById('pdfDownloadBtn');
    const modalTitle = document.getElementById('pdfPreviewModalLabel');

    try {
        // Construct URL object to parse parts
        // Use window.location.origin as base for relative URLs
        const pdfUrl = new URL(url, window.location.origin);

        // Add disposition=inline for local URLs or routes
        // We check if hostname matches to allow protocol mismatches (http vs https behind proxy)
        if (pdfUrl.hostname === window.location.hostname) {
             pdfUrl.searchParams.set('disposition', 'inline');
        }

        if(modalTitle) modalTitle.textContent = title;
        if(downloadBtn) downloadBtn.href = url; // Keep original URL for download button

        // Detect if file is an image based on extension
        const pathname = pdfUrl.pathname.toLowerCase();
        const isImage = /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(pathname);

        if (isImage) {
            // Image Mode
            if(iframe) iframe.style.display = 'none';
            if(imageContainer) imageContainer.classList.remove('d-none');
            if (zoomControls) zoomControls.classList.remove('d-none');

            // Reset Zoom
            isImageFitToScreen = true;
            imageZoomLevel = 1.0;
            if (typeof updateImageStyle === 'function') updateImageStyle();

            // Load Image
            if(imageSpinner) imageSpinner.classList.remove('d-none');
            if(imagePreview) {
                imagePreview.style.opacity = '0.5';
                imagePreview.src = pdfUrl.toString();

                imagePreview.onload = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    imagePreview.style.opacity = '1';
                };
                imagePreview.onerror = function() {
                    if(imageSpinner) imageSpinner.classList.add('d-none');
                    console.error('Image failed to load');
                };
            }

        } else {
            // PDF / Other Mode
            if(iframe) {
                iframe.style.display = 'block';
                iframe.src = pdfUrl.toString();
            }
            if(imageContainer) imageContainer.classList.add('d-none');
            if (zoomControls) zoomControls.classList.add('d-none');
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

    } catch (e) {
        console.error('Error opening preview:', e);
        // Fallback to new tab
        window.open(url, '_blank');
    }
};

(function() {
    let isPanning = false;
    let startX = 0, startY = 0;
    let translateX = 0, translateY = 0;
    let panStartX = 0, panStartY = 0;

    // Set initial transform state
    window.updateImageStyle = function() {
        const img = document.getElementById('imagePreview');
        if (!img) return;

        if (isImageFitToScreen) {
            translateX = 0;
            translateY = 0;
            img.style.transform = `translate(0px, 0px) scale(1)`;
            img.style.cursor = 'zoom-in';
        } else {
            img.style.transform = `translate(${translateX}px, ${translateY}px) scale(${imageZoomLevel})`;
            img.style.cursor = isPanning ? 'grabbing' : 'grab';
        }
    };

    window.zoomImage = function(delta, mouseX = null, mouseY = null) {
        const img = document.getElementById('imagePreview');
        const imgContainer = document.getElementById('imagePreviewContainer');
        if (!img || !imgContainer) return;

        // Container bounding rect
        const rect = imgContainer.getBoundingClientRect();

        // If no mouse coordinates provided, zoom into the center of the container
        if (mouseX === null || mouseY === null) {
            mouseX = rect.width / 2;
            mouseY = rect.height / 2;
        }

        const oldZoomLevel = isImageFitToScreen ? 1.0 : imageZoomLevel;
        let newZoomLevel = oldZoomLevel + delta;

        if (newZoomLevel < 0.1) newZoomLevel = 0.1;
        if (newZoomLevel > 10.0) newZoomLevel = 10.0;

        // Prevent action if zoom limit reached
        if (newZoomLevel === oldZoomLevel) return;

        isImageFitToScreen = false;
        imageZoomLevel = newZoomLevel;

        // Image coordinates relative to the screen
        const imgRect = img.getBoundingClientRect();

        // Cursor position relative to the image's top-left corner (in screen pixels)
        const cursorX_relativeToImg = (rect.left + mouseX) - imgRect.left;
        const cursorY_relativeToImg = (rect.top + mouseY) - imgRect.top;

        // Calculate how much the pixel under the cursor moves due to scaling
        const ratio = newZoomLevel / oldZoomLevel;
        const shiftX = cursorX_relativeToImg * (1 - ratio);
        const shiftY = cursorY_relativeToImg * (1 - ratio);

        // Adjust translation to keep the pixel exactly under the cursor
        translateX += shiftX;
        translateY += shiftY;

        window.updateImageStyle();
    };

    window.resetZoom = function() {
        isImageFitToScreen = true;
        imageZoomLevel = 1.0;
        translateX = 0;
        translateY = 0;
        window.updateImageStyle();
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Prevent default pinch zoom on the page if container hovered
        document.body.addEventListener('wheel', function(e) {
            const imgContainer = document.getElementById('imagePreviewContainer');
            if (imgContainer && imgContainer.contains(e.target)) {
                if (e.ctrlKey || e.metaKey) {
                    e.preventDefault();
                    // Zoom amount
                    const delta = e.deltaY < 0 ? 0.2 : -0.2;

                    const rect = imgContainer.getBoundingClientRect();
                    const mouseX = e.clientX - rect.left;
                    const mouseY = e.clientY - rect.top;

                    window.zoomImage(delta, mouseX, mouseY);
                }
            }
        }, { passive: false });

        // Handle Panning
        document.body.addEventListener('mousedown', function(e) {
            const imgContainer = document.getElementById('imagePreviewContainer');
            if (imgContainer && imgContainer.contains(e.target)) {
                if (isImageFitToScreen) return;

                if (e.target.tagName === 'IMG') {
                    e.preventDefault(); // Prevent native image drag
                }

                isPanning = true;
                startX = e.clientX;
                startY = e.clientY;
                panStartX = translateX;
                panStartY = translateY;

                // Temporarily disable transition during drag for smoothness
                const img = document.getElementById('imagePreview');
                if (img) img.style.transition = 'none';

                window.updateImageStyle();
            }
        });

        // Use window for mouseup/mousemove so dragging doesn't break if mouse leaves container
        window.addEventListener('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                const img = document.getElementById('imagePreview');
                if (img) img.style.transition = 'transform 0.1s ease-out';
                window.updateImageStyle();
            }
        });

        window.addEventListener('mousemove', function(e) {
            if (!isPanning) return;
            e.preventDefault();

            // Calculate pixel movement
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;

            // Apply new translation
            translateX = panStartX + dx;
            translateY = panStartY + dy;

            window.updateImageStyle();
        });
    });
})();

// Full-Featured Address Management Script
document.addEventListener('DOMContentLoaded', function () {
    (async () => {
    // --- Configuration & Global State ---
    const thaiDataUrl = "/thai-addresses"; // Hardcoded URL
    let thaiAddressData = [];
    let dataLoaded = false;
    const addressModalEl = document.getElementById('addressModal');
    if (!addressModalEl) return; // Exit if the modal isn't on the page

    const addressModal = new bootstrap.Modal(addressModalEl);
    const addressForm = document.getElementById('addressForm');
    const saveBtn = document.getElementById('saveAddressBtn');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // --- Form Fields ---
    const fields = {
        id: document.getElementById('address_id'),
        addressable_id: document.getElementById('addressable_id'),
        addressable_type: document.getElementById('addressable_type'),
        type: document.getElementById('address_type'),
        addrNo: document.getElementById('addrNo'),
        addrNoEn: document.getElementById('addrNoEn'),
        addrMoo: document.getElementById('addrMoo'),
        addrMooEn: document.getElementById('addrMooEn'),
        addrSoi: document.getElementById('addrSoi'),
        addrSoiEn: document.getElementById('addrSoiEn'),
        addrRoad: document.getElementById('addrRoad'),
        addrRoadEn: document.getElementById('addrRoadEn'),
        addrProvince: document.getElementById('addrProvince'),
        addrProvinceEn: document.getElementById('addrProvinceEn'),
        addrDistrict: document.getElementById('addrDistrict'),
        addrDistrictEn: document.getElementById('addrDistrictEn'),
        addrSubDistrict: document.getElementById('addrSubDistrict'),
        addrSubDistrictEn: document.getElementById('addrSubDistrictEn'),
        addrZipCode: document.getElementById('addrZipCode')
    };

    await fetchThaiAddressData();

    // --- Data Loading ---
    async function fetchThaiAddressData() {
        if (dataLoaded) return;
        try {
            const response = await fetch(thaiDataUrl);
            if (!response.ok) throw new Error('Network response was not ok.');
            thaiAddressData = await response.json();
            dataLoaded = true;
            populateProvinces();
        } catch (error) {
            console.error('Failed to fetch Thai address data:', error);
            showToast('{{ __('Failed to load address data') }}', 'danger');
        }
    }

    // --- Dropdown Population ---
    function populateProvinces() {
        fields.addrProvince.innerHTML = '<option value="">{{ __('Select Province') }}</option>';
        const uniqueProvinces = [...new Map(thaiAddressData.map(item => [item['province_th'].trim(), item])).values()];
        uniqueProvinces.sort((a, b) => a.province_th.trim().localeCompare(b.province_th.trim(), 'th'));
        uniqueProvinces.forEach(item => {
            const option = new Option(item.province_th.trim(), item.province_th.trim());
            fields.addrProvince.add(option);
        });

        // Notify Alpine.js about options update
        fields.addrProvince.dispatchEvent(new Event('options-updated'));
    }

    function populateDistricts(province) {
        fields.addrDistrict.innerHTML = '<option value="">{{ __('Select District') }}</option>';
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>'; // Reset sub-districts
        fields.addrDistrict.disabled = true;
        fields.addrSubDistrict.disabled = true;

        if (!province) {
            // Trigger Alpine.js observer explicitly if returning early
            fields.addrDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const districts = [...new Set(thaiAddressData.filter(d => d.province_th.trim() === province.trim()).map(d => d.district_th.trim()))];
        districts.sort((a, b) => a.localeCompare(b, 'th'));
        districts.forEach(district => {
            const option = new Option(district, district);
            fields.addrDistrict.add(option);
        });

        if (districts.length > 0) {
            fields.addrDistrict.disabled = false;
        }

        // Notify Alpine.js about the new options and state change
        fields.addrDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrDistrict.dispatchEvent(new Event('change'));
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    function populateSubDistricts(province, district) {
        fields.addrSubDistrict.innerHTML = '<option value="">{{ __('Select Sub-district') }}</option>';
        fields.addrSubDistrict.disabled = true;

        if (!province || !district) {
            fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));
            return;
        }

        const subDistricts = thaiAddressData.filter(d => d.province_th.trim() === province.trim() && d.district_th.trim() === district.trim());
        const uniqueSubDistricts = [...new Map(subDistricts.map(item => [item['subdistrict_th'].trim(), item])).values()];
        uniqueSubDistricts.sort((a, b) => a.subdistrict_th.trim().localeCompare(b.subdistrict_th.trim(), 'th'));
        uniqueSubDistricts.forEach(sub => {
            const option = new Option(sub.subdistrict_th.trim(), sub.subdistrict_th.trim());
            fields.addrSubDistrict.add(option);
        });

        if (uniqueSubDistricts.length > 0) {
            fields.addrSubDistrict.disabled = false;
        }

        // Notify Alpine.js
        fields.addrSubDistrict.dispatchEvent(new Event('options-updated'));
        fields.addrSubDistrict.dispatchEvent(new Event('change'));
    }

    // --- Event Listeners for Dropdowns ---
    fields.addrProvince.addEventListener('change', function() {
        populateDistricts(this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === this.value.trim());
        fields.addrProvinceEn.value = selectedData ? selectedData.province_en.trim() : '';
        fields.addrDistrictEn.value = '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrDistrict.addEventListener('change', function() {
        populateSubDistricts(fields.addrProvince.value, this.value);
        const selectedData = thaiAddressData.find(d => d.province_th.trim() === fields.addrProvince.value.trim() && d.district_th.trim() === this.value.trim());
        fields.addrDistrictEn.value = selectedData ? selectedData.district_en.trim() : '';
        fields.addrSubDistrictEn.value = '';
        fields.addrZipCode.value = '';
    });

    fields.addrSubDistrict.addEventListener('change', function() {
        const selectedData = thaiAddressData.find(d =>
            d.province_th.trim() === fields.addrProvince.value.trim() &&
            d.district_th.trim() === fields.addrDistrict.value.trim() &&
            d.subdistrict_th.trim() === this.value.trim()
        );
        if (selectedData) {
            fields.addrSubDistrictEn.value = selectedData.subdistrict_en.trim();
            fields.addrZipCode.value = selectedData.zip_code ? String(selectedData.zip_code).trim() : '';
        }
    });

    // --- Modal Opening Logic ---
    addressModalEl.addEventListener('show.bs.modal', function(e) {
        const button = e.relatedTarget;
        if (!button) return;

        const isAddButton = button.matches('.add-address-btn') || button.matches('.temp-edit-address-btn') === false && button.closest('.add-address-btn');
        const isEditButton = button.matches('.edit-address-btn') || button.closest('.edit-address-btn');

        // Identify the actual button element in case a child element (like an icon) was clicked
        const targetBtn = isAddButton ? (button.matches('.add-address-btn') ? button : button.closest('.add-address-btn')) :
                          isEditButton ? (button.matches('.edit-address-btn') ? button : button.closest('.edit-address-btn')) : null;

        if (isAddButton && targetBtn) {
            addressForm.reset();
            fields.id.value = '';
            fields.addressable_id.value = targetBtn.dataset.addressableId;
            if (targetBtn.dataset.addressableType) {
                fields.addressable_type.value = targetBtn.dataset.addressableType;
            } else {
                fields.addressable_type.value = 'App\\Models\\Employer';
            }
            fields.type.value = targetBtn.dataset.type;

            // Re-trigger Alpine's reactivity by firing a change event
            fields.addrProvince.dispatchEvent(new Event('change'));
            fields.addrDistrict.dispatchEvent(new Event('change'));
            fields.addrSubDistrict.dispatchEvent(new Event('change'));

            document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
        } else if (isEditButton && targetBtn) {
            const addressId = targetBtn.dataset.addressId;
            document.getElementById('addressModalLabel').textContent = '{{ __('Loading...') }}';

            // Disable save button while loading
            saveBtn.disabled = true;

            // Perform fetch without blocking the modal rendering (no await in the event listener)
            fetch(`/addresses/${addressId}/edit`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to fetch address data.');
                    return response.json();
                })
                .then(data => {
                    addressForm.reset();
                    for (const key in data) {
                        if (fields[key]) {
                           fields[key].value = data[key];
                        }
                    }

                    populateDistricts(data.addrProvince);
                    fields.addrDistrict.value = data.addrDistrict;

                    populateSubDistricts(data.addrProvince, data.addrDistrict);
                    fields.addrSubDistrict.value = data.addrSubDistrict;

                    // Trigger Alpine.js to re-read values
                    fields.addrProvince.dispatchEvent(new Event('change'));
                    fields.addrDistrict.dispatchEvent(new Event('change'));
                    fields.addrSubDistrict.dispatchEvent(new Event('change'));

                    document.getElementById('addressModalLabel').textContent = '{{ __('Edit Address') }}';
                })
                .catch(error => {
                    console.error('Error fetching address for edit:', error);
                    showToast('{{ __('Failed to fetch address data') }}', 'danger');
                    addressModal.hide();
                })
                .finally(() => {
                    saveBtn.disabled = false;
                });
        }
    });

    // --- Save Logic ---
    saveBtn.addEventListener('click', async function() {
        const formData = new FormData(addressForm);
        const addressId = fields.id.value;
        const url = addressId ? `/addresses/${addressId}` : '/addresses';
        const method = 'POST';

        if (addressId) {
            formData.append('_method', 'PUT');
        }

        try {
            saveBtn.disabled = true;
            saveBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> {{ __('Saving...') }}`;

            const response = await fetch(url, {
                method: method,
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (!response.ok) {
                if (response.status === 422 && result.errors) {
                     let errorMsg = Object.values(result.errors).flat().join('\\n');
                     throw new Error(errorMsg);
                }
                throw new Error(result.message || '{{ __('An unknown error occurred') }}');
            }

            showToast(result.message || '{{ __('Address saved successfully') }}', 'success');
            addressModal.hide();

            setTimeout(() => location.reload(), 1500);

        } catch (error) {
            console.error('Save address error:', error);
            showToast(error.message || '{{ __('Error saving address') }}', 'danger');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '{{ __('Save') }}';
        }
    });

    addressModalEl.addEventListener('hidden.bs.modal', function () {
        addressForm.reset();
        populateDistricts('');
        document.getElementById('addressModalLabel').textContent = '{{ __('Add New Address') }}';
    });
    })();
});
</script>

<script>
// --- Global Selection Manager (Persists across pages/searches) ---
const STORAGE_KEY = 'selectedEmployeeData';

// Helper: Get all stored data
window.getGlobalSelectedData = function() {
    const stored = sessionStorage.getItem(STORAGE_KEY);
    try {
        return stored ? JSON.parse(stored) : [];
    } catch (e) {
        console.error('Error parsing selectedEmployeeData', e);
        return [];
    }
};

// Helper: Get just IDs sorted by selection order
window.getGlobalSelectedIds = function() {
    return window.getGlobalSelectedData()
        .sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0))
        .map(item => item.id);
};

document.addEventListener('DOMContentLoaded', function () {
    // Stores array of objects: { id: "1", employer_id: "5", selection_order: 1 }
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');
    const bulkActionBar = document.querySelector('.bulk-action-bar');
    const selectedCountSpan = document.getElementById('selected-count');
    const bulkActionButton = bulkActionBar ? bulkActionBar.querySelector('button') : null;

    // Counter for tracking selection order
    window._selectionOrderCounter = (function() {
        const existing = window.getGlobalSelectedData();
        let maxOrder = 0;
        existing.forEach(item => {
            if (item.selection_order && item.selection_order > maxOrder) {
                maxOrder = item.selection_order;
            }
        });
        return maxOrder;
    })();

    // Helper: Save data
    window.setGlobalSelectedData = function(data) {
        // Ensure uniqueness by ID
        const unique = [];
        const map = new Map();
        for (const item of data) {
            if(!map.has(String(item.id))) {
                map.set(String(item.id), true);
                unique.push(item);
            }
        }
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(unique));
        updateUI();
    };

    // Helper: Add items (accepts array of {id, employer_id})
    function addItems(newItems) {
        const current = window.getGlobalSelectedData();
        // Filter out existing items that are being re-added (to update them with potentially newer data)
        const newIds = newItems.map(i => String(i.id));
        const currentFiltered = current.filter(i => !newIds.includes(String(i.id)));
        // Assign selection_order to new items that don't have one
        newItems.forEach(item => {
            if (!item.selection_order) {
                window._selectionOrderCounter++;
                item.selection_order = window._selectionOrderCounter;
            }
        });
        const combined = [...currentFiltered, ...newItems];
        window.setGlobalSelectedData(combined);
    }

    // Helper: Remove items by ID
    function removeItemsByIds(idsToRemove) {
        const current = window.getGlobalSelectedData();
        const filtered = current.filter(item => !idsToRemove.includes(String(item.id)));
        window.setGlobalSelectedData(filtered);
    }
    window.removeItemsByIds = removeItemsByIds;

    // Clear all selections
    window.clearGlobalSelection = function() {
        sessionStorage.removeItem(STORAGE_KEY);
        // Uncheck all visible
        document.querySelectorAll('.employee-checkbox').forEach(cb => cb.checked = false);
        if(selectAllCheckbox) selectAllCheckbox.checked = false;
        updateUI();
    };

    // UI Updater
    function updateUI() {
        const allData = window.getGlobalSelectedData();
        const count = allData.length;
        const allIds = allData.map(item => String(item.id));

        if (bulkActionBar) {
            if (count > 0) {
                bulkActionBar.style.display = 'flex';
                if (selectedCountSpan) selectedCountSpan.textContent = count;
                if (bulkActionButton) bulkActionButton.disabled = false;
            } else {
                bulkActionBar.style.display = 'none';
                if (bulkActionButton) bulkActionButton.disabled = true;
            }
        }

        // Update View Selected Button Count (Global Badge)
        const viewSelectedBadge = document.getElementById('view-selected-count');
        if (viewSelectedBadge) viewSelectedBadge.textContent = count;

        // Sync individual checkboxes dynamically across the page
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        let visibleCount = 0;
        let visibleCheckedCount = 0;

        currentCheckboxes.forEach(cb => {
            cb.checked = allIds.includes(String(cb.value));

            // Check if it's visible to determine "Select All" state
            const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
            const accordionCollapse = cb.closest('.accordion-collapse');
            const isHiddenByAccordion = accordionCollapse && !accordionCollapse.classList.contains('show');
            const isHidden = (cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none')) || isHiddenByAccordion;

            if (!isHidden) {
                visibleCount++;
                if (cb.checked) {
                    visibleCheckedCount++;
                }
            }
        });

        // Sync "Select All" checkbox state based on VISIBLE items
        if (selectAllCheckbox) {
            if (visibleCount > 0 && visibleCheckedCount === visibleCount) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (visibleCheckedCount > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }

        // Also fire a custom event so other components (like employer-select-all) can update
        document.dispatchEvent(new Event('global-selection-updated'));
    }

    // --- Initialization ---

    // Note: We use Event Delegation for the individual checkboxes to support dynamic content (AJAX)
    // The 'employeeCheckboxes' variable defined above is static. We will re-query it when needed inside updateUI.

    // Helper to extract rich data
    function getEmployeeData(cb) {
        return {
            id: cb.value,
            employer_id: cb.dataset.employerId || '',
            name_th: cb.dataset.nameTh || '',
            name_en: cb.dataset.nameEn || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            photo: cb.dataset.photo || '',
            employer_name: cb.dataset.employerName || '',
            title_th: cb.dataset.titleTh || '',
            title_en: cb.dataset.titleEn || '',
            nationality: cb.dataset.nationality || '',
            country_code: cb.dataset.countryCode || '',
            gender: cb.dataset.gender || '',
            insurance_type: cb.dataset.insuranceType || '',
            passport: cb.dataset.passport || '',
            production_item_id: cb.dataset.productionItemId || ''
        };
    }

    // Expose UI refresh globally (for AJAX loaded content)
    window.refreshGlobalSelectionUI = function() {
        const savedIds = window.getGlobalSelectedIds();
        // Re-query checkboxes to include newly added ones
        const currentCheckboxes = document.querySelectorAll('.employee-checkbox');
        currentCheckboxes.forEach(cb => {
            if (savedIds.includes(String(cb.value))) {
                cb.checked = true;
            } else {
                cb.checked = false; // Ensure unchecked if not in state
            }
        });
        updateUI();
    };

    // 1. Restore state from storage on load
    window.refreshGlobalSelectionUI();

    // 2. Handle Individual Checkbox Changes (Event Delegation)
    document.body.addEventListener('change', function (e) {
        if (e.target.matches('.employee-checkbox')) {
            const checkbox = e.target;
            const data = getEmployeeData(checkbox);
            if (checkbox.checked) {
                addItems([data]);
            } else {
                removeItemsByIds([data.id]);
            }
        }
    });

    // 2.5 ═══ Selection Mode — Click empty card surface to toggle checkbox ═══
    // After user manually checks the FIRST checkbox, clicking on an empty
    // (non-interactive) part of another employee card toggles its selection.
    // Clicks on buttons, links, inputs, badges, drag handles, Alpine @click
    // bindings, etc. are explicitly excluded so they perform their own action
    // without ALSO selecting the row.
    (function() {
        let mouseDownPos = null;
        const DRAG_THRESHOLD = 6; // px — anything beyond this is treated as a drag, not a click

        // CSS selectors for elements that should NEVER trigger a card toggle.
        const INTERACTIVE_SELECTOR = [
            'a', 'button', 'input', 'textarea', 'select', 'label',
            '[contenteditable]', '[role="button"]', '[role="link"]',
            '[onclick]', '[draggable="true"]',
            '[data-bs-toggle]', '[data-bs-dismiss]', '[data-bs-target]',
            '.form-check-input',
            '.btn', '.btn-group',
            '.dropdown', '.dropdown-toggle', '.dropdown-menu', '.dropdown-item',
            '.swal2-container', '.modal',
            '.accordion-button',
            '.cursor-pointer', '.cursor-grab',
            '.btn-preview',
            'img', '.badge'
        ].join(', ');

        // Alpine.js (and similar libraries) attach click handlers via attributes
        // such as @click, @click.stop, x-on:click, x-on:click.away, etc.
        // CSS attribute selectors can't match attribute-name prefixes so we
        // walk the ancestor chain and inspect attribute names manually.
        function hasAlpineClickHandler(el) {
            let n = el;
            while (n && n.nodeType === 1 && n !== document.body) {
                const attrs = n.attributes;
                for (let i = 0; i < attrs.length; i++) {
                    const name = attrs[i].name;
                    if (name.startsWith('@click') || name.startsWith('x-on:click')) {
                        return true;
                    }
                }
                n = n.parentElement;
            }
            return false;
        }

        function isInSelectionMode() {
            return (window.getGlobalSelectedData?.() || []).length > 0;
        }

        // Walk up from click target to find the smallest containing element
        // that has exactly one .employee-checkbox descendant — that's the card.
        function findCard(target) {
            let el = target;
            let safety = 0;
            while (el && el !== document.body && safety++ < 20) {
                // Looking for the immediate ancestor that contains an .employee-checkbox
                const cb = el.querySelector('.employee-checkbox');
                if (cb) {
                    // Make sure this is the only checkbox under this element
                    // (otherwise we'd be matching the page-level container)
                    const allCbs = el.querySelectorAll('.employee-checkbox');
                    if (allCbs.length === 1) {
                        return { card: el, checkbox: cb };
                    }
                    // If multiple, we've gone too far up — return null
                    return null;
                }
                el = el.parentElement;
            }
            return null;
        }

        function recordMouseDown(e) {
            if (e.button !== 0) return;
            mouseDownPos = { x: e.clientX, y: e.clientY };
        }

        function handleCardClick(e) {
            if (!isInSelectionMode()) return;

            // Skip if the click was on (or inside) any interactive element.
            if (e.target.closest(INTERACTIVE_SELECTOR)) return;

            // Skip if any ancestor has an Alpine @click / x-on:click handler.
            if (hasAlpineClickHandler(e.target)) return;

            // Skip if some inner handler already handled this click
            // (e.g. preventDefault'd or stopPropagation'd at a deeper level).
            if (e.defaultPrevented) return;

            // Find the card by walking up from the click target
            const result = findCard(e.target);
            if (!result) return;
            const { checkbox } = result;

            // Skip cancelled cards (employees in cancelled state shouldn't be selectable here)
            if (checkbox.disabled || checkbox.closest('.d-none, [style*="display: none"], [style*="display:none"]')) return;

            // Skip if user was dragging (text selection)
            if (mouseDownPos) {
                const dx = e.clientX - mouseDownPos.x;
                const dy = e.clientY - mouseDownPos.y;
                if (Math.sqrt(dx * dx + dy * dy) > DRAG_THRESHOLD) return;
            }

            // Skip if user has selected text (don't break copy)
            const selection = window.getSelection();
            if (selection && selection.toString().trim().length > 0) return;

            // Toggle the checkbox
            e.preventDefault();
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        }

        document.body.addEventListener('mousedown', recordMouseDown, true);
        document.body.addEventListener('touchstart', function(e) {
            if (e.touches && e.touches[0]) {
                mouseDownPos = { x: e.touches[0].clientX, y: e.touches[0].clientY };
            }
        }, { passive: true });

        document.body.addEventListener('click', handleCardClick);
    })();

    // 3. Handle "Select All" Checkbox
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            // Re-query currently visible checkboxes
            const currentCheckboxes = document.querySelectorAll('.employee-checkbox');

            const visibleCheckboxes = Array.from(currentCheckboxes).filter(cb => {
                const cardWrapper = cb.closest('.item-card-wrapper') || cb.closest('.employee-card-wrapper') || cb.closest('tr');
                const accordionCollapse = cb.closest('.accordion-collapse');
                const isHiddenByAccordion = accordionCollapse && !accordionCollapse.classList.contains('show');
                const isHidden = (cardWrapper && (cardWrapper.classList.contains('d-none') || cardWrapper.classList.contains('hide-cancelled') || cardWrapper.style.display === 'none')) || isHiddenByAccordion;
                return !isHidden;
            });

            const visibleItems = visibleCheckboxes.map((cb, index) => {
                const data = getEmployeeData(cb);
                // Select All: assign order based on DOM position (1-based)
                data.selection_order = index + 1;
                return data;
            });
            const visibleIds = visibleItems.map(item => item.id);

            if (this.checked) {
                // Check all visible and add to storage
                // Reset counter since Select All replaces all selections
                window._selectionOrderCounter = visibleItems.length;
                visibleCheckboxes.forEach(cb => cb.checked = true);
                // Clear existing and set fresh with DOM order
                const currentData = window.getGlobalSelectedData();
                const nonVisibleData = currentData.filter(i => !visibleIds.includes(String(i.id)));
                window.setGlobalSelectedData([...nonVisibleData, ...visibleItems]);
            } else {
                // Uncheck all visible and remove from storage
                visibleCheckboxes.forEach(cb => cb.checked = false);
                removeItemsByIds(visibleIds);
            }

            // Trigger custom event for specific employers
            document.dispatchEvent(new Event('global-selection-updated'));
        });
    }

    // --- New Feature: View Selected Modal Logic ---
    window.openViewSelectedModal = function(customData = null, customTitle = null, customStorageKey = null) {
        const data = customData || window.getGlobalSelectedData();
        const container = document.getElementById('selected-items-container');
        const countBadge = document.getElementById('modal-selected-count');
        const modalEl = document.getElementById('viewSelectedModal');
        const titleEl = document.getElementById('viewSelectedModalLabel');

        if (!container || !modalEl) {
            console.error('Modal elements not found');
            return;
        }

        if (customTitle && titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>${customTitle}`;
        } else if (titleEl) {
            titleEl.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i>{{ __('Selected Employees') }}`;
        }

        if (countBadge) countBadge.textContent = data.length;

        container.innerHTML = ''; // Clear

        if (data.length === 0) {
            container.innerHTML = `
                <div class="col-12 text-center py-5 text-muted w-100">
                    <i class="bi bi-check2-circle display-1 opacity-25"></i>
                    <p class="mt-3">{{ __('No employees selected') }}</p>
                </div>
            `;
        } else {
            // Sort by selection order before displaying
            const sortedData = [...data].sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0));
            // When customStorageKey is set (employer page), each item is an employer
            // and item.id IS the employer id. Otherwise items are employees with item.id
            // being the employee id and item.employer_id being the employer id.
            const isEmployerMode = !!customStorageKey;
            sortedData.forEach((item, index) => {
                const orderNum = index + 1;
                const titleTh = item.title_th || '';
                const nameTh = item.name_th || '-';
                const fullNameTh = titleTh + ' ' + nameTh;

                const titleEn = item.title_en || '';
                const nameEn = item.name_en || '-';
                const fullNameEn = titleEn + ' ' + nameEn;

                const nationality = item.nationality || '-';
                const employer = item.employer_name || '-';
                const photo = item.photo || 'https://placehold.co/50x50/e2e8f0/6c757d?text=PIC';

                // Preview buttons — wired to the global universalPreviewModal
                // via the .btn-preview event-delegated handler in app.js.
                let namePreviewBtn = '';
                let employerPreviewBtn = '';
                if (isEmployerMode) {
                    // In employer mode the row itself is an employer, attach the
                    // preview to the name line so the magnifying glass is visible.
                    namePreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employer" data-model-id="${item.id}" title="{{ __('Preview Data') }}"><i class="bi bi-search"></i></button>`;
                } else {
                    namePreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employee" data-model-id="${item.id}" title="{{ __('Preview Employee') }}"><i class="bi bi-search"></i></button>`;
                    if (item.employer_id) {
                        employerPreviewBtn = `<button type="button" class="btn btn-sm btn-outline-info btn-preview p-0 border-0 bg-transparent ms-1" data-model-type="employer" data-model-id="${item.employer_id}" title="{{ __('Preview Employer') }}"><i class="bi bi-search"></i></button>`;
                    }
                }

                const cardHtml = `
                    <div class="col" id="modal-item-${item.id}">
                        <div class="card h-100 shadow-sm border position-relative hover-shadow transition-all selected-item-card"
                             draggable="true"
                             data-item-id="${item.id}"
                             style="cursor: grab; user-select: none;">
                            <span class="position-absolute top-0 start-0 m-2 badge bg-primary rounded-pill shadow-sm modal-item-order" style="z-index: 5; font-size: 0.75rem;">${orderNum}</span>
                            <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 rounded-circle shadow-sm bg-white"
                                    draggable="false"
                                    onclick="window.removeSelectedItemFromModal('${item.id}', '${customStorageKey || ''}')"
                                    title="{{ __('Remove') }}" style="width: 28px; height: 28px; padding: 0; z-index: 5;">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <div class="card-body d-flex align-items-center gap-3 p-3">
                                <div class="flex-shrink-0">
                                    <img src="${photo}" class="rounded-circle shadow-sm border" width="60" height="60" style="object-fit: cover;" draggable="false">
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-1" title="${fullNameEn}">
                                        <span class="text-truncate">${fullNameEn}</span>${namePreviewBtn}
                                    </div>
                                    <div class="text-muted small text-truncate" title="${fullNameTh}">${fullNameTh}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <span class="badge bg-light text-dark border"><i class="bi bi-flag me-1"></i>${nationality}</span>
                                    </div>
                                    <div class="small text-primary mt-1 d-flex align-items-center gap-1" title="${employer}">
                                        <i class="bi bi-building"></i><span class="text-truncate">${employer}</span>${employerPreviewBtn}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += cardHtml;
            });

            // --- Drag-and-drop reorder ---
            // Cards are rebuilt every render, so we attach listeners fresh each time.
            window._setupSelectedItemsDnD(container, customData, customTitle, customStorageKey);
        }

        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    };

    // Internal helper exposed only because openViewSelectedModal is a window.* function
    // and re-rendering after a drop calls it back through the window scope.
    window._setupSelectedItemsDnD = function(container, customData, customTitle, customStorageKey) {
        let draggedId = null;

        const clearIndicators = () => {
            container.querySelectorAll('.drag-over-before, .drag-over-after').forEach(el => {
                el.classList.remove('drag-over-before', 'drag-over-after');
            });
        };

        const cards = container.querySelectorAll('.selected-item-card');
        cards.forEach(card => {
            card.addEventListener('dragstart', (e) => {
                // Don't start drag from interactive children (the X / preview buttons
                // already have draggable="false" but be defensive).
                if (e.target.closest('button, a, .btn-preview')) {
                    e.preventDefault();
                    return;
                }
                draggedId = card.dataset.itemId;
                card.style.opacity = '0.4';
                e.dataTransfer.effectAllowed = 'move';
                // Required for Firefox to actually start a drag
                try { e.dataTransfer.setData('text/plain', draggedId); } catch (_) {}
            });

            card.addEventListener('dragend', () => {
                card.style.opacity = '';
                clearIndicators();
                draggedId = null;
            });

            card.addEventListener('dragover', (e) => {
                if (!draggedId || draggedId === card.dataset.itemId) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                clearIndicators();
                const rect = card.getBoundingClientRect();
                const before = (e.clientY - rect.top) < (rect.height / 2);
                card.classList.add(before ? 'drag-over-before' : 'drag-over-after');
            });

            card.addEventListener('dragleave', (e) => {
                // Only clear if leaving the card entirely
                if (!card.contains(e.relatedTarget)) {
                    card.classList.remove('drag-over-before', 'drag-over-after');
                }
            });

            card.addEventListener('drop', (e) => {
                e.preventDefault();
                if (!draggedId || draggedId === card.dataset.itemId) {
                    clearIndicators();
                    return;
                }
                const rect = card.getBoundingClientRect();
                const dropBefore = (e.clientY - rect.top) < (rect.height / 2);
                window._reorderSelectedItems(draggedId, card.dataset.itemId, dropBefore, customData, customTitle, customStorageKey);
                clearIndicators();
            });
        });
    };

    window._reorderSelectedItems = function(draggedId, targetId, dropBefore, customData, customTitle, customStorageKey) {
        // Pull the freshest copy from the source of truth — sessionStorage for
        // custom mode, the global selection for default mode — so concurrent
        // ticking elsewhere doesn't get stomped.
        let data = [];
        if (customStorageKey) {
            try {
                const stored = sessionStorage.getItem(customStorageKey);
                data = stored ? JSON.parse(stored) : (customData || []);
            } catch (_) {
                data = customData || [];
            }
        } else {
            data = window.getGlobalSelectedData();
        }

        // Sort by current selection_order so splicing reflects the visible order
        data.sort((a, b) => (a.selection_order || 0) - (b.selection_order || 0));

        const fromIdx = data.findIndex(i => String(i.id) === String(draggedId));
        if (fromIdx === -1) return;
        const draggedItem = data.splice(fromIdx, 1)[0];

        let toIdx = data.findIndex(i => String(i.id) === String(targetId));
        if (toIdx === -1) {
            data.push(draggedItem);
        } else {
            if (!dropBefore) toIdx++;
            data.splice(toIdx, 0, draggedItem);
        }

        // Re-number selection_order so future renders stay consistent
        data.forEach((it, idx) => { it.selection_order = idx + 1; });

        // Persist
        if (customStorageKey) {
            sessionStorage.setItem(customStorageKey, JSON.stringify(data));
            window.dispatchEvent(new CustomEvent('custom-selection-changed', { detail: { key: customStorageKey } }));
        } else {
            window.setGlobalSelectedData(data);
            if (window.refreshGlobalSelectionUI) window.refreshGlobalSelectionUI();
        }

        // Re-render the modal in place. Pass customData=data to keep the modal
        // working off the freshly reordered array even in employer/custom mode.
        window.openViewSelectedModal(customStorageKey ? data : null, customTitle, customStorageKey);
    };

    window.removeSelectedItemFromModal = function(id, customStorageKey = null) {
        // removeItemsByIds is defined inside DOMContentLoaded scope previously, so it's not global.
        // We need to access the logic. But wait, `removeItemsByIds` was defined inside the closure.
        // We need to expose it or reimplement it.
        // Let's reimplement a simple version here relying on `getGlobalSelectedData` and `setGlobalSelectedData` which ARE global.

        let current = [];
        let key = STORAGE_KEY;

        if (customStorageKey) {
            key = customStorageKey;
            try {
                const stored = sessionStorage.getItem(key);
                current = stored ? JSON.parse(stored) : [];
            } catch (e) {
                console.error('Error parsing custom storage', e);
                current = [];
            }
        } else {
            current = window.getGlobalSelectedData();
        }

        const filtered = current.filter(item => String(item.id) !== String(id));

        if (customStorageKey) {
            sessionStorage.setItem(key, JSON.stringify(filtered));
            // Trigger a custom event for local UI updates
            window.dispatchEvent(new CustomEvent('custom-selection-changed', { detail: { key: key } }));
        } else {
            window.setGlobalSelectedData(filtered);
            // Sync main UI (Checkboxes)
            if(window.refreshGlobalSelectionUI) {
                window.refreshGlobalSelectionUI();
            }
        }

        // Remove from DOM immediately
        const el = document.getElementById(`modal-item-${id}`);
        if(el) {
            el.remove();
        }

        // Update Modal Count
        const countBadge = document.getElementById('modal-selected-count');
        if (countBadge) countBadge.textContent = filtered.length;

        // If empty, show empty state
        if (filtered.length === 0) {
            const container = document.getElementById('selected-items-container');
            if (container) {
                container.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted w-100">
                        <i class="bi bi-check2-circle display-1 opacity-25"></i>
                        <p class="mt-3">{{ __('No employees selected') }}</p>
                    </div>
                `;
            }
            // Optional: Close modal if empty? No, user might want to see it cleared.
        }
    };
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.body.addEventListener('submit', function(e) {
        if (e.target.matches('.delete-employee-form')) {
            e.preventDefault();
            const form = e.target;

            Swal.fire({
                title: '{{ __('Are you sure?') }}',
                text: "{{ __('This will move the employee to the Central Trash. You can recover them later.') }}",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '{{ __('Yes, move to Trash!') }}',
                cancelButtonText: '{{ __('Cancel') }}'
            }).then((result) => {
                if (result.isConfirmed) {
                    const action = form.getAttribute('action');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                    fetch(action, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: new FormData(form)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                             window.location.reload();
                        } else {
                            showToast(data.message || '{{ __('An error occurred while trying to delete the employee.') }}', 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Delete Error:', error);
                        showToast('{{ __('A network error occurred. Please try again.') }}', 'danger');
                    });
                }
            });
        }
    });
});
</script>

<!-- Scroll to Top Buttons -->
<div class="scroll-to-top left" id="scrollToTopLeft"><i class="bi bi-chevron-up"></i></div>
<div class="scroll-to-top right" id="scrollToTopRight"><i class="bi bi-chevron-up"></i></div>

<!-- Side Drawer Handle -->
<div id="drawer-handle" class="drawer-handle" title="Open Menu">
    <i class="bi bi-chevron-right"></i>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- Scroll to Top Logic ---
    const scrollToTopLeft = document.getElementById('scrollToTopLeft');
    const scrollToTopRight = document.getElementById('scrollToTopRight');

    function checkScroll() {
        if (window.scrollY > 200) {
            scrollToTopLeft.classList.add('show');
            scrollToTopRight.classList.add('show');
        } else {
            scrollToTopLeft.classList.remove('show');
            scrollToTopRight.classList.remove('show');
        }
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    window.addEventListener('scroll', checkScroll);
    scrollToTopLeft.addEventListener('click', scrollToTop);
    scrollToTopRight.addEventListener('click', scrollToTop);

    // --- Drawer Handle Logic ---
    const drawerHandle = document.getElementById('drawer-handle');
    const sidebarElement = document.getElementById('sidebar');

    if (drawerHandle && sidebarElement) {
        // Show handle when scrolled down
        window.addEventListener('scroll', function() {
            // Check if sidebar is currently open to avoid showing handle over it (though CSS z-index/transform handles visibility mostly)
            const isSidebarOpen = sidebarElement.classList.contains('show');

            if (window.scrollY > 100 && !isSidebarOpen) {
                drawerHandle.classList.add('show');
            } else {
                drawerHandle.classList.remove('show');
            }
        });

        // Open sidebar on click
        drawerHandle.addEventListener('click', function() {
            const bsOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(sidebarElement);
            bsOffcanvas.show();
        });

        // Hide handle when sidebar is open
        sidebarElement.addEventListener('show.bs.offcanvas', function () {
            drawerHandle.classList.remove('show');
        });

        // Re-check scroll position when sidebar closes
        sidebarElement.addEventListener('hidden.bs.offcanvas', function () {
            if (window.scrollY > 100) {
                drawerHandle.classList.add('show');
            }
        });
    }
});
</script>

<script>
    // Global Logout Handler for SweetAlert
    document.addEventListener('DOMContentLoaded', function() {
        const logoutBtn = document.getElementById('btn-logout');
        if(logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                Swal.fire({
                    title: '{{ __('Ready to Leave?') }}',
                    text: "{{ __('Select "Logout" below if you are ready to end your current session.') }}",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#F97316',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: '{{ __('Logout') }}',
                    cancelButtonText: '{{ __('Cancel') }}'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('logout-form').submit();
                    }
                });
            });
        }
    });

    // Global Highlight & Scroll Handler
    document.addEventListener('DOMContentLoaded', function() {
        if (window.location.hash) {
            // Decouple from execution flow to ensure DOM is fully ready
            setTimeout(() => {
                const id = window.location.hash.substring(1);
                const el = document.getElementById(id);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    el.classList.add('highlight');
                    setTimeout(() => {
                        el.classList.remove('highlight');
                    }, 5000);
                }
            }, 300);
        }
    });
</script>

<script>
    // ═══ Resolution Badge Auto-Fit ═══
    // Auto-shrinks font size so long tab names fit within the uniform-size badge
    window.fitResolutionBadges = function(root = document) {
        const badges = root.querySelectorAll('.resolution-badge .rb-text');
        const maxFont = 13; // px
        const minFont = 9;  // px
        badges.forEach(el => {
            // Reset to max before measuring
            let size = maxFont;
            el.style.fontSize = size + 'px';
            // Shrink until content fits
            let safety = 20;
            while (safety-- > 0 && (el.scrollHeight > el.clientHeight + 1 || el.scrollWidth > el.clientWidth + 1) && size > minFont) {
                size -= 0.5;
                el.style.fontSize = size + 'px';
            }
        });
    };

    // Run on page load
    document.addEventListener('DOMContentLoaded', () => window.fitResolutionBadges());

    // Run again when window resizes (size changes between mobile/desktop)
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => window.fitResolutionBadges(), 150);
    });

    // Observe DOM for dynamically added badges (AJAX content)
    const badgeObserver = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (node.nodeType === 1 && (node.classList?.contains('resolution-badge') || node.querySelector?.('.resolution-badge'))) {
                    window.fitResolutionBadges(node.parentNode || document);
                    return;
                }
            }
        }
    });
    badgeObserver.observe(document.body, { childList: true, subtree: true });
</script>

</body>
</html>
