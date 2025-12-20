@include('documents.layout', [
    'type' => 'Reimbursement Receipt',
    'title' => 'ใบเสร็จรับเงินทดรองจ่าย / Reimbursement Receipt',
    'mode' => 'advance_only',
    'advanceItems' => $advanceItems ?? collect()
])
