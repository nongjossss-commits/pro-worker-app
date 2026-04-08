@props(['model'])
@php
    $activity = $model->relationLoaded('lastEditedActivity')
        ? $model->lastEditedActivity
        : $model->lastEditedActivity()->with('user')->first();

    if (!$activity) return;

    $userName = $activity->user->name ?? 'System';
    $timeAgo = $activity->created_at->locale('th')->diffForHumans();
    $actionLabel = $activity->action === 'create' ? 'สร้างโดย' : 'แก้ไขโดย';
    $fullDate = $activity->created_at->locale('th')->translatedFormat('d M Y H:i');
@endphp
@if($activity)
<div class="last-edited-badge" title="{{ $actionLabel }} {{ $userName }} เมื่อ {{ $fullDate }}">
    <i class="bi bi-pencil-square"></i>
    <span class="last-edited-user">{{ $userName }}</span>
    <span class="last-edited-time">{{ $timeAgo }}</span>
</div>
@endif
