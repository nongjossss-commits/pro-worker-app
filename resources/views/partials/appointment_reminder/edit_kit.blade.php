{{-- Edit-employee popup for pages that don't have one: the same pieces the
     menus include (see components/edit-employee-modal's usage note). Loaded by
     the combined calendar's "Edit" button — AppointmentReminderController::editKit(). --}}
<x-edit-employee-modal />
<x-cropper-modal />
@include('employees.partials._edit_scripts')
