@php
    $commentUserSelectorSelectedId = (string) ($selectedUserId ?? '');
    $commentUserSelectorSelectedName = $selectedUser?->username ?? ($commentUserSelectorSelectedId === '' ? __('wncms::word.guest') : '');
    $commentUserSelectorSelectedEmail = $selectedUser?->email ?? '';
    $commentUserSelectorIsGuest = $commentUserSelectorSelectedId === '';
@endphp

<div class="comment-user-selector" data-comment-user-selector>
    <input type="hidden" name="user_id" class="js-comment-user-id" value="{{ $commentUserSelectorSelectedId }}">
    <input type="text" class="form-control form-control-sm js-comment-user-tagify-input" value="" placeholder="{{ __('wncms::word.search') }} {{ __('wncms::word.author') }}" data-initial-id="{{ $commentUserSelectorSelectedId }}" data-initial-name="{{ $commentUserSelectorSelectedName }}" data-initial-email="{{ $commentUserSelectorSelectedEmail }}" data-initial-is-guest="{{ $commentUserSelectorIsGuest ? '1' : '0' }}">
</div>
