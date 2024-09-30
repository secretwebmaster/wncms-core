<div class="row">

    {{-- Main --}}
    <div class="col-12 col-md-9">
        <div class="card">
            <div class="card-header border-0 cursor-pointer px-3 px-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">{{ wncms_model_word('post', 'edit') }}</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{-- title --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6" for="title">@lang('word.title')</label>
                    <input type="text" name="title" class="form-control form-control-sm" value="{{ old('title', $post->title) }}" required />
                </div>

                {{-- category --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.category')</label>

                    <div class="row">
                        <div class="col-12 col-md-9 mb-1">
                            <input class="form-control form-control-sm px-1 py-0" name="post_categories" id="post_categories" value="{{ old('post_categories', $post->getTagNameString('post_category')) }}" />

                            <script type="text/javascript">
                                window.addEventListener('DOMContentLoaded', (event) => {
                                    //Tagify
                                    var input = document.querySelector("#post_categories");
                                    var post_categories = @json($post_categories);
                        
                                    console.log(post_categories)
                                    // Initialize Tagify script on the above inputs
                
                                    new Tagify(input, {
                                        whitelist: post_categories,
                                        maxTags: 50,
                                        dropdown: {
                                            maxItems: 20,           // <- mixumum allowed rendered suggestions
                                            classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                                            enabled: 0,             // <- show suggestions on focus
                                            closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                                            originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(',')
                                        }
                                    });
                                });
                            </script>
                        </div>

                        <div class="col-12 col-md-3 d-flex align-items-center">
                            <div class="form-check form-check-custom form-switch fv-row">
                                <input type="hidden" name="auto_generate_category" value="0">
                                <input class="form-check-input w-35px h-20px" type="checkbox" name="auto_generate_category" value="1" {{ old('auto_generate_category') ? 'checked' : '' }} />
                                <label class="form-check-label">@lang('word.auto_generate_category') <a href="{{ route('tags.keywords.index') }}" target="_blank" title="@lang('word.go_to_keyword_binding')">(?)</a></label>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- tag --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.tag')</label>

                    <div class="row">
                        <div class="col-12 col-md-9 mb-1">
                            <input class="form-control form-control-sm px-1 py-0" name="post_tags" id="post_tags" value="{{ old('post_tags', $post->getTagNameString('post_tag')) }}" />

                            <script type="text/javascript">
                                window.addEventListener('DOMContentLoaded', (event) => {
                                    //Tagify
                                    var input = document.querySelector("#post_tags");
                                    var post_tags = @json($post_tags);
                        
                                    console.log(post_tags)
                                    // Initialize Tagify script on the above inputs
                
                                    new Tagify(input, {
                                        whitelist: post_tags,
                                        maxTags: 50,
                                        dropdown: {
                                            maxItems: 20,           // <- mixumum allowed rendered suggestions
                                            classname: "tagify__inline__suggestions", // <- custom classname for this dropdown, so it could be targeted
                                            enabled: 0,             // <- show suggestions on focus
                                            closeOnSelect: false,    // <- do not hide the suggestions dropdown once an item has been selected
                                            originalInputValueFormat: valuesArr => valuesArr.map(item => item.value).join(',')
                                        }
                                    });
                                });
                            </script>
                        </div>

                        <div class="col-12 col-md-3 d-flex align-items-center">
                            <div class="form-check form-check-custom form-switch fv-row">
                                <input type="hidden" name="auto_generate_tag" value="0">
                                <input class="form-check-input w-35px h-20px" type="checkbox" name="auto_generate_tag" value="1" {{ old('auto_generate_tag') ? 'checked' : '' }} />
                                <label class="form-check-label">@lang('word.auto_generate_tag') <a href="{{ route('tags.keywords.index') }}" target="_blank" title="@lang('word.go_to_keyword_binding')">(?)</a></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- label --}}
                    <div class="col-12 col-md-6">
                        <div class="form-item mb-3">
                            <label class="form-label fw-bold fs-6">@lang('word.label')</label>
                            <input type="text" name="label" class="form-control form-control-sm" value="{{ old('label', $post->label) }}" />
                        </div>
                    </div>

                    {{-- slug --}}
                    <div class="col-12 col-md-6">
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.slug') (@lang('word.show_in_url'))</label>
                            <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $post->slug) }}" />
                        </div>
                    </div>
                </div>

                {{-- excerpt --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.excerpt')</label>
                    <textarea type="text" name="excerpt" class="form-control form-control-sm" rows="4">{{ old('excerpt', $post->excerpt) }}</textarea>
                </div>

                {{-- remark --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.remark')</label>
                    <input type="text" name="remark" class="form-control form-control-sm" value="{{ old('remark', $post->remark) }}" />
                </div>

                <div class="row">
                    <div class="col-12 col-md-4">
                        {{-- order --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.order')</label>
                            <input type="number" name="order" class="form-control form-control-sm" value="{{ old('order', $post->order) }}" />
                        </div>

                    </div>
                    <div class="col-12 col-md-4">
                        {{-- password --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.password')</label>
                            <input type="text" name="password" class="form-control form-control-sm" value="{{ old('password', $post->password) }}" />
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        {{-- price --}}
                        <div class="form-item mb-3">
                            <label class="form-label required fw-bold fs-6">@lang('word.price')</label>
                            <input type="text" name="price" class="form-control form-control-sm" value="{{ old('price', $post->price) }}" />
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="mt-3">
            {{-- content --}}
            <div class="form-item mb-3">
                <label class="form-label fw-bold fs-6">@lang('word.content')</label>
                <textarea id="kt_docs_tinymce_basic" name="content" class="tox-target">{{ old('content', $post->content) }}</textarea>
            </div>
        </div>
    </div>

    {{-- Sizebar --}}
    <div class="col-12 col-md-3">

        {{-- Status --}}
        <div class="card">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.publish_related')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{-- status --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.status')</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="">@lang('word.please_select')</option>
                        @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ ($status===old('status', $post->status) || empty(old('status')) && $status == 'published') ? 'selected' :'' }}><b>@lang('word.' . $status)</b></option>
                        @endforeach
                    </select>
                </div>

                {{-- visibility --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.visibility')</label>
                    <select name="visibility" class="form-select form-select-sm" required>
                        <option value="">@lang('word.please_select')</option>
                        @foreach($visibilities as $visibility)
                        <option value="{{ $visibility }}" {{ ($visibility===old('visibility', $post->visibility) || empty(old('visibility')) && $visibility == 'public') ? 'selected' :'' }}><b>@lang('word.' . $visibility)</b></option>
                        @endforeach
                    </select>
                </div>

                {{-- published_at --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.published_at')</label>
                    <input type="text" name="published_at" value="{{ old('published_at', $post->published_at?->format('m/d/Y H:i:s'), now()->format('m/d/Y H:i:s'))  }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_default_now')" id="picker_published_at" />
                    <script>
                        window.addEventListener('DOMContentLoaded', (event) => {
                            $("#picker_published_at").daterangepicker({
                                autoUpdateInput: false,
                                // autoApply:true,
                                singleDatePicker: true,
                                showDropdowns: true,
                                drops:'bottom',
                                timePicker: true,
                                timePicker24Hour: true,
                                timePickerSeconds: true,
                                minYear: 1901,
                                maxYear: parseInt(moment().format("YYYY"),12),
                                locale: {
                                    cancelLabel: '清空',
                                    applyLabel: '設定',
                                    format: "MM/DD/YYYY hh:mm:ss",
                                }
                            }).on("apply.daterangepicker", function (e, picker) {
                                picker.element.val(picker.startDate.format(picker.locale.format));
                            }).on('cancel.daterangepicker', function(e, picker) {
                                picker.element.val('');
                            });
                        });
                    </script>
                </div>

                {{-- expired_at --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.expired_at')</label>
                    <input type="text" name="expired_at" value="{{ old('expired_at', $post->expired_at?->format('m/d/Y H:i:s'), now()->format('m/d/Y H:i:s'))  }}" class="form-control form-control-sm" placeholder="@lang('word.choose_date_or_leave_blank')" id="picker_expired_at" />
                    <script>
                        window.addEventListener('DOMContentLoaded', (event) => {
                            $("#picker_expired_at").daterangepicker({
                                autoUpdateInput: false,
                                // autoApply:true,
                                singleDatePicker: true,
                                showDropdowns: true,
                                drops:'bottom',
                                timePicker: true,
                                timePicker24Hour: true,
                                timePickerSeconds: true,
                                minYear: 1901,
                                maxYear: parseInt(moment().format("YYYY"),12),
                                locale: {
                                    cancelLabel: '清空',
                                    applyLabel: '設定',
                                    format: "MM/DD/YYYY hh:mm:ss",
                                }
                            }).on("apply.daterangepicker", function (e, picker) {
                                picker.element.val(picker.startDate.format(picker.locale.format));
                            }).on('cancel.daterangepicker', function(e, picker) {
                                picker.element.val('');
                            });
                        });
                    </script>
                </div>

                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit" disabled>
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>

                {{-- Preview --}}
                <div class="mb-3">
                    @foreach($post->websites as $preview_website)
                    <div><a href="{{ $wncms->getRoute('frontend.posts.single', ['slug' => $post->slug],false, $preview_website->domain) }}" target="_blank">@lang('word.preview_on', ['domain' => $preview_website->domain])</a></div>
                    @endforeach
                </div>

            </div>
        </div>

        {{-- Relationship --}}
        <div class="card mt-5">
            <div class="card-body p-2 p-md-5">
                {{-- website_id --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.website')</label>
                    @foreach($websites as $index => $_website)
                    <div class="col-12 col-md-3 mb-1 website_ids_checkbox">
                        <label class="form-check form-check-inline form-check-solid me-5">
                            <input class="form-check-input" name="website_ids[]" type="checkbox" value="{{ $_website->id }}"
                                @checked($post?->websites->contains($_website->id))
                            @checked(request()->routeIs('posts.create') && wncms()->isSelectedWebsite($_website))
                            />
                            <span class="fw-bold ps-2 fs-6">{{ $_website->domain }}</span>
                        </label>
                    </div>
                    @endforeach
                </div>

                {{-- user_id --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.author')</label>
                    <select name="user_id" class="form-select form-select-sm" required>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" @if($user->id == $post->user?->id) selected @endif>#{{ $user->id }} {{ $user->username }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Image --}}
        <div class="card mt-5">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.images')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{--thumbnail --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.thumbnail')</label>

                    <div class="image-input image-input-outline w-100 {{ !empty($post->getFirstMediaUrl('post_thumbnail')) ? '' : 'image-input-empty' }}" data-kt-image-input="true" style="background-image: url({{ !empty($post->getFirstMediaUrl('post_thumbnail')) ?: asset('wncms/images/placeholders/upload.png') }});background-position:center;">
                        <div class="image-input-wrapper w-100 h-100" style="background-image:{{ !empty($post->getFirstMediaUrl('post_thumbnail')) ? 'url('. $post->getFirstMediaUrl('post_thumbnail') .')' : 'none' }};aspect-ratio:16/10"></div>
                        <label class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="change" data-bs-toggle="tooltip" title="Change avatar">
                            <i class="fa fa-pencil fs-7"></i>
                            <input type="file" name="post_thumbnail" accept="image/*" />

                            <input type="hidden" name="post_thumbnail_remove" />
                        </label>
                        @if(!empty($post->exists) && request()->routeIs('posts.clone'))
                        <input type="hidden" name="post_thumbnail_clone_id" value="{{ $post->getFirstMediaUrl('post_thumbnail') ? $post->getMedia('post_thumbnail')->value('id') : '' }}" />
                        @endif

                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="cancel" data-bs-toggle="tooltip" title="Cancel avatar">
                            <i class="fa fa-rotate-left"></i>
                        </span>

                        <span class="btn btn-icon btn-circle btn-active-color-primary w-25px h-25px bg-body shadow" data-kt-image-input-action="remove" data-bs-toggle="tooltip" title="Remove avatar">
                            <i class="fa fa-times"></i>
                        </span>
                    </div>

                    <div class="form-text">@lang('word.allow_image_type')</div>
                </div>



                {{-- external_thumbnail --}}
                <div class="form-item mb-3">
                    <label class="form-label required fw-bold fs-6">@lang('word.external_thumbnail')</label>
                    <input type="text" name="external_thumbnail" class="form-control form-control-sm" value="{{ old('external_thumbnail', $post->external_thumbnail) }}" />
                </div>

                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit" disabled>
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>
            </div>
        </div>

        {{-- Switches --}}
        <div class="card mt-5">
            <div class="card-header border-0 cursor-pointer p-2 p-md-5">
                <div class="card-title m-0">
                    <h3 class="fw-bolder m-0">@lang('word.post_attribute')</h3>
                </div>
            </div>

            <div class="card-body p-2 p-md-5">

                {{-- is_pinned --}}
                <div class="row mb-1">
                    <label class="col-3 col-form-label fw-bold fs-6 py-1">@lang('word.is_pinned')</label>
                    <div class="col-8 d-flex align-items-center">
                        <div class="form-check form-check-custom form-switch fv-row">
                            <input type="hidden" name="is_pinned" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" name="is_pinned" value="1" {{ old('is_pinned', $post->is_pinned) ? 'checked' : '' }}/>
                            <label class="form-check-label"></label>
                        </div>
                    </div>
                </div>

                {{-- is_recommended --}}
                <div class="row mb-1">
                    <label class="col-3 col-form-label fw-bold fs-6 py-1">@lang('word.is_recommended')</label>
                    <div class="col-8 d-flex align-items-center">
                        <div class="form-check form-check-custom form-switch fv-row">
                            <input type="hidden" name="is_recommended" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" name="is_recommended" value="1" {{ old('is_recommended', $post->is_recommended) ? 'checked' : '' }}/>
                            <label class="form-check-label"></label>
                        </div>
                    </div>
                </div>

                {{-- is_dmca --}}
                <div class="row mb-5">
                    <label class="col-3 col-form-label fw-bold fs-6 py-1">@lang('word.is_dmca')</label>
                    <div class="col-8 d-flex align-items-center">
                        <div class="form-check form-check-custom form-switch fv-row">
                            <input type="hidden" name="is_dmca" value="0">
                            <input class="form-check-input w-35px h-20px" type="checkbox" name="is_dmca" value="1" {{ old('is_dmca', $post->is_dmca) ? 'checked' : '' }}/>
                            <label class="form-check-label"></label>
                        </div>
                    </div>
                </div>

                {{-- Publish --}}
                <div class="mb-3">
                    <button type="submit" wncms-btn-loading class="btn btn-primary w-100 wncms-submit" disabled>
                        @include('backend.parts.submit', ['label' => $submitLabelText ?? __('word.save_all')])
                    </button>
                </div>

            </div>
        </div>

    </div>
</div>

<script>
    window.addEventListener('DOMContentLoaded', (event) => {
        $(".wncms-submit").prop('disabled', false);

        const translatableFields = @json($post->getTranslatable());
        console.log('teast');
        console.log(translatableFields);

        translatableFields.forEach(field => {
            const label = document.querySelector(`label[for="${field}"]`);
            const input = document.querySelector(`input[name="${field}"]`);

            if (label && input) {
                const flagIcon = document.createElement("i");
                flagIcon.className = "fa-solid fa-language text-primary fa-lg ms-1";
                label.appendChild(flagIcon);
            }
        });
    });
</script>