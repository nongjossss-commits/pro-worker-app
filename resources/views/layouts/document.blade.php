<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>@yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: #333;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            margin: 10mm auto;
            border: 1px solid #d3d3d3;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 { margin: 0; font-weight: 700; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: #6b7280; }
        .text-primary { color: #2563eb; }
        .text-danger { color: #dc2626; }
        .text-sm { font-size: 0.875rem; }
        .font-bold { font-weight: bold; }

        /* Layout Utilities */
        .flex { display: flex; }
        .justify-between { justify-content: space-between; }
        .items-center { align-items: center; }
        .items-end { align-items: flex-end; }
        .mb-1 { margin-bottom: 0.25rem; }
        .mb-2 { margin-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 1rem; }
        .mb-8 { margin-bottom: 2rem; }
        .mt-4 { margin-top: 1rem; }
        .mt-8 { margin-top: 2rem; }
        .p-4 { padding: 1rem; }
        .border-b { border-bottom: 1px solid #e5e7eb; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        th, td { padding: 0.75rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: top; }
        th { background-color: #f9fafb; font-weight: 600; font-size: 0.875rem; color: #374151; }
        td.amount { text-align: right; font-variant-numeric: tabular-nums; }

        /* Components */
        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.25rem;
            color: #fff;
            background-color: #6b7280;
        }
        .badge-success { background-color: #10b981; }

        /* Print Styles */
        @media print {
            body { background: white; }
            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                padding: 0; /* Let printer margins handle it or keep for safe zone */
            }
            .no-print { display: none; }
        }

        /* Helpers */
        .logo-placeholder {
            width: 80px; height: 80px; background: #e5e7eb; border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            color: #9ca3af; font-size: 0.75rem;
        }
    </style>
</head>
<body>

    <div class="no-print" style="position: fixed; top: 20px; right: 20px; z-index: 9999;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
            🖨️ Print / Save as PDF
        </button>
    </div>

    <div class="page">
        <!-- Company Header -->
        <div class="flex justify-between mb-8">
            <div class="flex">
                @if($company && $company->logo)
                    <img src="{{ asset('storage/' . $company->logo) }}" alt="Logo" style="width: 80px; height: auto; max-height: 80px;" class="mr-4">
                @else
                    <div class="logo-placeholder mr-4">Logo</div>
                @endif
                <div style="margin-left: 1rem;">
                    <h2 class="text-primary">{{ $company->name ?? 'Company Name' }}</h2>
                    <div class="text-sm text-muted" style="white-space: pre-line;">
                        {{ $company->address ?? 'Address Line 1...' }}
                        Tax ID: {{ $company->tax_id ?? '-' }}
                        Tel: {{ $company->phone ?? '-' }}
                    </div>
                </div>
            </div>
            <div class="text-right">
                <h1 style="color: #374151; letter-spacing: 1px; margin-bottom: 0.5rem;">@yield('document_title')</h1>
                <div class="text-sm">
                    <strong>Document No:</strong> {{ $docNo ?? 'DOC-' . date('Ymd-Hi') }}<br>
                    <strong>Date:</strong> {{ date('d/m/Y') }}
                </div>
            </div>
        </div>

        <div class="border-b mb-8"></div>

        <!-- Customer & Project Info -->
        <div class="grid-2 mb-8">
            <div>
                <h4 class="mb-2 text-muted text-sm uppercase">Bill To</h4>
                <div class="font-bold text-lg">{{ $billTo->employerNameTh ?? 'Client Name' }}</div>
                <div class="text-sm text-muted">
                    {{ $billTo->employerAddress ?? 'Address' }}<br>
                    @if(isset($billTo->tax_id) && $billTo->tax_id !== '-') Tax ID: {{ $billTo->tax_id }}<br> @endif
                    Tel: {{ $billTo->employerPhone ?? '-' }}
                </div>
            </div>
            <div class="text-right">
                <h4 class="mb-2 text-muted text-sm uppercase">Project Details</h4>
                <div class="font-bold">{{ $production->project_name }}</div>
                <div class="text-sm text-muted">{{ $production->description }}</div>
                <div class="mt-2 text-sm">
                    <strong>Reference:</strong> #PROD-{{ $production->id }}
                </div>
            </div>
        </div>

        <!-- Content Area -->
        @yield('content')

        <!-- Footer / Signature -->
        <div style="position: absolute; bottom: 20mm; width: 100%; left: 20mm; width: calc(100% - 40mm);">
            <div class="grid-2">
                <div class="text-center">
                    <div style="border-bottom: 1px solid #ccc; height: 30px; margin-bottom: 5px; width: 80%; margin: 0 auto;"></div>
                    <div class="text-sm text-muted">Receiver Signature</div>
                    <div class="text-sm text-muted">Date: ____/____/____</div>
                </div>
                <div class="text-center">
                    <div style="border-bottom: 1px solid #ccc; height: 30px; margin-bottom: 5px; width: 80%; margin: 0 auto;"></div>
                    <div class="text-sm text-muted">Authorized Signature</div>
                    <div class="text-sm text-muted">{{ $company->name ?? 'Company' }}</div>
                </div>
            </div>

            <div class="text-center mt-8 text-xs text-muted">
                Thank you for your business!
            </div>
        </div>
    </div>

</body>
</html>
