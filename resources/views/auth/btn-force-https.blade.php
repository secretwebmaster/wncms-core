<div class="btn-force-https cursor-pointer" title="Force HTTPS">
    <i class="fa-solid fa-lock fa-2xl"></i>
    <span class="text-gray-100">@lang('wncms::word.force_https')</span>
</div>

@push('foot_js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const forceHttpsButton = document.querySelector('.btn-force-https');
        const installButton = document.querySelector('.btn-submit.wncms-install');
        const form = document.querySelector('#form-install-wncms');
        const currentUrl = new URL(window.location.href);
        const hasForceHttps = currentUrl.searchParams.get('force_https') === '1';

        // Initialize icon, data-route, and form input based on current URL
        if (hasForceHttps) {
            updateDataRoute('https');
            changeIcon('fa-lock', 'fa-unlock');
            addForceHttpsInput();
        } else {
            updateDataRoute('http');
            changeIcon('fa-unlock', 'fa-lock');
            removeForceHttpsInput();
        }

        forceHttpsButton.addEventListener('click', function() {
            const currentUrl = new URL(window.location.href);
            let icon = this.querySelector('i');

            if (currentUrl.searchParams.has('force_https')) {
                currentUrl.searchParams.delete('force_https');
                updateDataRoute('http');
                changeIcon('fa-unlock', 'fa-lock');
                removeForceHttpsInput();
            } else {
                currentUrl.searchParams.set('force_https', '1');
                updateDataRoute('https');
                changeIcon('fa-lock', 'fa-unlock');
                addForceHttpsInput();
            }

            window.history.pushState({}, '', currentUrl);
        });

        function updateDataRoute(protocol) {
            const baseRoute = '{{ route('installer.wizard.install') }}';
            const newRoute = baseRoute.replace(/^http:\/\//i, `${protocol}://`);
            if (installButton) {
                installButton.setAttribute('data-route', newRoute);
            }
        }

        function changeIcon(addClass, removeClass) {
            const icon = forceHttpsButton.querySelector('i');
            icon.classList.remove(removeClass);
            icon.classList.add(addClass);
        }

        function addForceHttpsInput() {
            if (form && !form.querySelector('input[name="force_https"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'force_https';
                input.value = '1';
                form.appendChild(input);
            }
        }

        function removeForceHttpsInput() {
            const input = form ? form.querySelector('input[name="force_https"]') : null;
            if (input) {
                form.removeChild(input);
            }
        }
    });
</script>
@endpush