<h2>{{ wncms_model_word('tag', 'index') }}</h2>
<div class="tabs">
    @foreach($wncms->tag()->getTypes() as $type)
        <button class="tab-link @if($loop->index == 0) active @endif" onclick="openTab(event, '{{ $type }}')">@lang('word.' . $type)</button>
    @endforeach
</div>

@foreach($wncms->tag()->getTypes() as $type)
    <div id="{{ $type }}" class="tab-content @if($loop->index == 0) active @endif">
        <div class="table-container">
            <table>
                <thead>
                    <th>@lang('word.id')</th>
                    <th>@lang('word.name')</th>
                </thead>
                <tbody>
                    @foreach($wncms->tag()->getList(tagType:$type,pageSize:10) as $tag)
                        <tr>
                            <td>{{ $tag->id }}</td>
                            <td><a href="{{ $tag->postCategoryUrl }}">{{ $tag->name }}</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

@push('foot_js')
    <script>
        function openTab(event, tabId) {
            // Get all elements with class="tab-content" and hide them
            var tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(function(content) {
                content.classList.remove('active');
            });

            // Get all elements with class="tab-link" and remove the class "active"
            var tabLinks = document.querySelectorAll('.tab-link');
            tabLinks.forEach(function(link) {
                link.classList.remove('active');
            });

            // Show the current tab content and add "active" class to the clicked tab
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
@endpush