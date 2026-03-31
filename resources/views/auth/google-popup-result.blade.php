<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('wncms::word.google_login_popup_title') }}</title>
</head>
<body>
    <script>
        (function () {
            const payload = {
                source: 'wncms-google-login',
                status: @json($status),
                redirect: @json($redirectUrl),
                message: @json($message),
            };

            if (window.opener && !window.opener.closed) {
                window.opener.postMessage(payload, window.location.origin);
                window.close();
                return;
            }

            window.location.href = payload.redirect;
        })();
    </script>
</body>
</html>
