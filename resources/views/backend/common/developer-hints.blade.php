<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (!{{ gss('show_developer_hints') ? 'true' : 'false' }}) return;

        document.querySelectorAll('label').forEach(label => {
            let name = null;

            // 1. Try to get from "for" attribute
            const forId = label.getAttribute('for');
            if (forId) {
                const input = document.getElementById(forId);
                if (input && input.name) name = input.name;
            }

            // 2. Fallback: look for sibling/nearby input/select/textarea
            if (!name) {
                const parent = label.closest('.row');
                if (parent) {
                    const input = parent.querySelector('input[name], select[name], textarea[name]');
                    if (input) name = input.name;
                }
            }

            // 3. Inject developer hint
            if (name) {
                const hint = document.createElement('span');
                hint.className = 'fs-xs text-gray-300 ms-2';
                hint.textContent = name;
                label.appendChild(hint);
            }
        });
    });
</script>