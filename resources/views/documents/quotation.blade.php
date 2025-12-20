@include('documents.layout', [
    'type' => $type ?? 'Quotation',
    'title' => $title ?? 'ใบเสนอราคา / Quotation',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
