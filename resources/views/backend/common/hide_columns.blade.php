{{-- Toggle Columns Button --}}
<button class="btn btn-sm btn-secondary fw-bold mb-1" id="btn_toggle_columns" data-bs-toggle="modal" data-bs-target="#modal_toggle_columns">
    @lang('wncms::word.toggle_columns')
</button>

<div class="modal fade" tabindex="-1" id="modal_toggle_columns">
    <div class="modal-dialog modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h3 class="modal-title">@lang('wncms::word.toggle_columns')</h3>
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close"></div>
            </div>

            <div class="modal-body">
                <p class="mb-3 fw-bold">@lang('wncms::word.select_columns_to_display')</p>

                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-light fw-bold" id="btn_toggle_all_on">@lang('wncms::word.show_all')</button>
                    <button type="button" class="btn btn-sm btn-light fw-bold" id="btn_toggle_all_off">@lang('wncms::word.hide_all')</button>
                </div>

                <div id="column-toggle-list"></div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">@lang('wncms::word.close')</button>
            </div>

        </div>
    </div>
</div>

@push('foot_js')
    {{-- toggle fields display --}}
    <script>
        const SERVERS_COLUMNS_STORAGE_KEY = "{{ $key ?? 'global' }}" + "_servers_table_columns";

        function loadEnabledColumns() {
            let raw = localStorage.getItem(SERVERS_COLUMNS_STORAGE_KEY);
            console.log("[loadEnabledColumns] raw =", raw);

            // null = never saved before
            if (raw === null) {
                return null;
            }

            // empty string = explicitly saved "hide all"
            if (raw === "") {
                console.log("[loadEnabledColumns] return [] (hide all)");
                return [];
            }

            // comma separated list: "0,2,3"
            let parts = raw.split(",").map(v => parseInt(v)).filter(v => !isNaN(v));
            console.log("[loadEnabledColumns] parsed =", parts);

            if (!parts.length) {
                // safety: bad value, treat as "no preference"
                return null;
            }

            return parts;
        }

        function saveEnabledColumns(enabled) {
            let value = enabled.join(",");
            localStorage.setItem(SERVERS_COLUMNS_STORAGE_KEY, value);
            console.log("[saveEnabledColumns] stored =", value);
        }

        function countHiddenColumns(enabled, total) {
            if (!enabled) return 0;
            return total - enabled.length;
        }

        function updateButtonBadge() {
            let headers = document.querySelectorAll("table thead th");
            if (!headers.length) return;

            let saved = loadEnabledColumns();
            let hidden = countHiddenColumns(saved, headers.length);
            let btn = document.getElementById("btn_toggle_columns");
            if (!btn) return;

            if (hidden > 0) {
                btn.innerHTML = `@lang('wncms::word.toggle_columns') (${hidden})`;
            } else {
                btn.innerHTML = `@lang('wncms::word.toggle_columns')`;
            }

            console.log("[updateButtonBadge] hidden =", hidden);
        }

        function applyColumnVisibility(enabled) {
            console.log("[applyColumnVisibility] enabled =", enabled);
            let headers = document.querySelectorAll("table thead th");
            let rows = document.querySelectorAll("table tbody tr");

            headers.forEach((th, index) => {
                let show = !enabled || enabled.includes(index);
                console.log(" - Column", index, "=>", show ? "SHOW" : "HIDE");
                th.style.display = show ? "" : "none";
                rows.forEach(row => {
                    let td = row.children[index];
                    if (td) td.style.display = show ? "" : "none";
                });
            });

            updateButtonBadge();
        }

        function initColumnToggler() {
            let headers = document.querySelectorAll("table thead th");
            if (headers.length === 0) {
                console.log("[initColumnToggler] table not ready, retry...");
                return setTimeout(initColumnToggler, 80);
            }

            console.log("[initColumnToggler] FOUND", headers.length, "COLUMNS");

            let saved = loadEnabledColumns();
            console.log("[initColumnToggler] saved =", saved);

            let listDiv = document.getElementById("column-toggle-list");
            if (!listDiv) return;
            listDiv.innerHTML = "";

            headers.forEach((th, index) => {
                let label = th.innerText.trim() || "";
                let checked = !saved || saved.includes(index);
                console.log("[checkbox] index =", index, "label =", label, "checked =", checked);

                listDiv.innerHTML += `
                <div class="form-check mb-2">
                    <input class="form-check-input column-toggler" type="checkbox" data-index="${index}" ${checked ? "checked" : ""}>
                    <label class="form-check-label fw-bold">${label}</label>
                </div>
            `;
            });

            applyColumnVisibility(saved);

            document.querySelectorAll(".column-toggler").forEach(cb => {
                cb.addEventListener("change", () => {
                    let enabled = [];
                    document.querySelectorAll(".column-toggler").forEach(c => {
                        if (c.checked) enabled.push(parseInt(c.dataset.index));
                    });
                    console.log("[change] enabled =", enabled);
                    saveEnabledColumns(enabled);
                    applyColumnVisibility(enabled);
                });
            });

            // toggle all on
            document.getElementById("btn_toggle_all_on").addEventListener("click", () => {
                let total = headers.length;
                let enabled = Array.from({
                    length: total
                }, (_, i) => i);
                console.log("[btn_toggle_all_on] enabled =", enabled);
                saveEnabledColumns(enabled);
                applyColumnVisibility(enabled);
                document.querySelectorAll(".column-toggler").forEach(c => c.checked = true);
            });

            // toggle all off
            document.getElementById("btn_toggle_all_off").addEventListener("click", () => {
                console.log("[btn_toggle_all_off] enabled = [] (hide all)");
                let enabled = [];
                saveEnabledColumns(enabled);
                applyColumnVisibility(enabled);
                document.querySelectorAll(".column-toggler").forEach(c => c.checked = false);
            });
        }

        document.addEventListener("DOMContentLoaded", initColumnToggler);
    </script>
@endpush
