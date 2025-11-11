<h2>@lang('wncms::word.theme_options')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('wncms::word.key')</th>
            <th>@lang('wncms::word.value')</th>
        </thead>
        <tbody>
            {{-- use gto if you want to cache --}}
            @foreach($website->get_options() as $key => $value)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>