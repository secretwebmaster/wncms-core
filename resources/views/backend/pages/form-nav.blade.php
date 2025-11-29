{{-- Navigation Tabs --}}
<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">

    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold {{ $activeTab == 'pills-basic' || !$activeTab ? 'active' : '' }}"
                id="pills-basic-tab"
                data-bs-toggle="pill"
                data-bs-target="#pills-basic"
                type="button"
                role="tab">
            @lang('wncms::word.basic')
        </button>
    </li>

    @if($page->type == 'template')
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $activeTab == 'pills-template-options' ? 'active' : '' }}"
                    id="pills-template-options-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#pills-template-options"
                    type="button"
                    role="tab">
                @lang('wncms::word.theme_template_options')
            </button>
        </li>
    @endif

    @if($page->type == 'builder')
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $activeTab == 'pills-builder' ? 'active' : '' }}"
                    id="pills-builder-tab"
                    data-bs-toggle="pill"
                    data-bs-target="#pills-builder"
                    type="button"
                    role="tab">
                @lang('wncms::word.builder')
            </button>
        </li>
    @endif

</ul>
