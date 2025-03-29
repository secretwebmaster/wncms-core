@php
    $status_attribute ??= 'status';
    $status = ((object)$model)->{$status_attribute};

    $active = array_merge(['active', 'paid', 'success', 'published'], $active_statuses ?? []);
    $pending = array_merge(['pending'], $pending_statuses ?? []);
    $completed = array_merge(['completed'], $completed_statuses ?? []);
    $error = array_merge(['error', 'rejected', 'unread', 'trashed', 'inactive', 'paused'], $error_statuses ?? []);

    $style = 'secondary';

    if (in_array($status, $active)) {
        $style = 'success';
    } elseif (in_array($status, $pending)) {
        $style = 'warning';
    } elseif (in_array($status, $completed)) {
        $style = 'success';
    } elseif (in_array($status, $error)) {
        $style = 'danger';
    }
@endphp

<span class="{{ !empty($badgeStyle) ? "badge badge-{$style}" : "text-{$style} fw-bold" }}">
    @lang('wncms::word.' . $status)
</span>