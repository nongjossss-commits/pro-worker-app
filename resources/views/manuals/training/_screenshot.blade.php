{{--
    Screenshot/placeholder partial — gracefully renders an image if it exists,
    otherwise a styled placeholder box with descriptive caption.

    Usage:
        @include('manuals.training._screenshot', [
            'src'     => 'workflow/step-1-tick-checkbox',  // relative path under public/images/manuals/
            'alt'     => 'การ์ดลูกจ้างใน Workflow tab Visa Renewal',
            'caption' => 'หน้าจอเมนู Workflow แสดงการ์ดลูกจ้างพร้อม checkbox แต่ละขั้นตอน',
            'callouts' => ['1. checkbox', '2. ปุ่ม Edit', '3. progress bar'],  // optional
        ])
--}}
@php
    $src = $src ?? '';
    $alt = $alt ?? '';
    $caption = $caption ?? '';
    $callouts = $callouts ?? [];

    // Resolve image path — supports relative under public/images/manuals/ or absolute URL
    $imagePath = null;
    $imageUrl = null;
    if ($src) {
        // Strip extension if provided
        $cleanSrc = preg_replace('/\.(png|jpg|jpeg|gif|webp|svg)$/i', '', $src);
        // Try common extensions in order
        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $ext) {
            $candidate = public_path("images/manuals/{$cleanSrc}.{$ext}");
            if (file_exists($candidate)) {
                $imagePath = $candidate;
                $imageUrl = asset("images/manuals/{$cleanSrc}.{$ext}");
                break;
            }
        }
    }
@endphp

<div class="screenshot-block">
    @if($imageUrl)
        <figure class="screenshot-figure">
            <img src="{{ $imageUrl }}" alt="{{ $alt ?: $caption }}" class="screenshot-img">
            @if($caption)
                <figcaption class="screenshot-caption">{{ $caption }}</figcaption>
            @endif
        </figure>
    @else
        <div class="screenshot-placeholder">
            <div class="screenshot-placeholder-inner">
                <div class="screenshot-placeholder-icon">
                    <i class="bi bi-image"></i>
                </div>
                <div class="screenshot-placeholder-title">[ {{ __('ภาพประกอบ') }} ]</div>
                <div class="screenshot-placeholder-desc">{{ $caption ?: $alt ?: __('ยังไม่ได้ใส่ภาพประกอบ') }}</div>
                <div class="screenshot-placeholder-hint">
                    <code>public/images/manuals/{{ $src ?: '...' }}.png</code>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($callouts))
        <ol class="screenshot-callouts">
            @foreach($callouts as $callout)
                {{-- Use raw HTML so <strong> in callout strings renders as bold instead of literal text --}}
                <li>{!! $callout !!}</li>
            @endforeach
        </ol>
    @endif
</div>
