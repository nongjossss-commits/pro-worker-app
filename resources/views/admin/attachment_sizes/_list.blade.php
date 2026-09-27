{{-- One page of the "ขนาดไฟล์แนบ" list — loaded into index.blade.php by AttachmentSizeController@list. --}}
@php
    $fmt = function (int $bytes) {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        return number_format($bytes / 1024) . ' KB';
    };
    $total = $ownedCount + $orphanCount;
    $isImage = fn ($f) => in_array($f['extension'], ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'heic', 'tif', 'tiff'], true);
@endphp
<div data-asz-result data-view="{{ $view }}">
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted">{{ __('Files from :size', ['size' => $minMb . ' MB']) }}</div>
                <div class="fs-3 fw-bold {{ $total ? 'text-danger' : 'text-success' }}">{{ number_format($total) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted">{{ __('Space they use') }}</div>
                <div class="fs-3 fw-bold">{{ $fmt($result['total_bytes']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted">{{ __('All uploaded files') }}</div>
                <div class="fs-5 fw-bold">{{ number_format($result['scanned_files']) }} {{ __('files') }}</div>
                <div class="small text-muted">{{ $fmt($result['scanned_bytes']) }}</div>
            </div></div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card h-100"><div class="card-body">
                <div class="small text-muted">{{ __('Scanned at') }}</div>
                <div class="fs-5 fw-bold">{{ $result['scanned_at'] }}</div>
            </div></div>
        </div>
    </div>

    @if(!$total)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-check-circle-fill text-success" style="font-size: 2.5rem;"></i>
                <p class="mt-3 mb-0 text-muted">{{ __('No uploaded file is :size or larger.', ['size' => $minMb . ' MB']) }}</p>
            </div>
        </div>
    @else
        <ul class="nav nav-tabs mb-0">
            <li class="nav-item">
                <a href="#" class="nav-link {{ $view === 'owned' ? 'active fw-bold' : '' }}" data-asz-view="owned">
                    <i class="bi bi-paperclip me-1"></i> {{ __('Attached to records') }} <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ number_format($ownedCount) }}</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link {{ $view === 'orphans' ? 'active fw-bold' : '' }}" data-asz-view="orphans">
                    <i class="bi bi-question-circle me-1"></i> {{ __('Not linked to any record') }} <span class="badge rounded-pill bg-secondary-subtle text-secondary-emphasis">{{ number_format($orphanCount) }}</span>
                </a>
            </li>
        </ul>

        <div class="card border-top-0" style="border-top-left-radius: 0;">
            @if($view === 'orphans')
                <div class="card-body py-2 small text-muted border-bottom">{{ __('Large files that no record points to — e.g. generated downloads or files left over after a record was changed. Nothing is deleted from here.') }}</div>
            @endif

            @if($files->isEmpty())
                <div class="card-body text-center py-5 text-muted">{{ __('No files in this group.') }}</div>
            @elseif($view === 'owned')
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('File') }}</th>
                                <th class="text-end">{{ __('Size') }}</th>
                                <th>{{ __('Attached to') }}</th>
                                <th>{{ __('Field') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $f)
                                @foreach($f['owners'] as $i => $o)
                                    <tr>
                                        @if($i === 0)
                                            <td rowspan="{{ count($f['owners']) }}">
                                                <div class="fw-semibold text-break">
                                                    <i class="bi {{ $isImage($f) ? 'bi-file-earmark-image text-primary' : ($f['extension'] === 'pdf' ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark') }}"></i>
                                                    {{ $f['name'] }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ strtoupper($f['extension']) }}@if($f['dimensions']) · {{ $f['dimensions'] }}@endif · {{ __('Uploaded') }} {{ \Carbon\Carbon::parse($f['modified'])->format('d/m/Y') }}
                                                </div>
                                                @if($isImage($f))
                                                    <div class="small text-warning-emphasis"><i class="bi bi-lightbulb"></i> {{ __('A photo or scan is usually clear enough at about 2000 px on the long side (under 1 MB).') }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold text-danger text-nowrap" rowspan="{{ count($f['owners']) }}">{{ $fmt($f['size']) }}</td>
                                        @endif
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis border">{{ $o['type'] }}</span>
                                            <span class="fw-semibold">{{ $o['name'] }}</span>
                                            @if($o['id'])<span class="small text-muted">({{ __('ID') }} {{ $o['id'] }})</span>@endif
                                            @if($o['deleted'])<span class="badge bg-danger-subtle text-danger">{{ __('In trash') }}</span>@endif
                                        </td>
                                        <td>{{ $o['field'] }}</td>
                                        <td class="text-end text-nowrap">
                                            <a href="{{ asset('storage/' . $f['path']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="{{ __('Open file') }}"><i class="bi bi-eye"></i></a>
                                            @if($o['preview_type'] && $o['id'] && !$o['deleted'])
                                                <button type="button" class="btn btn-sm btn-outline-info btn-preview" data-model-type="{{ $o['preview_type'] }}" data-model-id="{{ $o['id'] }}" title="{{ __('Preview') }}"><i class="bi bi-search"></i></button>
                                            @endif
                                            @if($o['edit_url'] && !$o['deleted'])
                                                <a href="{{ $o['edit_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary" title="{{ __('Edit — re-upload a smaller file') }}"><i class="bi bi-pencil"></i></a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('File') }}</th>
                                <th>{{ __('Folder') }}</th>
                                <th class="text-end">{{ __('Size') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($files as $f)
                                <tr>
                                    <td class="text-break">{{ $f['name'] }}<div class="small text-muted">{{ strtoupper($f['extension']) }}@if($f['dimensions']) · {{ $f['dimensions'] }}@endif · {{ \Carbon\Carbon::parse($f['modified'])->format('d/m/Y') }}</div></td>
                                    <td class="small text-muted">{{ $f['folder'] }}</td>
                                    <td class="text-end fw-bold text-nowrap">{{ $fmt($f['size']) }}</td>
                                    <td class="text-end"><a href="{{ asset('storage/' . $f['path']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($files->total() > 0)
                <div class="card-footer bg-body-tertiary d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">{{ __('Showing :from–:to of :total', ['from' => number_format($files->firstItem()), 'to' => number_format($files->lastItem()), 'total' => number_format($files->total())]) }}</div>
                    {{ $files->onEachSide(1)->links('admin.attachment_sizes._pager') }}
                </div>
            @endif
        </div>
    @endif
</div>
