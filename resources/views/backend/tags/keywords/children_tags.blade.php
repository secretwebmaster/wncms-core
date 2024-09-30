@foreach($children as $child)
    <tr class="@if(request()->keyword && strpos($child->name, request()->keyword) !== false) bg-light-info fw-bold text-info @endif">
        <td>
            <div class="form-check form-check-sm form-check-custom form-check-solid">
                <input class="form-check-input" type="checkbox" value="1" data-model-id="{{ $parent->id }}"/>
            </div>
        </td>
        <td>
            @include('backend.tags.keywords.modal_bind_keyword', [
                'binding_model' => $parent
            ])
        </td>
        <td class="ps-3">{{ $child->id }}</td>
        <td>@lang('word.' . $child->type)</td>
        <td class="text-info" title="{{ $child->description }}">{{ $level === 1 ? "├─" : str_repeat('├─', $level)}} #{{ $child->id}} {{ $child->name }}</td>
        <td>{{ $child->keywords->pluck('name')->implode(",") }}</td>

    <tr>
    @if ($child->children->count() > 0)
        @include('backend.tags.keywords.children_tags', ['children' => $child->children, 'level' => $level + 1])
    @endif
@endforeach