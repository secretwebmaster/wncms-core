@if(!empty($input_data) && !empty($input_data['type']))
    @php
        /**
         *  #options
         *      required
         *      - type
         *      
         *      optional
         *      - include_row
         *      - hide_label
         *      - label_col_span
         */

        $input_data['name'] ??= '';
        $input_data['include_row'] ??= true;
        $input_data['show_label'] ??= true;
        $input_data['has_translation'] ??= true;
        $input_data['label'] ??= $input_data['name'] ?? __('word.label_is_not_set');
        $input_data['label_col_span'] ??= '3';
        $input_data['input_col_span'] ??= '9';
        $input_data['label_font_size'] ??= '6';
        $input_data['required'] ??= false;
        $input_data['diabled'] ??= false;
        $input_data['placeholder'] ??= '';
        $input_data['description'] ??= '';

        $input_data['solid'] ??= false;
        $input_data['input_size'] ??= '';
        $input_data['input_class'] ??= '';
        
        $input_data['empty_value'] ??= '';
        $input_data['is_model'] ??= false;
        $input_data['model'] ??= null;
        $input_data['model_name'] ??=  __('word.model_name_is_not_set');
        $input_data['options'] ??= [];
        $input_data['value_column'] ??= 'id';
        $input_data['display_column'] ??= $input_data['value_column'];

        $input_data['value'] ??= null;
        $input_data['image_width'] ??= null;
        $input_data['image_height'] ??= null;

        $d = (object)$input_data;

    @endphp

    {{-- Item wrapper start --}}
    @if($d->include_row)<div class="row mb-6">@endif

        {{-- Lable --}}
        @if($d->include_row)
            <label class="col-lg-{{ $d->label_col_span }} col-form-label fw-bold fs-{{ $d->label_font_size }} @if($d->required) required @endif">
                {{ $d->has_translation ? __('word.' . $d->label) : $d->label }}
            </label>
        @endif

        {{-- Input wrapper start --}}
        <div class="col-lg-{{ $d->input_col_span }} fv-row">
            {{-- Input item --}}
            @if(in_array($d->type, ['text', 'number','password']))
                <div class="fv-row">
                    <input 
                        type="{{ $d->type }}" 
                        name="{{ $d->name }}" 
                        value="{{ old($d->name, $d->value) }}"
                        placeholder="{{ $d->placeholder }}"
                        class="form-control @if($d->input_size) form-control-{{ $d->input_size }} @endif  @if($d->solid) form-control-solid @endif @if($d->input_class){{ $d->input_class }} @endif"/>
                </div>
                @if($d->description)<div class="p-2 text-muted">{{ $d->description }}</div>@endif

            @elseif($d->type == 'select')

                <select 
                    name="{{ $d->name }}" 
                    class="form-select 
                        @if($d->solid) form-select-solid @endif 
                        @if($d->input_size) form-select-{{ $d->input_size }} @endif 
                        @if($d->input_class){{ $d->input_class }} @endif" 
                    @if($d->required) required @endif>
                    
                    <option value="{{ $input_data['empty_value'] ?? '' }}">@lang('word.please_select_model', ['model_name' => __('word.' . $d->model_name )])</option>
                    @foreach($d->options as $option)
                        <option 
                            value="{{ $d->is_model ? $option->{$d->value_column} : $option }}" 
                            @if($d->is_model)
                            {{ $option->{$d->value_column} === old($d->name, $d->value) ? 'selected' :'' }}
                            @else
                            {{ $option === old($d->name, $d->value) ? 'selected' :'' }}
                            @endif
                        >{{ $option->{$d->display_column} }}</option>
                    @endforeach
                </select>
                @if($d->description)<div class="p-2 text-muted">{{ $d->description }}</div>@endif

            @elseif($d->type == 'image')

                <div class="row mb-6">
                    <div class="col-lg-9">
                        <div class="image-input image-input-outline {{ ($d->value ?? $d->model?->{$d->name}) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ $d->value ??  $d->model?->{$d->name} ?? asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                            <div class="image-input-wrapper w-125px h-125px" style="background-image: {{ ($d->value ?? $d->model?->{$d->name}) ? 'url("'.asset(($d->value ?? $d->model?->{$d->name})).'")' : 'none' }};@if($d->image_width)width:{{ $d->image_width }} !important; @endif @if($d->image_height)height:{{ $d->image_height }} !important @endif;"></div>

                            <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                                <i class="fa fa-pencil fs-7"></i>

                                <input type="file" name="{{ $d->name }}" accept="image/*"/>
                                {{-- remove image --}}
                                <input type="hidden" name="{{ $d->name }}_remove"/>
                            </label>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel">
                                <i class="fa fa-times"></i>
                            </span>

                            <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove">
                                <i class="fa fa-times"></i>
                            </span>
                        </div>

                        <div class="form-text">@lang('word.allow_file_types', ['types' => 'png, jpg, jpeg, gif'])</div>
                    </div>
                </div>


                
            @endif

        </div>{{-- Input wrapper end --}}

    @if($d->include_row)</div>@endif{{-- Item wrapper end --}}
    

    @php
        unset($input_data);
        unset($d);
    @endphp

@endif