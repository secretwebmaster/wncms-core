<div class="container mb-3 mx-0 p-0 shadow-sm rounded">

    {{-- Empty placeholder --}}
    @for($i=0;$i<5;$i++)
    <div class="update-group mb-3 border border-dark border-2 rounded update-group-placeholder">
        <div class="d-flex align-items-center justify-content-between bg-dark p-3">
            <h2 class="mb-0 text-gray-100 fs-5">@lang('wncms::word.loading')<span class="loading-dots">...</span></h2>
        </div>
        <div class="update-details bg-light border-top p-3 rounded-bottom position-relative">
            <!-- Changes List -->
            <ul class="changes-list list-unstyled mb-0">
                <li class="change-item mb-2">
                    <span class="loading-bar bg-secondary w-100 d-inline-block rounded" style="height: 1rem;"></span>
                </li>
                <li class="change-item mb-2">
                    <span class="loading-bar bg-secondary w-75 d-inline-block rounded" style="height: 1rem;"></span>
                </li>
                <li class="change-item mb-2">
                    <span class="loading-bar bg-secondary w-75 d-inline-block rounded" style="height: 1rem;"></span>
                </li>
                <li class="change-item mb-2">
                    <span class="loading-bar bg-secondary w-50 d-inline-block rounded" style="height: 1rem;"></span>
                </li>
                <li class="change-item mb-2">
                    <span class="loading-bar bg-secondary w-25 d-inline-block rounded" style="height: 1rem;"></span>
                </li>
            </ul>
            <div class="shiny-bar position-absolute top-50 start-0 w-100 h-2"></div>
        </div>
    </div>
    @endfor
    
    <style>
        .loading-dots::after {
            content: '';
            animation: dots 1.5s steps(3, end) infinite;
        }
        @keyframes dots {
            0% { content: ''; }
            33% { content: '.'; }
            66% { content: '..'; }
            100% { content: '...'; }
        }
        .loading-bar {
            position: relative;
            overflow: hidden;
        }
        .loading-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
            animation: shiny 1.5s infinite;
        }
        .shiny-bar {
            height: 4px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%);
            animation: shiny 2s infinite;
        }
        @keyframes shiny {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
    </style>

    {{-- Template --}}
    <div class="update-group mb-3 border border-dark border-2 rounded d-none" id="update_group_placeholder">
        <div class="d-flex align-items-center justify-content-between bg-dark p-3">
            <h2 class="mb-0 text-gray-100 fs-5">@lang('wncms::word.version') <span class="version"></span></h2>
            <span class="release-date text-gray-300 fs-6"></span>
        </div>
        <div class="update-details bg-light border-top p-3 rounded-bottom">
            {{-- list item --}}
            <ul class="changes-list list-unstyled mb-0"></ul>
        </div>
    </div>

    <li class="change-item d-none mb-1 text-nowrap text-truncate" id="change_item_placeholder">
        <span class="change-type fw-bold me-2"></span>
        <span class="change-content text-dark"></span>
    </li>
</div>

@php
    $updateTypes = [
        'add' => __('wncms::word.add'),
        'fix' => __('wncms::word.fix'),
        'improve' => __('wncms::word.improve'),
        'remove' => __('wncms::word.remove'),
        'test' => __('wncms::word.test'),
        'developer' => __('wncms::word.developer'),
    ];
@endphp

@push('foot_js')
<script>
    $(document).ready(function () {
        const currentDomain = window.location.hostname;
        const url = `https://api.wncms.cc/api/v1/update/logs?product=core&domain=${currentDomain}`;
        const translations = @json($updateTypes);

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (response.status === "success" && Array.isArray(response.data)) {
                    const updatesContainer = $(".container");
                    const groupPlaceholder = $("#update_group_placeholder");
                    const itemPlaceholder = $("#change_item_placeholder");

                    $('.update-group-placeholder').hide();

                    response.data.forEach((update) => {
                        const newGroup = groupPlaceholder.clone().removeClass("d-none").attr("id", `update_group_${update.id}`);
                        newGroup.find(".version").text(`${update.version}`);
                        newGroup.find(".release-date").text(new Date(update.released_at).toLocaleDateString());

                        const changesList = newGroup.find(".changes-list");

                        // Sort changes by type
                        const sortedChanges = update.changes.sort((a, b) => a.type.localeCompare(b.type));

                        sortedChanges.forEach((change) => {
                            const newItem = itemPlaceholder.clone().removeClass("d-none");
                            newItem.find(".change-type").text(translations[change.type] || change.type).addClass(`text-${getBadgeColor(change.type)}`);
                            newItem.find(".change-content").text(change.content);

                            changesList.append(newItem);
                        });

                        updatesContainer.append(newGroup);
                    });
                }
            },
            error: function (xhr, status, error) {
                console.error("Failed to fetch update logs:", error);
            }
        });

        // Helper to map change types to badge colors
        function getBadgeColor(type) {
            switch (type) {
                case "fix":
                    return "primary";
                case "add":
                    return "success";
                case "improve":
                    return "info";
                case "remove":
                    return "danger";
                case "test":
                    return "warning";
                default:
                    return "gray-600";
            }
        }
    });
</script>
@endpush