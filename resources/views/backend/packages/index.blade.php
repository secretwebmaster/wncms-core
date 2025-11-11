@extends('wncms::layouts.backend')

@section('content')

@include('wncms::backend.parts.message')

{{-- Toolbar --}}
<div class="wncms-toolbar-filter mt-5">
    <div class="row gx-1 align-items-center position-relative my-1">
        @include('wncms::backend.common.default_toolbar_filters')
    </div>
</div>

{{-- Packages Table --}}
<div class="card card-flush rounded overflow-hidden mt-5">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover table-bordered align-middle text-nowrap mb-0">
                <thead class="table-dark">
                    <tr class="text-start fw-bold gs-0">
                        <th width="15%">@lang('wncms::word.actions')</th>
                        <th width="5%">@lang('wncms::word.id')</th>
                        <th>@lang('wncms::word.name')</th>
                        <th>@lang('wncms::word.description')</th>
                        <th>@lang('wncms::word.author')</th>
                        <th>@lang('wncms::word.version')</th>
                        <th>@lang('wncms::word.status')</th>
                        <th>@lang('wncms::word.path')</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($packages as $packageId => $packageInfo)
                    @php
                        $record     = $installed[$packageId] ?? null;
                        $active     = $record?->status === 'active';
                        $displayId  = $record?->id ?? $loop->index + 1;

                        $info       = $packageInfo['info'] ?? [];
                        $name       = $info['name'] 
                                        ?? $record?->name 
                                        ?? ucfirst($packageId);

                        $desc       = $info['description'] 
                                        ?? $record?->description 
                                        ?? ($packageInfo['title'] ?? '');

                        $author     = $info['author'] 
                                        ?? $record?->author 
                                        ?? '-';

                        $version    = $info['version'] 
                                        ?? $record?->version 
                                        ?? '1.0.0';

                        $path       = $record?->path 
                                        ?? ($packageInfo['paths']['base'] ?? '—');
                    @endphp

                    <tr>
                        <td>
                            {{-- Activate / Deactivate --}}
                            @if(!$active)
                                <button class="btn btn-sm btn-primary fw-bold px-2 py-1"
                                    wncms-btn-ajax
                                    wncms-btn-swal
                                    data-original-text="@lang('wncms::word.submit')"
                                    data-loading-text="@lang('wncms::word.installing').."
                                    data-success-text="@lang('wncms::word.successfully_installed')"
                                    data-fail-text="@lang('wncms::word.fail_to_submit')"
                                    data-route="{{ route('packages.activate', ['key' => $packageId]) }}"
                                    data-method="POST"
                                >@lang('wncms::word.activate')</button>
                            @else
                                {{-- deactivate_package --}}
                                <button type="button" class="btn btn-sm btn-danger fw-bold px-2 py-1" data-bs-toggle="modal" data-bs-target="#modal_deactivate_package_{{ $packageId }}">@lang('wncms::word.deactivate')</button>
                                <div class="modal fade" tabindex="-1" id="modal_deactivate_package_{{ $packageId }}">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h3 class="modal-title">@lang('wncms::word.deactivate_package')</h3>
                                            </div>
                                
                                            <div class="modal-body">
                                                @lang('wncms::word.deactivate_package_confirmation', ['package_name' => $name])
                                            </div>
                                
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
                                                <button class="btn btn-danger fw-bold" 
                                                    wncms-btn-ajax
                                                    wncms-btn-swal
                                                    data-original-text="@lang('wncms::word.submit')"
                                                    data-loading-text="@lang('wncms::word.loading').."
                                                    data-success-text="@lang('wncms::word.submitted')"
                                                    data-fail-text="@lang('wncms::word.fail_to_submit')"
                                                    data-route="{{ route('packages.deactivate', ['key' => $packageId]) }}"
                                                    data-method="POST"
                                                >@lang('wncms::word.deactivate_and_delete_data')</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            {{-- Optional future update button --}}
                            @if($active)
                            <button class="btn btn-sm btn-info fw-bold px-2 py-1" disabled>@lang('wncms::word.update')</button>
                            @endif
                        </td>
                        <td>{{ $packageId }}</td>
                        <td class="fw-bold">{{ $name }}</td>
                        <td class="small text-muted">{{ $desc ?: '-' }}</td>
                        <td>{{ $author }}</td>
                        <td>{{ $version }}</td>
                        <td>
                            @if($active)
                            <span class="badge bg-success">@lang('wncms::word.active')</span>
                            @else
                            <span class="badge bg-secondary">@lang('wncms::word.inactive')</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ Str::limit($path, 50) }}</td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-10">
                            @lang('wncms::word.no_packages_found')
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection