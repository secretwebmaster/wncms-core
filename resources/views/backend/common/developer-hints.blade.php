<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (!{{ gss('show_developer_hints') ? 'true' : 'false' }}) return;

        document.querySelectorAll('label:not([ignore-developer-hint])').forEach(label => {
            let name = null;

            const forId = label.getAttribute('for');
            if (forId) {
                const input = document.getElementById(forId);
                if (input && input.name && input.type !== 'hidden' && !input.hasAttribute('ignore-developer-hint')) {
                    name = input.name;
                }
            }

            if (!name) {
                const parent = label.closest('.row');
                if (parent) {
                    const input = parent.querySelector('input[name]:not([type="hidden"]):not([ignore-developer-hint]), select[name]:not([ignore-developer-hint]), textarea[name]:not([ignore-developer-hint])');
                    if (input) name = input.name;
                }
            }

            if (name) {
                const hint = document.createElement('span');
                hint.className = 'fs-xs text-gray-300 ms-2';
                hint.textContent = name;
                label.appendChild(hint);
            }
        });
    });
</script>
