<div class="my-2">
    @if ($models instanceof Illuminate\Pagination\LengthAwarePaginator)
        <span>@lang('word.showing_item_of_total', [
            'first' =>  $models?->firstItem(),
            'last' =>  $models?->lastItem(),
            'total' =>  $models?->total(),
        ])</span>
    @endif
</div>