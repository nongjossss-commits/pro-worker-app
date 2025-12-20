@include('documents.layout', [
    'type' => 'Tax Invoice',
    'title' => 'ใบกำกับภาษี / Tax Invoice',
    'mode' => $mode ?? 'combined',
    'advanceItems' => $advanceItems ?? collect()
])
