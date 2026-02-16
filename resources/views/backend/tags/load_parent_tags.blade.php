<script>
    $(document).ready(function () {
        var typeInput = document.querySelector('[name="type"]');
        var parentInput = document.querySelector('#tag_parent_id');
        var currentTag = @json($tag ?? null);
        var allParentTags = @json($tagifyParents ?? []);
        var preselectedParentId = @json(old('parent_id', request()->parent_id ?? ''));
        var parentTagify = null;

        function initParentTagify() {
            if (parentTagify) {
                return parentTagify;
            }

            parentTagify = new Tagify(parentInput, {
                whitelist: [],
                className: 'form-control form-control-sm',
                mode: 'select',
                enforceWhitelist: true,
                skipInvalid: true,
                duplicates: false,
                tagTextProp: 'name',
                maxTags: 1,
                dropdown: {
                    maxItems: 100,
                    mapValueTo: 'name',
                    classname: "tagify__inline__suggestions",
                    enabled: 0,
                    closeOnSelect: true,
                    searchKeys: ['name', 'value'],
                },
            });

            return parentTagify;
        }

        function getSelectedParentId() {
            var normalizedFromOld = normalizeParentId(preselectedParentId);
            if (normalizedFromOld) {
                return normalizedFromOld;
            }

            if (currentTag && currentTag.parent_id) {
                return String(currentTag.parent_id);
            }

            return '';
        }

        function normalizeParentId(input) {
            if (!input) {
                return '';
            }

            if (typeof input === 'number') {
                return String(input);
            }

            if (typeof input === 'string') {
                if (/^\d+$/.test(input)) {
                    return input;
                }

                try {
                    return normalizeParentId(JSON.parse(input));
                } catch (e) {
                    return '';
                }
            }

            if (Array.isArray(input)) {
                if (!input.length) {
                    return '';
                }

                return normalizeParentId(input[0]);
            }

            if (typeof input === 'object') {
                if (input.value) {
                    return String(input.value);
                }

                if (input.id) {
                    return String(input.id);
                }
            }

            return '';
        }

        function normalizeTagList(payload) {
            var rows = Array.isArray(payload) ? payload : [];
            var selectedId = getSelectedParentId();
            var currentTagId = currentTag && currentTag.id ? String(currentTag.id) : '';
            var selectedType = typeInput && typeInput.value ? String(typeInput.value) : '';
            var items = [];

            rows.forEach(function (row) {
                var id = row && row.value ? String(row.value) : '';
                var name = row && row.name ? String(row.name) : '';
                var type = row && row.type ? String(row.type) : '';

                if (!id || !name) {
                    return;
                }

                if (selectedType && type && type !== selectedType) {
                    return;
                }

                if (currentTagId && id === currentTagId) {
                    return;
                }

                items.push({
                    value: id,
                    name: name,
                    selected: selectedId && id === selectedId,
                });
            });

            return items;
        }

        function refreshParentTags(tagType) {
            var tagify = initParentTagify();
            var items = normalizeTagList(allParentTags);
            var selected = items.filter(function (item) {
                return item.selected;
            });

            tagify.removeAllTags();
            tagify.settings.whitelist = items;
            tagify.dropdown.hide();

            if (selected.length > 0) {
                tagify.addTags(selected.slice(0, 1), true, true);
            }
        }

        if (typeInput && parentInput) {
            refreshParentTags(typeInput.value);

            $(typeInput).on('change', function () {
                preselectedParentId = '';
                refreshParentTags($(this).val());
            });
        }
    });
</script>
