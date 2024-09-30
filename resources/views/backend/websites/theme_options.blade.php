@extends('layouts.backend')

@push('head_css')
<link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}">
<style>
    .nav-link.active{
        font-weight: bold
    }
</style>
@endpush

@section('content')

@include('backend.parts.message')

<div class="row mx-auto mb-3 gx-1">
    <div class="ms-0 mb-1 mb-md-0 col-12 col-md-auto"><a href="{{ wncms_add_https($_website->domain) }}" target="_blank" class="btn btn-sm btn-info fw-bold text-truncate w-100">@lang('word.current_website'): {{ $_website->site_name }} ({{ $_website->domain }})</a></div>
    <div class="ms-0 ms-md-1 mb-1 mb-md-0 col-6 col-md-auto"><span class="btn btn-sm btn-danger fw-bold text-truncate w-100">@lang('word.current_theme'): {{ $_website->theme }}</span></div>
    <div class="ms-0 ms-md-1 mb-1 mb-md-0 col-6 col-md-auto"><a href="{{ route('websites.edit', $_website) }}" class="btn btn-sm btn-primary fw-bold text-truncate w-100">@lang('word.switch_to_edit_website')</a></div>
    <div class="ms-0 ms-md-1 mb-1 mb-md-0 col-6 col-md-auto"><button type="button" class="btn btn-sm btn-primary fw-bold text-truncate w-100" data-bs-toggle="modal" data-bs-target="#clone_theme_options_from_another_website">@lang('word.clone_theme_options_from_another_website')</button></div>
    <div class="ms-0 ms-md-1 mb-1 mb-md-0 col-6 col-md-auto"><button type="button" class="btn btn-sm btn-primary fw-bold text-truncate w-100" data-bs-toggle="modal" data-bs-target="#modal_import_default_theme_option">@lang('word.import_default_theme_option')</button></div>
</div>

<form class="form" method="POST" action="{{ route('websites.theme.options.update' , $_website) }}" enctype="multipart/form-data">

    <div class="card show">
        @csrf
        @method('PUT')
        <div class="card-body border-top px-5 py-3">

            <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                @foreach($option_tabs ?? [] as $tab_name => $options)
                    <li class="nav-item">
                        <a class="nav-link @if($loop->iteration == 1) active @endif" 
                            data-bs-toggle="tab" href="#tab_{{ $tab_name }}"
                            data-bs-target="#tab_{{ $tab_name }}"
                            >@lang("{$_website->theme}.{$tab_name}")</a>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content">
                @foreach($option_tabs ?? [] as $tab_name => $options)
                    <div class="tab-pane fade @if($loop->iteration == 1) show active @endif" id="tab_{{ $tab_name }}" role="tabpanel">
                        @foreach($options as $option_index => $option)
                            @include('backend.parts.inputs' , [
                                'website'=> $_website,
                                'option_index'=>$option_index,
                                'option'=>$option,
                                'current_options'=>$current_options,
                            ])
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-5 text-end">
        <button type="submit" wncms-btn-loading class="btn btn-primary fw-bold wncms-submit w-100 w-md-auto">
            @include('backend.parts.submit', ['label' => __('word.save_all')])
        </button>
    </div>
</form>

{{-- Modal clone theme option --}}
<div class="modal fade" tabindex="-1" id="clone_theme_options_from_another_website">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form_clone_theme_options_from_another_website" action="{{ route('websites.theme.clone', $_website) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h3 class="modal-title">@lang('word.clone_theme_options_from_another_website')</h3>
                </div>
    
                <div class="modal-body">
                    @if($websites->isNotEmpty())
                        <select name="from_website_id" id="" class="form-select">
                            <option value="">@lang('word.please_select')</option>
                            @foreach($websites as $websiteOption)
                            <option value="{{ $websiteOption->id }}">{{ $websiteOption->domain }}</option>
                            @endforeach
                        </select>
                    @else
                        <p>@lang('word.no_other_websites_using_this_theme')</p>
                    @endif
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                    <button type="submit" class="btn btn-primary" form-id="form_clone_theme_options_from_another_website">@lang('word.submit')</button>
                </div>
            </form>
           
        </div>
    </div>
</div>

{{-- Modal Import default theme option --}}
<div class="modal fade" tabindex="-1" id="modal_import_default_theme_option">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="form_import_default_theme_option" action="{{ route('websites.theme.import_default_option', $_website) }}" method="POST">
                @csrf
                <input type="hidden" name="website_id" value="{{ $_website->id }}">
                <div class="modal-header">
                    <h3 class="modal-title">@lang('word.import_default_theme_option')</h3>
                </div>
    
                <div class="modal-body">
                    <div class="alert alert-danger">@lang('word.importing_default_theme_options_will_override_your_current_options')</div>
                    <input type="text" class="form-control" name="confirmation" placeholder="@lang('word.enter_default_to_confirm')" >
                </div>
    
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">@lang('word.close')</button>
                    <button type="submit" class="btn btn-danger fw-bold" form-id="form_import_default_theme_option">@lang('word.confirm')</button>
                </div>
            </form>
           
        </div>
    </div>
</div>

@endsection

@push('foot_js')
    @include('common.js.tinymce')
    
    <script src="{{ asset('wncms/js/jquery.dragsort.min.js?v=' . wncms_get_version('js')) }}"></script>
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {

            var activeThemeOptionTabCookieName = 'activeThemeOptionTab' + '{{ $_website->id }}'

            // Save the active tab to a cookie when a tab is shown
            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                var tabName = $(e.target).attr('href').replace('#tab_', '');
                WNCMS.Cookie.Set(activeThemeOptionTabCookieName, tabName, 7); // Cookie will expire in 365 days
            });

            // Activate the tab on page load
            activateTabFromCookie();

            // Function to activate the tab based on the cookie value
            function activateTabFromCookie() {
                var activeThemeOptionTab = WNCMS.Cookie.Get(activeThemeOptionTabCookieName);

                if (activeThemeOptionTab) {
                    var triggerEl  = document.querySelector('[data-bs-target="#tab_'+ activeThemeOptionTab +'"]')
                    var  tabTrigger  = new bootstrap.Tab(triggerEl)
                    tabTrigger.show()
                }
            }


            //color picker
            var input_names = [];
            var current_colors = [];
            var pickrs = [];
            $(".colorpicker-input").each(function(index , item){

                input_names[index] = $(this).attr('data-input');
                current_colors[index] = $(this).attr('data-current');
                var input_el = $(this).parent().find('input[name="'+ input_names[index] +'"]');


                var pickr = Pickr.create({
                    el: ".colorpicker-input",
                    theme: "classic", // or 'monolith', or 'nano'
                    // default: "#405189",
                    useAsButton: false,
                    padding: 8,
                    closeWithKey: 'Escape',
                    defaultRepresentation: 'HEX',
                    swatches: [
                        "rgba(244, 67, 54, 1)",
                        "rgba(233, 30, 99, 0.95)",
                        "rgba(156, 39, 176, 0.9)",
                        "rgba(103, 58, 183, 0.85)",
                        "rgba(63, 81, 181, 0.8)",
                        "rgba(33, 150, 243, 0.75)",
                        "rgba(3, 169, 244, 0.7)",
                        "rgba(0, 188, 212, 0.7)",
                        "rgba(0, 150, 136, 0.75)",
                        "rgba(76, 175, 80, 0.8)",
                        "rgba(139, 195, 74, 0.85)",
                        "rgba(205, 220, 57, 0.9)",
                        "rgba(255, 235, 59, 0.95)",
                        "rgba(255, 193, 7, 1)",
                    ],

                    components: {
                        // Main components
                        preview: true,
                        opacity: true,
                        hue: true,

                        // Input / output Options
                        interaction: {
                            hex: true,
                            rgba: true,
                            hsva: false,
                            input: true,
                            clear: false,
                            save: false,
                        },
                    },
                    default:current_colors[index] ? current_colors[index] : "#000000"

                });



                pickr[index] = pickr;
                input_el.on('change',function(){
                    pickr.setColor(input_el.val())
                })

                pickr.on('change', function(color, source, instance){
                    // console.log($(pickr.getRoot().root).find('input[type="text"]'));
                    // console.log(color.toHEXA().toString());
                    // console.log($(pickr).parent().find('input[type="text"]').length)
                    var hex = color.toHEXA().toString();
                    // console.log('input[name="'+ input_names[index] +'"]');
                    // console.log($('input[name="'+ input_names[index] +'"]'));
                    $('input[name="'+ input_names[index] +'"]').val(hex)
                }).on('changestop',function(){
                    pickr.applyColor();
                    pickr.hide();
                });


            });
        });
    </script>

    <script>
        $(document).ready(function () {
            // Expand all accordions
            $('.expand-all-accordion-items').click(function () {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse:not(.show)').addClass('show');
            });

            // Collapse all accordions
            $('.collapse-all-accordion-items').click(function () {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse.show').removeClass('show');
            });
        });
    </script>
@endpush