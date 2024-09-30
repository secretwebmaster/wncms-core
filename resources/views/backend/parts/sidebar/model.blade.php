@if(!empty(gss('active_models')))

{{-- Models CRUD --}}
<div class="menu-item">
    <div class="menu-content pt-5 pb-2">
        <span class="menu-section text-white fw-bold text-uppercase fs-8 ls-1">@lang('word.models')</span>
    </div>
</div>

@php
    $models = wncms_get_model_names()->sortByDesc('priority');
    $modelMenuItems = [];

    foreach ($models as $modelData) {
        $model_class_name = $modelData['model_name_with_namespace'];
        $model = (new $model_class_name)->newModelInstance();
        $snake_name = str()->snake($modelData['model_name'], '_');
        $table_name = $model->getTable();

        if (defined(get_class($model) . "::ROUTES") && in_array($modelData['model_name'], json_decode(gss('active_models'), true))) {
            $menuItem = [
                'model' => $model,
                'routes' => array_map(fn($route) => $snake_name . '_' . $route, $model::ROUTES),
                'table_name' => $table_name,
                'snake_name' => $snake_name,
                'icon' => defined(get_class($model) . "::ICONS") && !empty($model::ICONS['fontaweseom']) ? $model::ICONS['fontaweseom'] : 'fa-solid fa-cube',
                'sub_routes' => [],
            ];

            if (defined(get_class($model) . "::SUB_ROUTES") && in_array($modelData['model_name'], json_decode(gss('active_models'), true))) {
                foreach ($model::SUB_ROUTES as $route_name) {
                    $sub_model_class_name = explode(".", $route_name)[0] ?? '';
                    $route_suffix = explode(".", $route_name)[1] ?? '';
                    if (empty($sub_model_class_name) || empty($route_suffix)) {
                        continue;
                    }
                    $sub_snake_name = str($sub_model_class_name)->singular();
                    $permission_name = $sub_snake_name . "_" . $route_suffix;

                    $menuItem['sub_routes'][] = [
                        'route_name' => $route_name,
                        'permission_name' => $permission_name,
                        'sub_snake_name' => $sub_snake_name,
                        'sub_model_class_name' => $sub_model_class_name,
                        'route_suffix' => $route_suffix,
                    ];
                }
            }

            $modelMenuItems[] = $menuItem;
        }
    }
@endphp

{{-- New --}}
@foreach($modelMenuItems as $menuItem)
    @canany($menuItem['routes'])
        <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if(request()->routeIs(array_map(fn($route) => $menuItem['table_name'] . '.' . $route, array_merge($menuItem['model']::ROUTES, ['edit'])))) show @endif">
            <span class="menu-link py-2">
                <span class="menu-icon">
                    <i class="fa-lg {{ $menuItem['icon'] }} @if(request()->routeIs(array_map(fn($route) => $menuItem['table_name'] . '.' . $route, array_merge($menuItem['model']::ROUTES, ['edit'])))) fa-beat @endif"></i>
                </span>
                <span class="menu-title fw-bold">@lang('word.model_management', ['model_name' => __('word.' . $menuItem['snake_name'])])</span>
                <span class="menu-arrow"></span>
            </span>

            <div class="menu-sub menu-sub-accordion">
                @foreach($menuItem['model']::ROUTES as $route_name)
                    @if(wncms_route_exists($menuItem['table_name'] . '.' . $route_name))
                        @can($menuItem['snake_name'] . "_" . $route_name)
                            <div class="menu-item">
                                <a class="menu-link @if(request()->routeIs($menuItem['table_name'] . '.'. $route_name .'*')) active @endif" href="{{ route($menuItem['table_name'] . '.' . $route_name) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title fw-bold">{{ wncms_model_word($menuItem['snake_name'] . '', $route_name) }}</span>
                                </a>
                            </div>
                        @endcan
                    @endif
                @endforeach

                @foreach($menuItem['sub_routes'] as $subRoute)
                    @if(wncms_route_exists($subRoute['route_name']))
                        @can($subRoute['permission_name'])
                            <div class="menu-item">
                                <a class="menu-link @if(request()->routeIs($subRoute['sub_model_class_name'] . '.' . $subRoute['route_suffix'])) active @endif" href="{{ route($subRoute['route_name']) }}">
                                    <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                    <span class="menu-title fw-bold">{{ wncms_model_word($subRoute['sub_snake_name'] . '', $subRoute['route_suffix']) }}</span>
                                </a>
                            </div>
                        @endcan
                    @endif
                @endforeach
            </div>
        </div>
    @endcanany
@endforeach

{{-- Old --}}
@foreach([] as $modelData)

    @php
        $model_class_name = $modelData['model_name_with_namespace'];
        $model = (new $model_class_name)->newModelInstance();
        $snake_name = str()->snake($modelData['model_name'], '_');
        $table_name = $model->getTable();
    @endphp

    @if(defined(get_class($model) . "::ROUTES") && in_array($modelData['model_name'], json_decode(gss('active_models'), true)))
        @canany(array_map(fn($route) => $snake_name . '_' . $route, $model::ROUTES))
            <div data-kt-menu-trigger="click" class="menu-item menu-accordion @if(request()->routeIs(array_map(fn($route) => $table_name . '.' . $route, array_merge($model::ROUTES, ['edit']))))) show @endif">
                <span class="menu-link py-2">
                    <span class="menu-icon">
                        <i class="fa-lg 
                            {{ defined(get_class($model) . "::ICONS") && !empty($model::ICONS['fontaweseom']) ? $model::ICONS['fontaweseom'] : 'fa-solid fa-cube' }} 
                            @if(request()->routeIs(array_map(fn($route) => $table_name . '.' . $route, array_merge($model::ROUTES, ['edit']))))) fa-beat @endif"></i>
                    </span>
                    <span class="menu-title fw-bold">@lang('word.model_management', ['model_name' => __('word.' . $snake_name)])</span>
                    <span class="menu-arrow"></span>
                </span>

                <div class="menu-sub menu-sub-accordion">
                    @foreach($model::ROUTES as $route_name)
                        @if(wncms_route_exists($table_name . '.' . $route_name))
                            @can($snake_name . "_" . $route_name)
                                <div class="menu-item">
                                    <a class="menu-link @if(request()->routeIs($table_name . '.'. $route_name .'*')) active @endif" href="{{ route($table_name . '.' . $route_name) }}">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        {{-- <span class="menu-title fw-bold">@lang('word.' . $table_name . "_" . $route_name)</span> --}}
                                        <span class="menu-title fw-bold">{{ wncms_model_word($snake_name . '', $route_name) }}</span>
                                    </a>
                                </div>
                            @endcan
                        @endif
                    @endforeach

                    @if(defined(get_class($model) . "::SUB_ROUTES") && in_array($modelData['model_name'], json_decode(gss('active_models'), true)))
                        @foreach($model::SUB_ROUTES as $route_name)

                            @php
                                $sub_model_class_name = explode(".", $route_name)[0] ?? '';
                                $route_suffix = explode(".", $route_name)[1] ?? '';
                                if(empty($sub_model_class_name) || empty($route_suffix)){
                                    continue;
                                }
                                $sub_snake_name = str($sub_model_class_name)->singular();
                                $permission_name = $sub_snake_name . "_" . $route_suffix;
                            @endphp

                            @if(wncms_route_exists($route_name))
                                @can($permission_name)
                                    <div class="menu-item">
                                        <a class="menu-link @if(request()->routeIs($sub_model_class_name . '.' . $route_suffix)) active @endif" href="{{ route($route_name) }}">
                                            <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                            <span class="menu-title fw-bold">{{ wncms_model_word($sub_snake_name . '', $route_suffix) }}</span>
                                        </a>
                                    </div>
                                @endcan
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        @endrole
    @endif

@endforeach

@endif