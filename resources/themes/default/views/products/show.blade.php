@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2>@lang('wncms-ecommerce::word.product_detail')</h2>
        <div class="table-container">
            <table class="kv-table">
                <tr>
                    <th>@lang('wncms-ecommerce::word.product_name')</th>
                    <td>{{ $product->name }}</td>
                </tr>
                <tr>
                    <th>@lang('wncms-ecommerce::word.price')</th>
                    <td>{{ number_format($product->price, 2) }}</td>
                </tr>
                <tr>
                    <th>@lang('wncms-ecommerce::word.type')</th>
                    <td>@lang('wncms-ecommerce::word.' . $product->type)</td>
                </tr>
                <tr>
                    <th>@lang('wncms-ecommerce::word.stock')</th>
                    <td>{{ $product->stock ?? '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('wncms-ecommerce::word.properties')</th>
                    <td>
                        @if(!empty($product->properties))
                            @foreach($product->properties as $key => $value)
                                <div>{{ $key }}: {{ $value }}</div>
                            @endforeach
                        @else
                            <em>@lang('wncms::word.none')</em>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>@lang('wncms-ecommerce::word.variants')</th>
                    <td>
                        @if(!empty($product->variants))
                            @foreach($product->variants as $group => $options)
                                <div class="mb-2">
                                    <strong>{{ ucfirst($group) }}:</strong>
                                    @foreach($options as $option)
                                        <label class="mr-2 inline-flex items-center gap-1">
                                            <input type="radio" name="variant_{{ $group }}" value="{{ $option }}">
                                            <span>{{ $option }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endforeach
                        @else
                            <em>@lang('wncms::word.none')</em>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="mt-4">
            <a class="rounded-md border border-slate-300 px-3 py-2 text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700" href="{{ route('frontend.products.index') }}">@lang('wncms::word.back')</a>
        </div>
    </div>
</main>
@endsection
