<h2>@lang('wncms::word.page_list')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('wncms::word.name')</th>
            <th>@lang('wncms::word.slug')</th>
            <th>@lang('wncms::word.url')</th>
        </thead>
        <tbody>
            <tr>
                <td>@lang('wncms::word.home')</td>
                <td>home</td>
                <td><a href="{{ route('frontend.pages.home') }}">{{ route('frontend.pages.home') }}</a></td>
            </tr>
            <tr>
                <td>@lang('wncms::word.blog')</td>
                <td>blog</td>
                <td><a href="{{ route('frontend.pages.blog') }}">{{ route('frontend.pages.blog') }}</a></td>
            </tr>
            <tr>
                <td>@lang('wncms::word.maintenance')</td>
                <td>maintenance</td>
                <td><a href="{{ route('frontend.pages.show', ['slug' => 'maintenance']) }}">{{ route('frontend.pages.show', ['slug' => 'maintenance']) }}</a></td>
            </tr>
            @foreach(wncms()->page()->getList() as $page)
            <tr>
                <td>{{ $page->title }}</td>
                <td>{{ $page->slug }}</td>
                <td><a href="{{ route('frontend.pages.show', ['slug' => $page->slug ]) }}">{{ route('frontend.pages.show', ['slug' => $page->slug ]) }}</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>