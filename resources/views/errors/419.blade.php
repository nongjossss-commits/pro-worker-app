<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Page Expired') }} - {{ __('Pro Worker Labour Business OS') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: #f8fafc; /* Tailwind's slate-50 */
        }
    </style>

    <script>
        // Auto-redirect to login page after 5 seconds
        setTimeout(function() {
            window.location.href = "{{ route('login') }}";
        }, 3000);
    </script>
</head>
<body class="antialiased text-slate-800">

    <div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full text-center space-y-8 bg-white p-10 rounded-xl shadow-lg">

            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100">
                <svg class="h-10 w-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>

            <div>
                <h2 class="mt-2 text-3xl font-extrabold text-slate-900">
                    {{ __('หน้าเว็บหมดอายุ') }}
                </h2>
                <p class="mt-2 text-sm text-slate-500">
                    {{ __('Page Expired') }}
                </p>
                <p class="mt-4 text-base text-slate-600">
                    {{ __('ขออภัย เซสชั่นของคุณหมดอายุเนื่องจากไม่ได้ใช้งานเป็นเวลานาน') }}<br>
                    {{ __('ระบบจะนำคุณกลับไปหน้าเข้าสู่ระบบในอีกสักครู่...') }}
                </p>
            </div>

            <div class="mt-6">
                <a href="{{ route('login') }}"
                   class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-150 ease-in-out">
                    {{ __('เข้าสู่ระบบใหม่ทันที') }}
                </a>
            </div>
        </div>
    </div>

</body>
</html>
