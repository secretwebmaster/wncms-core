@if(empty($hideContactFormTitle))
<h3 class="title fw-bold mb-4">{{ $contactForm?->title }}</h3>
@endif

<form id="contact_form_{{ $contactForm->id }}" class="form-submit-init" novalidate="true">
    @csrf
    
    <input type="hidden" name="contact_form_id" value="{{ $contactForm->id }}">
    <input type="hidden" name="current_url" value="{{ request()->fullUrl() }}">
    <div class="row g-4">
        @foreach($contactForm->options->sortBy('pivot.order') ?? [] as $option)

            <div class="col-12" data-type="{{ $option->type }}" data-name="{{ $option->name }}">
                <div class="form-group">
                    <label for="{{ $option->name }}" class="form-label">{{ $option->display_name }}<span class="form-label-colon">:<span></label>

                    @if($option->type == 'utm_trackers')
                        <input type="hidden" name="utm_source" id="utm_source">
                        <input type="hidden" name="utm_medium" id="utm_medium">
                        <input type="hidden" name="utm_campaign" id="utm_campaign">
                        <input type="hidden" name="utm_content" id="utm_content">
                        <input type="hidden" name="utm_term" id="utm_term">

                    @elseif($option->type == 'hidden')
                        <input type="hidden" 
                            name="{{ $option->name }}" 
                            value="{{ $option->default_value }}">

                    @elseif($option->type == 'text')
                        
                        <input type="text" 
                            class="form-control form-control-sm" 
                            name="{{ $option->name }}" 
                            placeholder="{{ $option->placeholder }}" 
                            value="{{ $option->default_value }}"
                            @if( $option->pivot->is_required) required @endif>
    
                    @elseif($option->type == 'textarea')

                        <textarea 
                            name="{{ $option->name }}" 
                            class="form-control form-control-sm" 
                            placeholder="{{ $option->placeholder }}" 
                            @if( $option->pivot->is_required) required @endif>{{ $option->default_value }}</textarea>

                    @elseif($option->type == 'select')

                        <select name="{{ $option->name }}" class="form-select form-select-sm" @if( $option->pivot->is_required) required @endif>
                            <option value="">@lang('word.please_select'){{ $option->display_name }}</option>
                            @foreach(explode("\r\n", $option->options) as $optionOption)
                                @if(strpos($optionOption, "|"))
                                <option value="{{ explode("|", $optionOption)[0] }}">{{ explode("|", $optionOption)[1] }}</option>
                                @else
                                <option value="{{ $optionOption }}">{{ $optionOption }}</option>
                                @endif
                            @endforeach
                        </select>

                    @elseif($option->type == 'checkbox')

                        @foreach(explode("\r\n", $option->options) as $optionOption)
                            @if(strpos($optionOption, "|"))
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="{{ $option->name }}" id="{{ explode("|", $optionOption)[0] }}" value="{{ explode("|", $optionOption)[0] }}" @if( $option->pivot->is_required) required @endif>
                                    <label class="form-check-label" for="{{ explode("|", $optionOption)[0] }}">{{ explode("|", $optionOption)[1] }}</label>
                                </div>
                            @else
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" name="{{ $option->name }}" id="{{ $optionOption }}" value="{{ $optionOption }}" @if( $option->pivot->is_required) required @endif>
                                    <label class="form-check-label" for="{{ $optionOption }}">{{ $optionOption }}</label>
                                </div>
                            @endif
                        @endforeach

                    @elseif($option->type == 'radio')

                        @foreach(explode("\r\n", $option->options) as $optionOption)
                            @if(strpos($optionOption, "|"))
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="{{ $option->name }}" id="{{ explode("|", $optionOption)[0] }}" value="{{ explode("|", $optionOption)[0] }}" @if( $option->pivot->is_required) required @endif>
                                    <label class="form-check-label" for="{{ explode("|", $optionOption)[0] }}">{{ explode("|", $optionOption)[1] }}</label>
                                </div>
                            @else
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="{{ $option->name }}" id="{{ $optionOption }}" value="{{ $optionOption }}" @if( $option->pivot->is_required) required @endif>
                                    <label class="form-check-label" for="{{ $optionOption }}">{{ $optionOption }}</label>
                                </div>
                            @endif
                        @endforeach

                    @endif

                </div>
                <div class="form-error-message" style="display: none">@lang('word.this_field_is_required')</div>
            </div>

        @endforeach

        <div class="col-12">
            <div class="form-group">
                <button class="btn btn-primary" type="button"
                    wncms-btn-ajax
                    data-route="{{ route('frontend.contact_form_submissions.submit_ajax') }}"
                    data-method="POST"
                    data-form="contact_form_{{ $contactForm->id }}"
                    data-original-text="@lang('word.submit')"
                    data-loading-text="@lang('word.loading').."
                    data-success-text="@lang('word.submitted')"
                    data-fail-text="@lang('word.fail_to_submit')"
                >@lang('word.submit')</button>
            </div>
            <div class="form-result mt-4"></div>
        </div>

    </div>


    <script>

        function customSuccessAction(){
            {!! $contactForm->success_action !!}
        }

        function customFailAction(){
            {!! $contactForm->fail_action !!}
        }

    </script>


</form>