@include('documents.layout', [
    'type' => 'Reimbursement Receipt',
    'title' => 'ใบเสร็จรับเงินสำรองจ่าย / Advance Receipt',
    'mode' => 'advance_only',
    'advanceItems' => $advanceItems ?? collect()
])
