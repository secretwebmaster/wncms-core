@php $currentDepth = $depth; @endphp
@foreach ($children as $child)

    <option value="{{ $child->id }}">{{ str_repeat('├─', $depth) }}{{ $child->name }}</option>
    @if ($child->children->count() > 0)
        @php $depth++; @endphp
        @include('backend.tags.recursive_children_options', ['children' => $child->children, 'depth' => $depth])
    @endif
@endforeach
@php $depth = $currentDepth; @endphp