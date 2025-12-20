@include('documents.layout', [
    'type' => $type ?? 'Tax Invoice',
    'title' => $title ?? 'ใบกำกับภาษี / Tax Invoice',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
