@extends("$themeId::layouts.app")

@section('content')

<h2>@lang('wncms-ecommerce::word.products')</h2>

@if($products->count())
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
                    <td>
                        <a href="{{ route('frontend.products.show', ['slug' => $product->slug]) }}">
                            @lang('wncms::word.view')
                        </a>
                    </td>
                    <td>
                        <form action="{{ route('frontend.orders.create', ['product_id' => $product->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit">@lang('wncms-ecommerce::word.purchase')</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:10px;">
        {{ $products->links() }}
    </div>
@else
    <p>@lang('wncms::word.no_records_found')</p>
@endif

@endsection
