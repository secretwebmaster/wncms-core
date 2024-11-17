<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ __('wncms::word.email_title') }}</title>
        <style>
            /* Base Styles */
            body,
            body *:not(html):not(style):not(br):not(tr):not(code) {
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif,
                    'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol';
                position: relative;
            }

            body {
                -webkit-text-size-adjust: none;
                background-color: #ffffff;
                color: #718096;
                height: 100%;
                line-height: 1.4;
                margin: 0;
                padding: 0;
                width: 100% !important;
            }

            h1 {
                color: #3d4852;
                font-size: 18px;
                font-weight: bold;
                margin-top: 0;
                text-align: left;
            }

            p {
                font-size: 16px;
                line-height: 1.5em;
                margin-top: 0;
                text-align: left;
            }

            p.subcopy {
                font-size: 14px;
                border-top: 1px solid #e8e5ef;
                margin-top: 25px;
                padding-top: 25px;
            }

            a {
                color: #3869d4;
                text-decoration: none;
            }

            .email-container {
                background-color: #ffffff;
                border-color: #e8e5ef;
                border-radius: 2px;
                border-width: 1px;
                margin: 0 auto;
                padding: 32px;
                width: 570px;
                max-width: 100vw;
            }

            .action-button {
                background-color: #2d3748;
                border-radius: 4px;
                border: 1px solid #2d3748;
                color: #ffffff;
                display: inline-block;
                font-size: 14px;
                padding: 10px 18px;
                text-decoration: none;
                text-align: center;
            }

            .action-button:hover {
                background-color: #1a202c;
                border-color: #1a202c;
            }

            .footer {
                margin-top: 20px;
                font-size: 12px;
                color: #888888;
                text-align: center;
            }
        </style>
    </head>

    <body>
        <div class="email-container">
            @yield('content')

            {{-- Salutation --}}
            <p>{{ $salutation ?? __('wncms::word.salutation', ['appName' => $appName ?? '']) }}</p>

            {{-- Subcopy --}}
            @isset($actionUrl)
                <p class="subcopy">
                    {{ __('wncms::word.action_text_trouble', ['action_text' => $actionText ?? __('wncms::word.action_button')]) }}
                    <br>
                    <a href="{{ $actionUrl }}">{{ $actionUrl }}</a>
                </p>
            @endisset

            {{-- Footer --}}
            @isset($footerText)
                <div class="footer">
                    {!! $footerText !!}
                </div>
            @endisset
        </div>
    </body>
</html>
