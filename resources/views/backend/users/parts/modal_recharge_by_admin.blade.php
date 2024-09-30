<button type="button" class="btn btn-sm btn-primary px-2 py-1 fw-bold" data-bs-toggle="modal" data-bs-target="#recharge_user_{{ $user->id }}">@lang('word.recharge')</button>
<div class="modal fade" tabindex="-1" id="recharge_user_{{ $user->id }}">
    <div class="modal-dialog">
        <form action="{{ route('users.recharge.admin', $user) }}" method="POST">
            @csrf

            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">@lang('word.recharge_for_this')</h3>
                </div>
    
                <div class="modal-body">
                    <div class="form-item mb-6">
                        <label>@lang('word.username')</label>
                        <input class="form-control" type="text" value="{{ $user->username }}" disabled>
                    </div>
                    <div class="form-item mb-6">
                        <label>@lang('word.email')</label>
                        <input class="form-control" type="text" value="{{ $user->email }}" disabled>
                    </div>
                    <div class="form-item mb-6">
                        <label>@lang('word.amount')</label>
                        <input class="form-control" type="number" name="amount" value="">
                    </div>
                
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('word.close')</button>
                    <button type="submit" class="btn btn-primary fw-bold">@lang('word.submit')</button>
                </div>
            </div>

        </form>

    </div>
</div>