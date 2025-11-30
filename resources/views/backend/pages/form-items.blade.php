@push('head_css')
    <link rel="stylesheet" href="{{ asset('wncms/css/pickr.min.css') }}" />
    <style>
        .wncms-gallery-droparea {
            display: block;
            background: #f8f9fa;
            border: 2px dashed #ced4da;
            border-radius: 8px;
            cursor: pointer;
            transition: all .2s ease;
        }

        .wncms-gallery-droparea:hover {
            background: #eef1f4;
            border-color: #999;
        }

        .gallery-action-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 4px;
            margin-right: 4px;
            border-radius: 6px;
        }

        .gallery-action-btn i {
            font-size: 14px;
        }
    </style>
@endpush

@push('head_js')
    <script src="{{ asset('wncms/js/sortable.min.js') }}"></script>
    <script src="{{ asset('wncms/js/jquery.dragsort.min.js?v=' . wncms_get_version('js')) }}"></script>
@endpush

<div class="row">

    <div class="col-12 col-md-9" id="page-main">
        @php $activeTab = request()->tab; @endphp
        @include('wncms::backend.pages.form-nav')
        @include('wncms::backend.pages.form-main')
    </div>

    <div class="col-12 col-md-3">
        @include('wncms::backend.pages.form-sidebar')
    </div>

</div>

@push('foot_js')
    @include('wncms::common.js.tinymce')

    {{-- color picker --}}
    <script>
        window.addEventListener('DOMContentLoaded', (event) => {
            var input_names = [];
            var current_colors = [];
            var pickrs = [];
            $(".colorpicker-input").each(function(index, item) {

                input_names[index] = $(this).attr('data-input');
                current_colors[index] = $(this).attr('data-current');
                var input_el = $(this).parent().find('input[name="' + input_names[index] + '"]');


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
                    default: current_colors[index] ? current_colors[index] : "#000000"

                });

                pickr[index] = pickr;
                input_el.on('change', function() {
                    pickr.setColor(input_el.val())
                })

                pickr.on('change', function(color, source, instance) {
                    // console.log($(pickr.getRoot().root).find('input[type="text"]'));
                    // console.log(color.toHEXA().toString());
                    // console.log($(pickr).parent().find('input[type="text"]').length)
                    var hex = color.toHEXA().toString();
                    // console.log('input[name="'+ input_names[index] +'"]');
                    // console.log($('input[name="'+ input_names[index] +'"]'));
                    $('input[name="' + input_names[index] + '"]').val(hex)
                }).on('changestop', function() {
                    pickr.applyColor();
                    pickr.hide();
                });


            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Expand all accordions
            $('.expand-all-accordion-items').click(function() {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse:not(.show)').addClass('show');
            });

            // Collapse all accordions
            $('.collapse-all-accordion-items').click(function() {
                const target = $(this).data('target');
                $(target).find('.accordion-collapse.show').removeClass('show');
            });
        });
    </script>

    <script>
        window.addEventListener('DOMContentLoaded', (event) => {

            const hash = window.location.hash;
            if (hash && document.querySelector('[data-bs-target="' + hash + '"]')) {
                const triggerEl = document.querySelector('[data-bs-target="' + hash + '"]');
                const tab = new bootstrap.Tab(triggerEl);
                tab.show();
            }

            $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const id = $(e.target).attr('data-bs-target');
                history.replaceState(null, null, id);
            });

        });
    </script>

    {{-- tab persistence --}}
    <script>
        window.addEventListener('DOMContentLoaded', function() {

            const url = new URL(window.location);
            const paramTab = url.searchParams.get('tab');

            if (paramTab) {
                const selector = '[data-bs-target="#' + paramTab + '"]';
                const el = document.querySelector(selector);
                if (el) {
                    new bootstrap.Tab(el).show();
                    console.log('Activated tab via param:', paramTab);
                }
            }

            $('[data-bs-toggle="pill"], [data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const id = $(e.target).attr('data-bs-target').replace('#', '');
                console.log('Switched to tab:', id);

                const newUrl = new URL(window.location);
                newUrl.searchParams.set('tab', id);
                history.replaceState(null, '', newUrl.toString());
            });

        });
    </script>
@endpush
