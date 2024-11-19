@extends('wncms::layouts.backend')

@section('content')

    @include('wncms::backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('subscriptions.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('wncms::backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- Filter by User ID --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="text" name="user_id" class="form-control form-control-sm" placeholder="@lang('wncms::word.user_id')" value="{{ request('user_id') }}">
                </div>

                {{-- Filter by Status --}}
                <div class="col-6 col-md-auto mb-3 ms-0">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">@lang('wncms::word.select_status')</option>
                        @foreach(['active', 'expired', 'cancelled'] as $status)
                            <option value="{{ $status }}" @if(request('status') === $status) selected @endif>@lang('wncms::word.' . $status)</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold mb-1" value="@lang('wncms::word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('wncms::word.' . $show)</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bulk Create + Clone + Bulk Delete --}}
            @include('wncms::backend.common.default_toolbar_buttons', [
                'model_prefix' => 'subscriptions',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $subscriptions])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">

                    {{-- thead --}}
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            {{-- Checkbox --}}
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('wncms::word.action')</th>
                            <th>@lang('wncms::word.id')</th>
                            <th>@lang('wncms::word.user_id')</th>
                            <th>@lang('wncms::word.plan')</th>
                            <th>@lang('wncms::word.status')</th>
                            <th>@lang('wncms::word.subscribed_at')</th>
                            <th>@lang('wncms::word.expired_at')</th>

                            @if(request()->show_detail)
                                <th>@lang('wncms::word.updated_at')</th>
                            @endif
                        </tr>
                    </thead>

                    {{-- tbody --}}
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($subscriptions as $subscription)
                            <tr>
                                {{-- Checkboxes --}}
                                <td>
                                    <div class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $subscription->id }}"/>
                                    </div>
                                </td>
                                {{-- Actions --}}
                                <td>
                                    <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('subscriptions.edit' , $subscription) }}">@lang('wncms::word.edit')</a>
                                    @include('wncms::backend.parts.modal_delete' , ['model'=>$subscription , 'route' => route('subscriptions.destroy' , $subscription), 'btn_class' => 'px-2 py-1'])
                                </td>

                                {{-- Data --}}
                                <td>{{ $subscription->id }}</td>
                                <td>{{ $subscription->user_id }}</td>
                                <td>{{ $subscription->plan->name }}</td>
                                <td>@lang('wncms::word.' . $subscription->status)</td>
                                <td>{{ $subscription->subscribed_at }}</td>
                                <td>{{ $subscription->expired_at ?? __('wncms::word.not_applicable') }}</td>

                                @if(request()->show_detail)
                                    <td>{{ $subscription->updated_at }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('wncms::backend.common.showing_item_of_total', ['models' => $subscriptions])

    {{-- Pagination --}}
    <div class="mt-5">
        {{ $subscriptions->withQueryString()->links() }}
    </div>

@endsection

@push('foot_js')
    <script>
        //修改checkbox時直接提交
        $('.model_index_checkbox').on('change', function(){
            if($(this).is(':checked')){
                $(this).val('1');
            } else {
                $(this).val('0');
            }
            $(this).closest('form').submit();
        })
    </script>
@endpush
