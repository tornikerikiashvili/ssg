@props(['extension' => ''])
@php
    $extension = strtolower($extension);
    $type = match ($extension) {
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'bmp', 'tif', 'tiff', 'heic' => 'image',
        'mp4', 'mov', 'webm', 'avi', 'mkv', 'm4v' => 'video',
        'zip', 'rar', '7z', 'tar', 'gz', 'bz2' => 'archive',
        'svg', 'eps', 'ai', 'psd', 'psb', 'fig', 'sketch', 'indd' => 'design',
        'mp3', 'wav', 'ogg', 'aac', 'flac', 'm4a' => 'audio',
        'pdf' => 'pdf',
        'doc', 'docx', 'txt', 'rtf', 'odt', 'md' => 'document',
        'xls', 'xlsx', 'csv', 'ods' => 'spreadsheet',
        'ppt', 'pptx', 'key', 'odp' => 'presentation',
        'html', 'css', 'js', 'ts', 'json', 'xml', 'yml', 'yaml' => 'code',
        default => 'file',
    };
    $color = match ($type) {
        'image' => '#CA5486',
        'video' => '#00AED4',
        'archive', 'spreadsheet' => '#08B89D',
        'design', 'pdf' => '#ED1846',
        'audio' => '#A78BFA',
        'presentation' => '#F59E0B',
        'document', 'code' => '#60A5FA',
        default => '#94A3B8',
    };
    $symbol = match ($type) {
        'image' => 'M1 18 8 10 13 15 17 11 23 18 M16 4h.01',
        'video' => 'm8 3 13 9-13 9Z',
        'archive' => 'M10 0h4v4h-4z M10 8h4v4h-4z M10 16h4v6h-4z',
        'design' => 'm12 1 10 10-10 11L2 11Z M12 1v10 M10 11h4',
        'audio' => 'M9 18V5l12-3v13 M9 5v5l12-3 M9 18a4 3 0 1 1-4-3h4 M21 15a4 3 0 1 1-4-3h4',
        'spreadsheet' => 'M2 2h20v20H2Z M2 9h20 M2 15h20 M9 2v20',
        'presentation' => 'M2 2h20v15H2Z M12 17v5 M7 22h10 M6 12l5-5 4 3 3-5',
        'code' => 'm7 5-6 7 6 7 M17 5l6 7-6 7 M14 2l-4 20',
        default => 'M3 5h18 M3 12h18 M3 19h12',
    };
@endphp
<svg class="assets_item-icon" data-file-icon="{{ $type }}" width="90" height="90" viewBox="0 0 90 90" fill="none" role="img" aria-label="{{ strtoupper($extension ?: 'file') }} file">
    <rect width="90" height="90" rx="8" fill="{{ $color }}" fill-opacity="0.08" />
    <g stroke="{{ $color }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M18 14h20l10 10v29H18a3 3 0 0 1-3-3V17a3 3 0 0 1 3-3Z M38 14v10h10" />
        <path d="{{ $symbol }}" transform="translate(22 29) scale(.8)" />
    </g>
</svg>
