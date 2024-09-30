@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')
    
    <div class="card card-flush">

        {{-- 工具欄 --}}
        <div class="card-header align-items-center pt-5 gap-2 gap-md-5">
            <div class="card-title">
                <form action="{{ route('users.websites') }}">
                    <div class="row gx-1 align-items-center position-relative my-1">

                        {{-- 搜索 --}}
                        <div class="d-flex align-items-center col-12 col-md-auto mb-3 ms-0">
                            <span class="svg-icon svg-icon-1 position-absolute ms-4">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                    <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
                                </svg>
                            </span>
                            <input type="text" name="keyword" value="{{ request()->keyword }}" data-kt-ecommerce-order-filter="search" class="form-control form-control-sm ps-14" placeholder="@lang('word.search')" />
                        </div>


                        {{-- 排序依據 --}}
                        <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                            <select name="user" class="form-select form-select-sm">
                                <option value="">@lang('word.select_user')</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" @if($user->id == request()->user) selected @endif>{{ $user->username }} - ID: {{ $user->id }}</option>
                                @endforeach
                            </select>
                        </div>


                        {{-- 排序依據 --}}
                        <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                            <select name="order" class="form-select form-select-sm">
                                <option value="">@lang('word.select_order')</option>
                                @foreach($orders as $order)
                                    <option value="{{ $order }}" @if($order == request()->order) selected @endif>@lang('word.' . $order)</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 大小 --}}
                        <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                            <select name="sort" class="form-select form-select-sm">
                                <option value="">@lang('word.select_sort')</option>
                                @foreach(['asc','desc'] as $sort)
                                    <option value="{{ $sort }}" @if($sort == request()->sort) selected @endif>@lang('word.sort_by_'. $sort)</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                            <input type="submit" class="btn btn-sm btn-dark fw-bold" value="@lang('word.submit')">
                        </div>
                    </div>
                </form>

                <div id="kt_ecommerce_report_sales_export" class="d-none"></div>
            </div>

        </div>
        
        {{-- Buttons --}}
        <div class="card-header">
            <div class="card-toolbar flex-row-fluid gap-1">

                {{-- Assign--}}
                <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#assign_user_to_manage">@lang('word.assign_user_to_manage')</button>
                <div class="modal fade" tabindex="-1" id="assign_user_to_manage">

                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('word.please_choose_users_to_manage_website')</h3>
                                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                            </div>

                            <div class="modal-body">
                                <p class="text-muted fw-bold">@lang('word.assign_user_description')</p>

                                <div class="row mb-6">
                                    <div class="col">
                                        <label class="form-label">@lang('word.select_users')</label>
                                        <input name="user_ids" class="form-control d-flex align-items-center" value="" id="kt_tagify_assign_user_to_manage" />
                                    </div>
                                </div>

                                {{-- Tagify參數 --}}
                                <script type="text/javascript">
                                        window.addEventListener('DOMContentLoaded', (event) => {
                                        var inputElm = document.querySelector('#kt_tagify_assign_user_to_manage');

                                            const usersList = [
                                                @foreach($users as $user)
                                                { value: {{ $user->id }}, name: '{{ $user->username }}', email: '{{ $user->email }}' },
                                                @endforeach
                                            ];

                                            function tagTemplate(tagData) {
                                                return `
                                                    <tag title="${(tagData.title || tagData.email)}"
                                                            contenteditable='false'
                                                            spellcheck='false'
                                                            tabIndex="-1"
                                                            class="${this.settings.classNames.tag} ${tagData.class ? tagData.class : ""}"
                                                            ${this.getAttributes(tagData)}>
                                                        <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
                                                        <div class="d-flex align-items-center">
                                                            <span class='tagify__tag-text'>${tagData.name}</span>
                                                        </div>
                                                    </tag>
                                                `
                                            }

                                            function suggestionItemTemplate(tagData) {
                                                return `
                                                    <div ${this.getAttributes(tagData)}
                                                        class='tagify__dropdown__item d-flex align-items-center ${tagData.class ? tagData.class : ""}'
                                                        tabindex="0"
                                                        role="option">
                                                        <div class="d-flex flex-column">
                                                            <strong>${tagData.name}</strong>
                                                            <span>${tagData.email}</span>
                                                        </div>
                                                    </div>
                                                `
                                            }

                                            // initialize Tagify on the above input node reference
                                            var tagify = new Tagify(inputElm, {
                                                tagTextProp: 'name', // very important since a custom template is used with this property as text. allows typing a "value" or a "name" to match input with whitelist
                                                enforceWhitelist: true,
                                                skipInvalid: true, // do not remporarily add invalid tags
                                                dropdown: {
                                                    closeOnSelect: false,
                                                    enabled: 0,
                                                    classname: 'users-list',
                                                    searchKeys: ['name', 'email']  // very important to set by which keys to search for suggesttions when typing
                                                },
                                                templates: {
                                                    tag: tagTemplate,
                                                    dropdownItem: suggestionItemTemplate
                                                },
                                                whitelist: usersList
                                            })

                                            tagify.on('dropdown:show dropdown:updated', onDropdownShow)
                                            tagify.on('dropdown:select', onSelectSuggestion)

                                            var addAllSuggestionsElm;

                                            function onDropdownShow(e) {
                                                var dropdownContentElm = e.detail.tagify.DOM.dropdown.content;

                                                if (tagify.suggestedListItems.length > 1) {
                                                    addAllSuggestionsElm = getAddAllSuggestionsElm();

                                                    // insert "addAllSuggestionsElm" as the first element in the suggestions list
                                                    dropdownContentElm.insertBefore(addAllSuggestionsElm, dropdownContentElm.firstChild)
                                                }
                                            }

                                            function onSelectSuggestion(e) {
                                                if (e.detail.elm == addAllSuggestionsElm)
                                                    tagify.dropdown.selectAll.call(tagify);
                                            }

                                            // create a "add all" custom suggestion element every time the dropdown changes
                                            function getAddAllSuggestionsElm() {
                                                // suggestions items should be based on "dropdownItem" template
                                                return tagify.parseTemplate('dropdownItem', [{
                                                    class: "addAll",
                                                    name: "{{ __('word.select_all') }}",
                                                    email: tagify.settings.whitelist.reduce(function (remainingSuggestions, item) {
                                                        return tagify.isTagDuplicate(item.value) ? remainingSuggestions : remainingSuggestions + 1
                                                    }, 0) + " {{ __('word.num_of_managers') }}"
                                                }]
                                            )
                                        }
                                    });
                        
                                </script>
                            </div>
                
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                                <button type="button" class="btn btn-sm btn-info fw-bold assign_user_to_manage_submit" data-route="{{ route('users.websites.assign') }}">
                                    <span class="indicator-label">@lang('word.assign')</span>
                                    <span class="indicator-progress">@lang('word.please_wait')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </div>
                    </div>
          
                </div>

                {{-- Remove--}}
                <button class="btn btn-sm btn-danger fw-bold" data-bs-toggle="modal" data-bs-target="#remove_user_from_managing">@lang('word.remove_user_from_managing')</button>
                <div class="modal fade" tabindex="-1" id="remove_user_from_managing">

                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('word.please_choose_users_to_remove_from')</h3>
                                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"><span class="svg-icon svg-icon-1"></span></div>
                            </div>

                            <div class="modal-body">
                                <p class="text-muted fw-bold">@lang('word.remove_user_description')</p>

                                <div class="row mb-6">
                                    <div class="col">
                                        <label class="form-label">@lang('word.select_users')</label>
                                        <input name="user_ids" class="form-control d-flex align-items-center" value="" id="kt_tagify_remove_user_from_managing" />
                                    </div>
                                </div>

                                {{-- Tagify參數 --}}
                                <script type="text/javascript">
                                        window.addEventListener('DOMContentLoaded', (event) => {
                                        var inputElm = document.querySelector('#kt_tagify_remove_user_from_managing');

                                            const usersList = [
                                                @foreach($users as $user)
                                                { value: {{ $user->id }}, name: '{{ $user->username }}', email: '{{ $user->email }}' },
                                                @endforeach
                                            ];

                                            function tagTemplate(tagData) {
                                                return `
                                                    <tag title="${(tagData.title || tagData.email)}"
                                                            contenteditable='false'
                                                            spellcheck='false'
                                                            tabIndex="-1"
                                                            class="${this.settings.classNames.tag} ${tagData.class ? tagData.class : ""}"
                                                            ${this.getAttributes(tagData)}>
                                                        <x title='' class='tagify__tag__removeBtn' role='button' aria-label='remove tag'></x>
                                                        <div class="d-flex align-items-center">
                                                            <span class='tagify__tag-text'>${tagData.name}</span>
                                                        </div>
                                                    </tag>
                                                `
                                            }

                                            function suggestionItemTemplate(tagData) {
                                                return `
                                                    <div ${this.getAttributes(tagData)}
                                                        class='tagify__dropdown__item d-flex align-items-center ${tagData.class ? tagData.class : ""}'
                                                        tabindex="0"
                                                        role="option">
                                                        <div class="d-flex flex-column">
                                                            <strong>${tagData.name}</strong>
                                                            <span>${tagData.email}</span>
                                                        </div>
                                                    </div>
                                                `
                                            }

                                            // initialize Tagify on the above input node reference
                                            var tagify = new Tagify(inputElm, {
                                                tagTextProp: 'name', // very important since a custom template is used with this property as text. allows typing a "value" or a "name" to match input with whitelist
                                                enforceWhitelist: true,
                                                skipInvalid: true, // do not remporarily add invalid tags
                                                dropdown: {
                                                    closeOnSelect: false,
                                                    enabled: 0,
                                                    classname: 'users-list',
                                                    searchKeys: ['name', 'email']  // very important to set by which keys to search for suggesttions when typing
                                                },
                                                templates: {
                                                    tag: tagTemplate,
                                                    dropdownItem: suggestionItemTemplate
                                                },
                                                whitelist: usersList
                                            })

                                            tagify.on('dropdown:show dropdown:updated', onDropdownShow)
                                            tagify.on('dropdown:select', onSelectSuggestion)

                                            var addAllSuggestionsElm;

                                            function onDropdownShow(e) {
                                                var dropdownContentElm = e.detail.tagify.DOM.dropdown.content;

                                                if (tagify.suggestedListItems.length > 1) {
                                                    addAllSuggestionsElm = getAddAllSuggestionsElm();

                                                    // insert "addAllSuggestionsElm" as the first element in the suggestions list
                                                    dropdownContentElm.insertBefore(addAllSuggestionsElm, dropdownContentElm.firstChild)
                                                }
                                            }

                                            function onSelectSuggestion(e) {
                                                if (e.detail.elm == addAllSuggestionsElm)
                                                    tagify.dropdown.selectAll.call(tagify);
                                            }

                                            // create a "add all" custom suggestion element every time the dropdown changes
                                            function getAddAllSuggestionsElm() {
                                                // suggestions items should be based on "dropdownItem" template
                                                return tagify.parseTemplate('dropdownItem', [{
                                                    class: "addAll",
                                                    name: "{{ __('word.select_all') }}",
                                                    email: tagify.settings.whitelist.reduce(function (remainingSuggestions, item) {
                                                        return tagify.isTagDuplicate(item.value) ? remainingSuggestions : remainingSuggestions + 1
                                                    }, 0) + " {{ __('word.num_of_managers') }}"
                                                }]
                                            )
                                        }
                                    });
                        
                                </script>
                            </div>
                
                            <div class="modal-footer">
                                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('word.cancel')</button>
                                <button type="button" class="btn btn-sm btn-info fw-bold remove_user_from_managing_submit" data-route="{{ route('users.websites.remove') }}">
                                    <span class="indicator-label">@lang('word.remove')</span>
                                    <span class="indicator-progress">@lang('word.please_wait')...<span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                                </button>
                            </div>
                        </div>
                    </div>
          
                </div>


            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle text-nowrap mb-0" id="kt_ecommerce_report_sales_table">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                    <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>#</th>
                            <th>@lang('word.site_name')</th>
                            <th>@lang('word.site_url')</th>
                            <th>@lang('word.site_traffic')</th>
                            <th>@lang('word.manager')</th>
                            {{-- <th>@lang('word.site_click')</th> --}}
                            <th>@lang('word.remark')</th>
                            <th>@lang('word.created_at')</th>
                            {{-- <th>@lang('word.action')</th> --}}
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($websites as $website)
                        <tr>
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $website->id }}"/>
                                </div>
                            </td>
                            <td>{{ $website->id }}</td>
                            <td><span class="px-2 py-1 rounded fw-bold">{{ $website->site_name }}</span></td>
                            <td><a href="{{ wncms_add_http($website->domain) }}" target="_blank">{{ $website->domain }}</a></td>
                            <td>{{ $website->traffics_count }}</td>
                            <td>
                                @foreach($website->users as $user)
                                <span class="badge badge-light-success border border-success">{{ $user->username }}</span>
                                @endforeach
                            </td>
                            {{-- <td>{{ $website->clicks_count }}</td> --}}
                            <td>{{ $website->remark }}</td>
                            <td>{{ $website->created_at }}</td>


                       
             
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@endsection