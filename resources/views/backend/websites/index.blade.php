@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('websites.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                <div class="col-6 col-md-auto mb-3 ms-0 ms-md-2">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('wncms::word.submit')">
                </div>
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'websites',
            ])
        </div>
    </div>

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle text-nowrap mb-0 border border-2 border-dark">
                    <thead class="table-dark">
                        <tr class="fw-bold gs-0">
                            <th>@lang('wncms::word.action')</th>
                            <th>#</th>
                            <th>@lang('wncms::word.site_name')</th>
                            <th>@lang('wncms::word.domain')</th>
                            <th>@lang('wncms::word.theme')</th>
                            <th>@lang('wncms::word.remark')</th>
                            <th>@lang('wncms::word.created_at')</th>

                        </tr>
                    </thead>
                    <tbody class="fw-semibold text-gray-600">
                        @foreach($websites as $website)
                        <tr>
                            <td>
                                <a class="btn btn-sm btn-dark px-2 py-1 fw-bold" href="{{ route('websites.edit' , $website) }}">@lang('wncms::word.website_options')</a>
                                <a class="btn btn-sm btn-dark px-2 py-1 fw-bold" href="{{ route('websites.theme.options' , $website) }}">@lang('wncms::word.theme_options')</a>
                                @include('wncms::backend.parts.modal_delete', ['model' => $website, 'route' => route('websites.destroy', $website), 'btn_class' => 'px-2 py-1'])
                            </td>
                            <td>{{ $website->id }}</td>
                            <td><span class="px-2 py-1 rounded fw-bold">{{ $website->site_name }}</span></td>
                            <td><a href="//{{ $website->domain }}" target="_blank">{{ $website->domain }}</a></td>
                            <td>{{ $website->theme }}</td>
                            <td>{{ $website->remark }}</td>
                            <td>{{ $website->created_at }}</td>
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $websites->withQueryString()->links() }}
    </div>

@endsection