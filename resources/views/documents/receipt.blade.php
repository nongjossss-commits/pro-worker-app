@include('documents.layout', [
    'type' => $type ?? 'Receipt',
    'title' => $title ?? 'ใบเสร็จรับเงิน / Receipt',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
