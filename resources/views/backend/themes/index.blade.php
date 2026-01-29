@extends('wncms::layouts.backend')

@section('content')
    @include('wncms::backend.parts.message')

    {{-- 工具欄 --}}
    <div class="card-header align-items-center pt-5 gap-2 gap-md-5">
        <div class="card-title">
            <form action="{{ route('themes.index') }}">
                <div class="row gx-1 align-items-center position-relative my-1">

                    @include('wncms::backend.common.default_toolbar_filters')

                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                    </div>
                </div>

                {{-- Checkboxes --}}
                <div class="d-flex">
                    @foreach (['show_detail'] as $show)
                        <div class="col-6 col-md-auto mb-3 ms-0">
                            <div class="form-check form-check-sm form-check-custom me-2">
                                <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if (request()->{$show}) checked @endif />
                                <label class="form-check-label fw-bold ms-1">@lang('wncms::word.' . $show)</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'themes',
            ])

            {{-- upload_theme --}}
            <button type="button" class="btn btn-sm btn-primary fw-bold mb-1 mb-1" data-bs-toggle="modal" data-bs-target="#modal_upload_theme">@lang('wncms::word.upload_theme')</button>
            <div class="modal fade" tabindex="-1" id="modal_upload_theme">
                <div class="modal-dialog">
                    <form action="{{ route('themes.upload') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title">@lang('wncms::word.upload_theme')</h3>
                            </div>

                            <div class="modal-body">
                                <div class="form-item">
                                    <input type="file" name="theme_file">
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                                <button type="submit" class="btn btn-primary fw-bold">@lang('wncms::word.submit')</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle text-nowrap mb-0 table-bordered">
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            {{-- <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th> --}}
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.thumbnail')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.valid_structure')</th>
                            {{-- <th>@lang('wncms::word.status')</th> --}}
                            <th>@lang('wncms::word.name')</th>
                            @if (request()->show_detail)
                                <th>@lang('wncms::word.description')</th>
                            @endif
                            <th>@lang('wncms::word.demo_url')</th>
                            @if (request()->show_detail)
                                <th>@lang('wncms::word.author')</th>
                                <th>@lang('wncms::word.current_version')</th>
                                <th>@lang('wncms::word.created_at')</th>
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif

                            @if (request()->show_detail)
                            @endif

                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach ($themes as $theme)
                            <tr>
                                {{-- <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $theme['id'] }}"/>
                                    </div>
                                </td> --}}

                                <td>
                                    {{-- Delete --}}
                                    @if (!in_array($theme['id'], ['default', 'starter']))
                                        <button type="button" class="btn btn-sm btn-danger fw-bold px-2 py-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal_confirm_delete_theme_{{ $theme['id'] }}">
                                            @lang('wncms::word.delete')
                                        </button>
                                        <div class="modal fade" id="modal_confirm_delete_theme_{{ $theme['id'] }}" tabindex="-1" aria-labelledby="label_confirm_delete_theme_{{ $theme['id'] }}" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <form action="{{ route('themes.delete', ['themeId' => $theme['id']]) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="label_confirm_delete_theme_{{ $theme['id'] }}">
                                                                @lang('wncms::word.confirm_delete_theme')
                                                            </h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="@lang('wncms::word.close')"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            @lang('wncms::word.are_you_sure_to_delete_theme', ['theme_name' => $theme['name']])
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('wncms::word.cancel')</button>
                                                            <button type="submit" class="btn btn-danger">@lang('wncms::word.confirm_delete')</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    @else
                                        <div title="@lang('wncms::word.default_theme_cannot_be_deleted')">
                                            <button type="button" class="btn btn-sm btn-secondary fw-bold px-2 py-1" disabled>
                                                @lang('wncms::word.delete')
                                            </button>

                                        </div>
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($theme['screenshot']))
                                        <img class="h-40px" src="{{ $theme['screenshot'] }}" alt="">
                                    @endif
                                </td>
                                <td>{{ $theme['id'] }}</td>
                                <td>@include('wncms::common.table_is_active', ['model' => $theme, 'active_column' => 'isValid'])</td>
                                {{-- <td>@include('wncms::common.table_status', ['model' => $theme])</td> --}}
                                <td>{{ $theme['name'] }}</td>
                                @if (request()->show_detail)
                                    <td class="mw-300px text-truncate text-hover-info" title="{{ $theme['description'] ?? '' }}">{{ $theme['description'] ?? '' }}</td>
                                @endif

                                <td>@include('wncms::common.table_url', ['url' => $theme['demo_url'] ?? ''])</td>
                                @if (request()->show_detail)
                                    <td>{{ $theme['author'] ?? '' }}</td>
                                    <td>{{ $theme['version'] ?? '' }}</td>
                                    <td>{{ $theme['created_at'] ?? '' }}</td>
                                    <td>{{ $theme['updated_at'] ?? '' }}</td>
                                @endif

                            <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card card-flush rounded overflow-hidden mt-5">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle text-nowrap mb-0 table-bordered">
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            <th>@lang('wncms::word.invalid_themes_are_found')</th>
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach ($invalidThemes as $invalidTheme)
                            <tr>
                                <td class="text-danger">{{ $invalidTheme['path'] ?? 'Unknown Path' }}</td>
                            <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('foot_js')
    <script>
        $('.model_index_checkbox').on('change', function() {
            if ($(this).is(':checked')) {
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>
@endpush
