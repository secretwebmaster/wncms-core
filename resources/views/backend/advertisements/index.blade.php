@extends('layouts.backend')

@section('content')

    @include('backend.parts.message')

    {{-- WNCMS toolbar filters --}}
    <div class="wncms-toolbar-filter mt-5">
        <form action="{{ route('advertisements.index') }}">
            <div class="row gx-1 align-items-center position-relative my-1">

                @include('backend.common.default_toolbar_filters')

                {{-- Add custom toolbar item here --}}

                {{-- Example --}}
                @if(!empty($positions))
                    <div class="col-6 col-md-auto mb-3 ms-0">
                        <select name="position" class="form-select form-select-sm">
                            <option value="">@lang('word.select_website')</option>
                            @foreach($positions as $position)
                                <option value="{{ $position }}" @if($position == request()->position) selected @endif>@lang('word.' . $position)</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-6 col-md-auto mb-3 ms-0">
                    <input type="submit" class="btn btn-sm btn-primary fw-bold" value="@lang('word.submit')">
                </div>
            </div>

            {{-- Checkboxes --}}
            <div class="d-flex flex-wrap">
                @foreach(['show_detail', 'show_view', 'show_click'] as $show)
                    <div class="mb-3 ms-0">
                        <div class="form-check form-check-sm form-check-custom me-2">
                            <input class="form-check-input model_index_checkbox" name="{{ $show }}" type="checkbox" @if(request()->{$show}) checked @endif/>
                            <label class="form-check-label fw-bold ms-1">@lang('word.' . $show)</label>
                        </div>
                    </div>
                @endforeach
            </div>
        </form>
    </div>

    {{-- WNCMS toolbar buttons --}}
    <div class="wncms-toolbar-buttons mb-5">
        <div class="card-toolbar flex-row-fluid gap-1">
            {{-- Create + Bilk Create + Clone + Bulk Delete --}}
            @include('backend.common.default_toolbar_buttons', [
                'model_prefix' => 'advertisements',
            ])
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $advertisements])

    {{-- Model Data --}}
    <div class="card card-flush rounded overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-nowrap mb-0">
                    <thead class="table-dark">
                        <tr class="text-start fw-bold gs-0">
                            <th class="w-10px pe-2">
                                <div class="form-check form-check-sm form-check-custom me-3">
                                    <input class="form-check-input border border-2 border-white" type="checkbox" data-kt-check="true" data-kt-check-target="#table_with_checks .form-check-input" value="1" />
                                </div>
                            </th>
                            <th>@lang('word.action')</th>
                            <th>@lang('word.id')</th>
                            <th>@lang('word.website_id')</th>
                            <th>@lang('word.status')</th>
                            <th>@lang('word.expired_at')</th>
                            <th>@lang('word.image')</th>
                            <th>@lang('word.name')</th>
                            <th>@lang('word.type')</th>
                            <th>@lang('word.position')</th>
 
                            @if(request()->show_view)
                            <th>@lang('word.view_count')</th>
                            @endif
                            
                            @if(request()->show_click)
                            <th>@lang('word.click_count')</th>
                            @endif

                            <th>@lang('word.order')</th>
                            <th>@lang('word.cta_text')</th>
                            <th>@lang('word.url')</th>
                            <th>@lang('word.cta_text_2')</th>
                            <th>@lang('word.url_2')</th>
                            <th>@lang('word.remark')</th>
                            <th>@lang('word.text_color')</th>
                            <th>@lang('word.background_color')</th>
                            <th>@lang('word.advertisement_script')</th>
                            <th>@lang('word.style')</th>
                            <th>@lang('word.created_at')</th>

                            @if(request()->show_detail)
                            <th>@lang('word.updated_at')</th>
                            @endif
                            
                        </tr>
                    </thead>
                    <tbody id="table_with_checks" class="fw-semibold text-gray-600">
                        @foreach($advertisements as $advertisement)
                        <tr>
                            {{-- Checkbox --}}
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $advertisement->id }}"/>
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td>
                                <a class="btn btn-sm btn-dark fw-bold px-2 py-1" href="{{ route('advertisements.edit' , $advertisement) }}">@lang('word.edit')</a>
                                <a class="btn btn-sm btn-info fw-bold px-2 py-1" href="{{ route('advertisements.clone' , $advertisement) }}">@lang('word.clone')</a>
                                @include('backend.parts.modal_delete' , ['model'=>$advertisement , 'route' => route('advertisements.destroy' , $advertisement), 'btn_class' => 'px-2 py-1'])
                            </td>

                            {{-- Data --}}
                            <td>{{ $advertisement->id }}</td>
                            <td> <a href="({{ $advertisement->website?->id }})" target="_blank">#{{ $advertisement->website?->id }}</a> </td>
                            <td>{{ $advertisement->status }}</td>
                            <td>@include('common.table_date', ['model' => $advertisement, 'column' => 'expired_at'])</td>
                            <td>@include('common.table_image', ['model' => $advertisement, 'attribute' => 'thumbnail'])</td>
                            <td>{{ $advertisement->name }}</td>
                            <td>{{ $advertisement->type }}</td>
                            <td>{{ $advertisement->position }}</td>

                            @if(request()->show_view)
                            <td>{{ $advertisement->view_count }}</td>
                            @endif
    
                            @if(request()->show_click)
                            <td>{{ $advertisement->click_count }}</td>
                            @endif

                            <td>{{ $advertisement->order }}</td>
                            <td>{{ $advertisement->cta_text }}</td>
                            <td>@include('common.table_url', ['url' => $advertisement->url])</td>
                            <td>{{ $advertisement->cta_text_2 }}</td>
                            <td>@include('common.table_url', ['url' => $advertisement->url_2])</td>
                            <td>{{ $advertisement->remark }}</td>
                            <td>{{ $advertisement->text_color }}</td>
                            <td>{{ $advertisement->background_color }}</td>
                            <td class="mw-200px text-truncate" title="{{ $advertisement->code }}">{{ $advertisement->code }}</td>
                            <td>{{ $advertisement->style }}</td>
                            <td>{{ $advertisement->created_at }}</td>

                            @if(request()->show_detail)
                            <td>{{ $advertisement->updated_at }}</td>
                            @endif
                            
                        <tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Index --}}
    @include('backend.common.showing_item_of_total', ['models' => $advertisements])

    {{-- Pagination --}}
    {{-- <div class="mt-5">
        {{ $advertisements->withQueryString()->links() }}
    </div> --}}

@endsection

@push('foot_js')
    <script>
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