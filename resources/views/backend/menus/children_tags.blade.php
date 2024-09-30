<div class="mb-10">
    @foreach($children as $child)
        <div class="col-12">
            <div class="form-check form-check-sm form-check-custom form-check-solid mb-2">
                <input class="form-check-input" type="checkbox" data-id="{{ $child->id }}" data-name="{{ $child->name }}" data-type="{{ $child->type }}" data-model-type="Tag" data-model-id="{{ $child->id }}" id="checkbox_{{ $child->type }}_{{ $child->id }}">
                <label class="form-check-label small" for="checkbox_{{ $child->type }}_{{ $child->id }}">{{ $level === 1 ? "├─" : str_repeat('├─', $level)}} #{{ $child->id}} {{ $child->name }}</label>
            </div>
        </div>

        @if ($child->children->count() > 0)
            @include('backend.menus.children_tags', ['children' => $child->children, 'level' => $level + 1])
        @endif
    @endforeach
</div>
