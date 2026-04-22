@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2>@lang('wncms-ecommerce::word.products')</h2>

        @if($products->count())
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>@lang('wncms::word.name')</th>
                            <th>@lang('wncms-ecommerce::word.price')</th>
                            <th>@lang('wncms-ecommerce::word.type')</th>
                            <th>@lang('wncms::word.view')</th>
                            <th>@lang('wncms-ecommerce::word.purchase')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $product->id }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ number_format($product->price, 2) }}</td>
                                <td>{{ $product->typeLabel }}</td>
                                <td><a href="{{ route('frontend.products.show', ['slug' => $product->slug]) }}">@lang('wncms::word.view')</a></td>
                                <td>
                                    <form action="{{ route('frontend.orders.create', ['product_id' => $product->id]) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="rounded-md border border-slate-300 px-2 py-1 text-xs font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">@lang('wncms-ecommerce::word.purchase')</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $products->links() }}
        @else
            <p>@lang('wncms::word.no_records_found')</p>
        @endif
    </div>
</main>
@endsection
