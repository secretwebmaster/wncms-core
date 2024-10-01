<h2>@lang('word.page_list')</h2>
<div class="table-container">
    <table>
        <thead>
            <th>@lang('word.name')</th>
            <th>@lang('word.slug')</th>
            <th>@lang('word.url')</th>
        </thead>
        <tbody>
            <tr>
                <td>@lang('word.home')</td>
                <td>home</td>
                <td><a href="{{ route('frontend.pages.home') }}">{{ route('frontend.pages.home') }}</a></td>
            </tr>
            <tr>
                <td>@lang('word.blog')</td>
                <td>blog</td>
                <td><a href="{{ route('frontend.pages.blog') }}">{{ route('frontend.pages.blog') }}</a></td>
            </tr>
            <tr>
                <td>@lang('word.maintenance')</td>
                <td>maintenance</td>
                <td><a href="{{ route('frontend.pages.single', ['slug' => 'maintenance']) }}">{{ route('frontend.pages.single', ['slug' => 'maintenance']) }}</a></td>
            </tr>
            @foreach(wncms()->page()->getList() as $page)
            <tr>
                <td>{{ $page->title }}</td>
                <td>{{ $page->slug }}</td>
                <td><a href="{{ route('frontend.pages.single', ['slug' => $page->slug ]) }}">{{ route('frontend.pages.single', ['slug' => $page->slug ]) }}</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>