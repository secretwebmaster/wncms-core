<h2>@lang('word.theme_options')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('word.key')</th>
            <th>@lang('word.value')</th>
        </thead>
        <tbody>
            @foreach($website->get_options() as $key => $value)
            <tr>
                <td>{{ $key }}</td>
                <td>{{ $value }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>