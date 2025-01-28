<div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-3">
	<div id="kt_app_toolbar_container" class="app-container container-fluid d-flex flex-stack">
        {{-- Page Title --}}
        <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
            <h1 class="page-heading d-flex text-dark fw-bold fs-3 justify-content-center align-items-center my-0">
                <span>{{ $page_title ?? (gss('hide_empty_page_title') ? '' : __('wncms::word.page_title_not_set')) }}</span>


                @role(['super-admin', 'admin'])
                    @if(!empty($quickLinks = collect(json_decode(gss('quick_links'), true))) && !$quickLinks->where('route', request()->route()->getName())->count())
                        <form class="ms-1 small" action="{{ route('settings.quick.add') }}" method="POST">
                            @csrf
                            <input type="hidden" name="name" value="{{ $page_title ?? '' }}">
                            <input type="hidden" name="route" value="{{ request()->route()->getName() }}">
                            <input type="hidden" name="url" value="{{ request()->getPathInfo() }}">
                            <button type="submit" class="btn btn-link d-flex align-items-center" title="@lang('wncms::word.add_to_quick_link')">
                                <i class="fs-6 text-warning fa-regular fa-star"></i>
                            </button>
                        </form>
                    @else
                        <form class="ms-1 small" action="{{ route('settings.quick.remove') }}" method="POST">
                            @csrf
                            <input type="hidden" name="route" value="{{ request()->route()->getName() }}">
                            <input type="hidden" name="name" value="{{ $page_title ?? '' }}">
                            <input type="hidden" name="url" value="{{ request()->getPathInfo() }}">
                            <button type="submit" class="btn btn-link d-flex align-items-center" title="@lang('wncms::word.remove_from_quick_link')">
                                <i class="fs-6 text-warning fas fa-star"></i>
                            </button>
                        </form>
                    @endif
                @endrole
            </h1>
            
            {{-- <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                <li class="breadcrumb-item text-muted">
                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">@lang('wncms::word.dashboard')</a>
                </li>

                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">{{ $sub_item_title ?? 'xxxxx' }}</li>
                <li class="breadcrumb-item"><span class="bullet bg-gray-400 w-5px h-2px"></span></li>
                <li class="breadcrumb-item text-muted">{{ $sub_item_title ?? 'xxxxx' }}</li>

            </ul> --}}
        </div>

		{{-- <div class="d-flex align-items-center gap-2 gap-lg-3">
			<a href="#" class="btn btn-sm fw-bold bg-body btn-color-gray-700 btn-active-color-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_app">BTN 1</a>
			<a href="#" class="btn btn-sm fw-bold btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_new_target">BTN 2</a>
		</div> --}}
	</div>
</div>
