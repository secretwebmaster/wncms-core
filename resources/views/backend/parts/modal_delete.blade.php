<button type="button" class="btn btn-sm btn-danger fw-bold {{ $btn_class ?? 'px-2 py-1' }}" data-bs-toggle="modal" data-bs-target="#{{ "modal_" . ($target??'')  . $model->id }}">
    {!! $btn_text ?? __('word.delete') !!}
</button>

<div class="modal fade" tabindex="-1" id="{{ "modal_" . ($target??'')  . $model->id }}">
    <form id="{{ "form_" . ($target??'')  . $model->id }}" action="{{ $route }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title">{{ $title ?? __('word.confirm_delete') .  $model->id  . "?" }}</h3>
                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <span class="svg-icon svg-icon-1"></span>
                    </div>
                </div>
    
                <div class="modal-body">
                    <p class="text-danger fw-bold fs-4">@lang('word.this_process_is_irreversible')</p>
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                    <button type="submit" wncms-btn-loading form-id="{{ "form_" . ($target??'')  . $model->id  }}"  class="btn btn-danger">@lang('word.confirm_delete')</button>
                </div>
            </div>
        </div>
    </form>
</div>