{{-- Main tab --}}
<div class="tab-content" id="pills-tabContent">

    {{-- Baic tab --}}
    <div class="tab-pane fade show {{ $activeTab == 'pills-basic' || !$activeTab ? 'active' : '' }}" id="pills-basic">

        <div class="card">
            <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0 d-block d-md-flex align-items-center text-gray-100">
                        {{ $page->exists ? wncms_model_word('page', 'edit') : wncms_model_word('page', 'create') }}
                    </h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{-- title --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('wncms::word.title')</label>
                    <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $page->title) }}" required>
                </div>

                {{-- slug --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">
                        @lang('wncms::word.slug') (@lang('wncms::word.show_in_url'))
                    </label>
                    <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $page->slug) }}">
                </div>

                {{-- type --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('wncms::word.type')</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.please_select')</option>
                        @foreach ($types ?? [] as $type)
                            <option value="{{ $type }}" @selected($type === old('type', $page->type) || (!old('type') && !$page->type && $type == 'plain'))>
                                @lang('wncms::word.' . $type)
                            </option>
                        @endforeach
                    </select>

                    <div class="text-muted">
                        @lang('wncms::word.theme_template_list_will_be_shown_after_saving')
                    </div>
                </div>

                {{-- template selector --}}
                @if ($page->type == 'template')
                    <div class="form-item mb-3">
                        <label class="form-label fw-bold fs-6">
                            @lang('wncms::word.available_page_templates')
                        </label>

                        <select name="blade_name" class="form-select form-select-sm" required>
                            <option value="">@lang('wncms::word.please_select_page_template')</option>

                            @foreach ($available_templates ?? [] as $tpl)
                                <option value="{{ $tpl['blade_name'] }}"
                                    {{ $tpl['blade_name'] == $page->blade_name ? 'selected' : '' }}>
                                    {{ $tpl['label'] ?? $tpl['blade_name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="blade_name" value="{{ $page->blade_name }}">
                @endif

                {{-- remark --}}
                <div class="form-item mb-3">
                    <label class="form-label fw-bold fs-6">@lang('wncms::word.remark')</label>
                    <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $page->remark) }}">
                </div>

                {{-- sort --}}
                <div class="form-item mb-3">
                    <label class="form-label fw-bold fs-6">@lang('wncms::word.sort')</label>
                    <input type="number" name="sort" class="form-control form-control-sm" value="{{ old('sort', $page->sort) }}">
                </div>

                {{-- content --}}
                <div class="form-item mb-3">
                    <label class="form-label fw-bold fs-6">@lang('wncms::word.content')</label>
                    <textarea id="kt_docs_tinymce_basic" name="content" class="tox-target">{{ old('content', $page->content) }}</textarea>
                </div>

            </div>
        </div>
    </div>

    {{-- Template options tab --}}
    @if ($page->type == 'template')
        <div class="tab-pane fade {{ $activeTab == 'pills-template-options' ? 'show active' : '' }}" id="pills-template-options">

            @if (!empty($page_template_options))

                @foreach ($page_template_options as $groupId => $group)
                    <div class="card mb-5">
                        <div class="card-header border-0 cursor-pointer px-3 px-md-9 bg-dark">
                            <div class="card-title m-0">
                                <h3 class="fw-bolder m-0 text-gray-100">{{ $group['label'] }}</h3>
                            </div>
                        </div>

                        <div class="card-body p-2 p-md-5">

                            @foreach ($group['options'] as $optionIndex => $option)
                                @include('wncms::backend.parts.inputs', [
                                    'option' => $option,
                                    'optionIndex' => $optionIndex,
                                    'currentOptions' => $page_template_values[$groupId] ?? [],
                                    'inputNameKey' => "template_inputs[$groupId]",
                                ])
                            @endforeach

                        </div>
                    </div>
                @endforeach
            @else
                <div class="alert alert-danger">
                    @lang('wncms::word.no_template_option_found')
                </div>
            @endif

        </div>
    @endif

    {{-- Builder tab --}}
    @if ($page->type == 'builder')
        <div class="alert alert-info">Builder mode is under development.</div>
    @endif

</div>