@include('documents.layout', [
    'type' => $type ?? 'Credit Note',
    'title' => $title ?? 'ใบลดหนี้ / Credit Note',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
