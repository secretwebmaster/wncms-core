@extends('wncms::layouts.install')

@section('title')
@lang('wncms::installer.environment.wizard.title')
@endsection


@section('container')
<div class="tabs tabs-full">

    <input id="tab1" type="radio" name="tabs" class="tab-input" checked />
    <label for="tab1" class="tab-label">
        <i class="fa-solid fa-cog fa-2x fa-fw" aria-hidden="true"></i>
        <br />
        @lang('wncms::installer.environment.wizard.tabs.environment')
    </label>

    <input id="tab2" type="radio" name="tabs" class="tab-input" />
    <label for="tab2" class="tab-label">
        <i class="fa-solid fa-rocket fa-2x fa-fw" aria-hidden="true"></i>
        <br />
        @lang('wncms::installer.environment.wizard.tabs.application')
    </label>

    <input id="tab3" type="radio" name="tabs" class="tab-input" />
    <label for="tab3" class="tab-label">
        <i class="fa-solid fa-database fa-2x fa-fw" aria-hidden="true"></i>
        <br />
        @lang('wncms::installer.environment.wizard.tabs.database')
    </label>

    <form id="form-install-wncms" class="tabs-wrap">
        @csrf

        {{-- Basics --}}
        <div class="tab" id="tab1content">
            {{-- App Name --}}
            <div class="form-group {{ $errors->has('app_name') ? ' has-error ' : '' }}">
                <label for="app_name">
                    @lang('wncms::installer.environment.wizard.form.app_name_label')
                </label>
                <input type="text" name="app_name" id="app_name" value="{{ old('app_name', 'wncms' . date('YmdHis')) }}" placeholder="@lang('wncms::installer.environment.wizard.form.app_name_placeholder')" />
                @if ($errors->has('app_name'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('app_name') }}
                </span>
                @endif
            </div>

            <input type="hidden" name="app_env" value="production">
            <input type="hidden" name="app_debug" value="false">
            <input type="hidden" name="log_level" value="{{ old('log_level','info') }}">

            {{-- App URL --}}
            <div class="form-group {{ $errors->has('app_url') ? ' has-error ' : '' }}">
                <label for="app_url">@lang('wncms::installer.environment.wizard.form.app_url_label')</label>
                <input type="url" name="app_url" id="app_url" value="{{ old('app_url', request()->root()) }}" placeholder="@lang('wncms::installer.environment.wizard.form.app_url_placeholder')" />
                @if ($errors->has('app_url'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('app_url') }}
                </span>
                @endif
            </div>

            {{-- Language --}}
            <div class="form-group">
                <label for="app_locale">@lang('wncms::word.language')</label>
                <select name="app_locale" id="app_locale" class="form-select">
                    @foreach($languages as $locale => $props)
                    <option value="{{ $locale }}"
                        {{ old('app_locale', app()->getLocale()) == $locale ? 'selected' : '' }}>
                        {{ $props['native'] }} ({{ strtoupper($locale) }})
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Multi-Website --}}
            <div class="form-group form-check mt-3">
                <label for="multi_website" class="form-check-label fw-semibold">@lang('wncms::word.multi_website')</label>
                <div class="form-check-item">
                    <input type="checkbox" name="multi_website" id="multi_website" class="form-check-input" value="1" {{ old('multi_website') ? 'checked' : '' }}>
                    <div class="form-text text-muted">@lang('wncms::word.multi_website_hint')</div>
                </div>
            </div>

            <div class="buttons">
                <button class="button" onclick="showCacheSettings();return false">
                    @lang('wncms::installer.environment.wizard.form.buttons.setup_database')
                    <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        {{-- Cache Settings --}}
        <div class="tab" id="tab2content">

            <div class="form-group {{ $errors->has('cache_store') ? ' has-error ' : '' }}">
                <label for="cache_store">@lang('wncms::installer.environment.wizard.form.app_tabs.cache_label')</label>
                <select name="cache_store" id="cache_store" class="form-select">
                    @if (class_exists(\Redis::class))
                    <option value="redis" {{ old('cache_store', 'redis' )=='redis' ? 'selected' : '' }}>Redis</option>
                    @endif

                    <option value="file" {{ old('cache_store')=='file' ? 'selected' : '' }}>File</option>

                    @if (class_exists(\Memcached::class))
                    <option value="memcached" {{ old('cache_store')=='memcached' ? 'selected' : '' }}>Memcached</option>
                    @endif
                </select>
                @if ($errors->has('cache_store'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('cache_store') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('session_driver') ? ' has-error ' : '' }}">
                <label for="session_driver">@lang('wncms::installer.environment.wizard.form.app_tabs.session_label')</label>
                <input type="text" name="session_driver" id="session_driver" value="{{ old('session_driver', 'file') }}" placeholder="@lang('wncms::installer.environment.wizard.form.app_tabs.session_placeholder')" />
                @if ($errors->has('session_driver'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('session_driver') }}
                </span>
                @endif
            </div>

            <input type="hidden" name="queue_connection" value="sync">

            <div class="form-group {{ $errors->has('redis_host') ? ' has-error ' : '' }}">
                <label for="redis_host">@lang('wncms::installer.environment.wizard.form.app_tabs.redis_host')</label>
                <input type="text" name="redis_host" id="redis_host" value="127.0.0.1" placeholder="@lang('wncms::installer.environment.wizard.form.app_tabs.redis_host')" />
                @if ($errors->has('redis_host'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('redis_host') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('redis_password') ? ' has-error ' : '' }}">
                <label for="redis_password">@lang('wncms::installer.environment.wizard.form.app_tabs.redis_password')</label>
                <input type="password" name="redis_password" id="redis_password" value="" placeholder="@lang('wncms::installer.environment.wizard.form.app_tabs.redis_password_placeholder')" />
                @if ($errors->has('redis_password'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('redis_password') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('redis_port') ? ' has-error ' : '' }}">
                <label for="redis_port">@lang('wncms::installer.environment.wizard.form.app_tabs.redis_port')</label>
                <input type="number" name="redis_port" id="redis_port" value="6379" placeholder="@lang('wncms::installer.environment.wizard.form.app_tabs.redis_port')" />
                @if ($errors->has('redis_port'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('redis_port') }}
                </span>
                @endif
            </div>

            <div class="buttons">
                <button class="button" onclick="showDatabaseSettings();return false">
                    @lang('wncms::installer.environment.wizard.form.buttons.setup_application')
                    <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                </button>
            </div>

        </div>

        {{-- Database --}}
        <div class="tab" id="tab3content">

            <input type="hidden" name="database_connection" value="mysql">

            <div class="form-group {{ $errors->has('database_hostname') ? ' has-error ' : '' }}">
                <label for="database_hostname">
                    @lang('wncms::installer.environment.wizard.form.db_host_label')
                </label>
                <input type="text" name="database_hostname" id="database_hostname" value="{{ old('database_hostname', '127.0.0.1') }}" placeholder="@lang('wncms::installer.environment.wizard.form.db_host_placeholder')" />
                @if ($errors->has('database_hostname'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('database_hostname') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('database_port') ? ' has-error ' : '' }}">
                <label for="database_port">
                    @lang('wncms::installer.environment.wizard.form.db_port_label')
                </label>
                <input type="number" name="database_port" id="database_port" value="{{ old('database_port', '3306') }}" placeholder="@lang('wncms::installer.environment.wizard.form.db_port_placeholder')" />
                @if ($errors->has('database_port'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('database_port') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('database_name') ? ' has-error ' : '' }}">
                <label for="database_name">
                    @lang('wncms::installer.environment.wizard.form.db_name_label')
                </label>
                <input type="text" name="database_name" id="database_name" value="{{ old('database_name') }}" placeholder="@lang('wncms::installer.environment.wizard.form.db_name_placeholder')" />
                @if ($errors->has('database_name'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('database_name') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('database_username') ? ' has-error ' : '' }}">
                <label for="database_username">
                    @lang('wncms::installer.environment.wizard.form.db_username_label')
                </label>
                <input type="text" name="database_username" id="database_username" value="{{ old('database_username') }}" placeholder="@lang('wncms::installer.environment.wizard.form.db_username_placeholder')" />
                @if ($errors->has('database_username'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('database_username') }}
                </span>
                @endif
            </div>

            <div class="form-group {{ $errors->has('database_password') ? ' has-error ' : '' }}">
                <label for="database_password">
                    @lang('wncms::installer.environment.wizard.form.db_password_label')
                </label>
                <input type="password" name="database_password" id="database_password" value="{{ old('database_password') }}" placeholder="@lang('wncms::installer.environment.wizard.form.db_password_placeholder')" />
                @if ($errors->has('database_password'))
                <span class="error-block">
                    <i class="fa fa-fw fa-exclamation-triangle" aria-hidden="true"></i>
                    {{ $errors->first('database_password') }}
                </span>
                @endif
            </div>

            {{-- Error message --}}
            <div class="error-message-box" style="display: none">
                <div class="alert alert-danger error-message"></div>
            </div>

            {{-- Submit --}}
            <div class="buttons">
                <button class="button btn-submit wncms-install"
                    wncms-btn-ajax
                    data-form="form-install-wncms"
                    data-original-text="@lang('wncms::word.install')"
                    data-loading-text="@lang('wncms::word.installing').."
                    data-success-text="@lang('wncms::word.successfully_installed')"
                    data-fail-text="@lang('wncms::word.install_again')"
                    data-route="{{ route('installer.wizard.install') }}"
                    data-method="POST"
                    type="button">
                    <span>@lang('wncms::word.install')</span>
                    <i class="fa fa-angle-right fa-fw" aria-hidden="true"></i>
                </button>
            </div>

            <div class="btn-force-https" title="Force HTTPS"><i class="fa-solid fa-unlock fa-2xl"></i></div>
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
        </div>
    </form>

</div>
@endsection

@section('foot_js')
<script type="text/javascript">
    function showCacheSettings() {
        document.getElementById('tab2').checked = true;
    }

    function showDatabaseSettings() {
        document.getElementById('tab3').checked = true;
    }

    function customFailAction(response){
        console.log(response);
        if(response.status == 'fail'){
            $('.error-message-box').show();
            // Check if response.message is an object
            if (typeof response.message === 'object') {
                let errorList = '<ul>';
                for (let field in response.message) {
                    // Iterate through each error message for the field (assuming they are arrays)
                    response.message[field].forEach(function(error) {
                        errorList += '<li>' + error + '</li>';
                    });
                }
                errorList += '</ul>';
                
                // Display the list of errors
                $('.error-message').html(errorList);
            } else {
                // If not an object, display the message directly
                $('.error-message').html(response.message);
            }
        }
    }

</script>
@endsection