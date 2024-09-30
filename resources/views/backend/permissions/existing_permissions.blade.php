<h6>@lang('word.existing_permission')</h6>
<div class="row">
    @foreach($permissions as $existing_permissions)
    <div class="col-6">
        <span>{{ $existing_permissions->name }}</span>
    </div>
    @endforeach
</div>