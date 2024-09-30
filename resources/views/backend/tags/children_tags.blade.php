@foreach($children as $child)
    <tr class="@if(request()->keyword && strpos($child->name, request()->keyword) !== false) bg-light-info fw-bold text-info @endif">
        <td>
            <div class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $child->id }}"/>
            </div>
        </td>
        <td>
            <a class="btn btn-sm px-2 py-1 btn-primary fw-bold" href="{{ route('tags.create' , ['type' => $child->type,'parent_id' => $child->id]) }}">@lang('word.add_children_tag')</a>
            <a class="btn btn-sm px-2 py-1 btn-dark fw-bold" href="{{ route('tags.edit' , $child) }}">@lang('word.edit')</a>
            @include('backend.parts.modal_delete' , ['model'=>$child , 'route' => route('tags.destroy' , $child)])
        </td>
        <td class="ps-3">{{ $child->id }}</td>
        <td>@lang('word.' . $child->type)</td>
        <td class="@if($level < 2)text-info @endif" title="{{ $child->description }}">{{ $level === 1 ? "├─" : str_repeat('├─', $level)}} #{{ $child->id}} {{ $child->name }}</td>
        <td>{{ $child->slug }}</td>
        <td>{{ $child->order_column }}</td>
        <td>{{ $child->links_count }}</td>
        <td><span class="badge badge-secondary text-gray-600">{{ $child->parent?->name }}</span></td>
        <td>{{ $child->getFirstMediaUrl('child_image')}}</td>
        <td>{{ $child->icon }} <i class="{{ $child->icon }}"></i></td>
        <td>{{ $child->created_at }}</td>
    <tr>
    @if ($child->children->count() > 0)
        @include('backend.tags.children_tags', ['children' => $child->children, 'level' => $level + 1])
    @endif
@endforeach