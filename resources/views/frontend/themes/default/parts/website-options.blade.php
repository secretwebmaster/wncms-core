<h2>@lang('wncms::word.website_options')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('wncms::word.key')</th>
            <th>@lang('wncms::word.method')</th>
            <th>@lang('wncms::word.value')</th>
        </thead>
        <tbody>
            @foreach([
                'site_favicon',
                'site_logo',
                'site_name',
                'site_slogan',
                'site_seo_keywords',
                'site_seo_description',
                'theme',
                'remark',
                ] as $field)
            <tr>
                <td>{{ $field }}</td>
                <td><code>$website->{{ $field }}</code></td>
                <td>{{ $website->$field }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>