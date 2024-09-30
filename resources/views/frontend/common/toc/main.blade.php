@push('head_js')
    <script src="https://cdn.jsdelivr.net/npm/tocbot@4/dist/tocbot.min.js"></script>
@endpush

{{-- Toc --}}
<div id="wn-toc" class="wn-toc-v2_0_55 counter-hierarchy wn-toc-counter wn-toc-grey wn-toc-direction">
    <div class="wn-toc-header-wrapper">
        <p class="wn-toc-title">@lang('word.table_of_content')</p>
        {{-- Toggle --}}
        <label for="wn-toc-cssicon-toggle-item-650ad5951ac0b">
            <span class="">
                <span style="display:none;">Toggle</span>
                <span class="wn-toc-icon-toggle-span">
                    <svg style="fill: #999;color:#999" xmlns="http://www.w3.org/2000/svg" class="list-377408" width="20px" height="20px" viewBox="0 0 24 24" fill="none">
                        <path d="M6 6H4v2h2V6zm14 0H8v2h12V6zM4 11h2v2H4v-2zm16 0H8v2h12v-2zM4 16h2v2H4v-2zm16 0H8v2h12v-2z" fill="currentColor">
                        </path>
                    </svg>
                </span>
            </span>
        </label>
    </div>

    <input type="checkbox" id="wn-toc-cssicon-toggle-item-650ad5951ac0b" aria-label="Toggle">
    {{-- List --}}
    <nav id="toc"></nav>
</div>

@push('foot_css')
    {{-- Style1 --}}
    <style>
        #wn-toc {
            background: #f9f9f9;
            border: 1px solid #aaa;
            border-radius: 4px;
            -webkit-box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            display: table;
            margin-bottom: 1em;
            padding: 10px;
            position: relative;
            width: auto
        }

        .wn-toc-header-wrapper{
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        #wn-toc ul ul {
            margin-left: 1.5em
        }

        #wn-toc li,
        #wn-toc ul {
            margin: 0;
            padding: 0
        }

        #wn-toc li,
        #wn-toc ul,
        #wn-toc ul li {
            background: 0 0;
            list-style: none;
            line-height: 1.6;
            margin: 0;
            overflow: hidden;
            z-index: 1
        }

        #wn-toc .wn-toc-title {
            text-align: left;
            line-height: 1.45;
            margin: 0;
            padding: 0;
            color:rgb(89, 89, 89);
        }

        .wn-toc-title {
            display: inline;
            text-align: left;
            vertical-align: middle
        }

        #wn-toc a {
            color: #444;
            box-shadow: none;
            text-decoration: none;
            text-shadow: none;
            display: inline-flex;
            align-items: stretch;
            flex-wrap: nowrap;
            font-size: 16px;
        }

        #wn-toc a:visited {
            color: #9f9f9f
        }

        #wn-toc a:hover {
            text-decoration: underline
        }

        .wn-toc-widget-container ul.wn-toc-list li.active {
            background-color: #ededed
        }

        .wn-toc-widget-container li.active>a {
            font-weight: 900
        }

        .wn-toc-btn.active {
            background-image: none;
            outline: 0;
            -webkit-box-shadow: inset 0 3px 5px rgba(0, 0, 0, .125);
            box-shadow: inset 0 3px 5px rgba(0, 0, 0, .125)
        }

        .wn-toc-btn-default.active {
            color: #333;
            background-color: #ebebeb;
            border-color: #adadad
        }

        .wn-toc-btn-default.active {
            background-image: none
        }

        .btn.active {
            background-image: none
        }

        .wn-toc-btn-default.active {
            background-color: #e0e0e0;
            border-color: #dbdbdb
        }

        #wn-toc input {
            position: absolute;
            left: -999em
        }

        #wn-toc input[type=checkbox]:checked+nav {
            opacity: 0;
            max-height: 0;
            border: none;
            display: none
        }

        #wn-toc label {
            position: relative;
            cursor: pointer;
            display: initial
        }

        #wn-toc .wn-toc-title {
            display: initial
        }

        #wn-toc {
            padding-right: 20px
        }

        .wn-toc-icon-toggle-span {
            display: flex;
            align-items: center;
            width: 20px;
            height: 30px;
            justify-content: center;
            direction: ltr
        }

        #wn-toc .wn-toc-title {
            /* font-size: 150% */
        }

        #wn-toc .wn-toc-title {
            font-weight: 500
        }

        #wn-toc ul li {
            font-size: 110%
        }

        #wn-toc nav ul ul li ul li {
            font-size: 100% !important
        }

        #wn-toc {
            width: 66%
        }

        .wn-toc-direction {
            direction: ltr
        }

        .wn-toc-counter ul {
            counter-reset: item
        }

        .wn-toc-counter nav ul li a::before {
            content: counters(item, ".", decimal) ". ";
            display: inline-block;
            counter-increment: item;
            flex-grow: 0;
            flex-shrink: 0;
            margin-right: .2em;
            float: left
        }

        @media screen and (max-width:767px) {
            #wn-toc {
                width: 100%;
            }
        }
    </style>
@endpush

@push('foot_js')
    <script>
        // Get all the headings (h1 to h6) in the content
        const headings = document.querySelectorAll('{{ $contentSelector ?? "" }} h1, {{ $contentSelector ?? "" }} h2, {{ $contentSelector ?? "" }} h3, {{ $contentSelector ?? "" }} h4, {{ $contentSelector ?? "" }} h5, {{ $contentSelector ?? "" }} h6');

        let idCounter = 1;

        headings.forEach(function(heading) {
            // Generate a random ID using a combination of letters and numbers
            const randomId = 'heading-' + idCounter
            
            // Set the generated ID as the heading's id attribute
            heading.setAttribute('id', randomId);
            idCounter++;
        });

        tocbot.init({
            tocSelector: '{{ $tocSelector ?? "#toc" }}',
            contentSelector: '{{ $contentSelector ?? "" }}',
            headingSelector: '{{ $headingSelector ?? "h2, h3, h4, h5, h6" }}',
            isCollapsedClass: 'is-collapsed',
            collapsibleClass: 'is-collapsible',
            listItemClass: 'toc-list-item',
            activeListItemClass: 'is-active-li',
            linkClass: 'wn-toc-link',
            listClass: 'wn-toc-list',
            orderedList: false,

            scrollSmooth: true,
            headingsOffset: 90,
            scrollSmoothOffset: -90,
            includeHtml: true,
            includeTitleTags: true,
        });

    </script>
@endpush