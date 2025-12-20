@include('documents.layout', [
    'type' => $type ?? 'Invoice',
    'title' => $title ?? 'ใบแจ้งหนี้ / Invoice',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
