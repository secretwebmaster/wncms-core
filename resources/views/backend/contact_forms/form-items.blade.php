<div class="card-body border-top p-3 p-md-9">


    {{-- name --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.name')</label>
        <div class="col-lg-9 fv-row">
            <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $contact_form->name) }}" />
        </div>
    </div>

    {{-- title --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.title')</label>
        <div class="col-lg-9 fv-row">
            <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $contact_form->title) }}" />
        </div>
    </div>

    {{-- description --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.description')</label>
        <div class="col-lg-9 fv-row">
            <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $contact_form->description) }}" />
        </div>
    </div>

    {{-- remark --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.remark')</label>
        <div class="col-lg-9 fv-row">
            <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $contact_form->remark) }}" />
        </div>
    </div>

    {{-- contact_form_options --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.options')</label>
        <div class="col-lg-9 fv-row">
            <div class="row align-items-center mt-3" id="contact-form-options">
                @foreach($options->sortBy('order') as $index => $option)
                <div class="col-12 mb-1">
                    <label class="form-check form-check-inline form-check-solid me-5">
                        <input class="form-check-input" name="options[{{ $index }}][option_id]" type="checkbox" value="{{ $option->id }}" @if($contact_form->options->contains($option->id)) checked @endif/>
                        <span class="fw-bold ps-2 fs-6">
                            <i class="fa-solid fa-bars"></i>
                            <span>{{ $option->display_name }}</span>

                            <input type="checkbox" class="ms-5" name="options[{{ $index }}][option_is_required]" @if($contact_form->options->where('name', $option->name)->first()?->pivot?->is_required) checked @endif>
                            <label for="">@lang('wncms::word.required')</label>

                            @if(gss('show_developer_hints'))
                            <span class="text-gray-300"> (#{{ $option->id }} | {{ $option->name }} | {{ $option->placeholder ?: '---' }} | {{ $option->order }})</span>
                            @endif

                        </span>
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        @push('foot_js')
        <script>
            widgetList = document.getElementById('contact-form-options');
                            new Sortable(widgetList, {
                                group: {
                                    name: 'shared',
                                    pull: 'clone', // To clone: set pull to 'clone',
                                    put: false, // Disable putting on the widget-list
                                    disabled: true,
                                },
                                animation: 150,
                                sort: true, // Disable sorting for widget-list

                            });
        </script>
        @endpush
    </div>

    {{-- success_action --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.success_action')</label>
        <div class="col-lg-9 fv-row">
            <textarea name="success_action" class="form-control" rows="6">{{ old('success_action', $contact_form->success_action) }}</textarea>
        </div>
    </div>

    {{-- fail_action --}}
    <div class="row mb-3">
        <label class="col-lg-3 col-form-label fw-bold fs-6">@lang('wncms::word.fail_action')</label>
        <div class="col-lg-9 fv-row">
            <textarea name="fail_action" class="form-control" rows="6">{{ old('fail_action', $contact_form->fail_action) }}</textarea>
        </div>
    </div>
</div>