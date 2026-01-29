@extends("$themeId::layouts.app")

@section('content')

<div class="container py-4">
    <h2>@lang('wncms-ecommerce::word.product_detail')</h2>

    <table border="1" cellpadding="6" cellspacing="0" style="width:auto; border-collapse:collapse;">
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.product_name')</th>
            <td>{{ $product->name }}</td>
        </tr>
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.price')</th>
            <td>{{ number_format($product->price, 2) }}</td>
        </tr>
        {{-- <tr>
            <th style="white-space:nowrap;">@lang('wncms::word.status')</th>
            <td>@lang('wncms-ecommerce::word.' . $product->status)</td>
        </tr> --}}
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.type')</th>
            <td>@lang('wncms-ecommerce::word.' . $product->type)</td>
        </tr>
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.stock')</th>
            <td>{{ $product->stock ?? '-' }}</td>
        </tr>

        {{-- Properties --}}
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.properties')</th>
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

        {{-- Variants --}}
        <tr>
            <th style="white-space:nowrap;">@lang('wncms-ecommerce::word.variants')</th>
            <td>
                @if(!empty($product->variants))
                    @foreach($product->variants as $group => $options)
                        <div style="margin-bottom:4px;">
                            <strong>{{ ucfirst($group) }}:</strong>
                            @foreach($options as $option)
                                <label style="margin-right:6px;">
                                    <input type="radio" name="variant_{{ $group }}" value="{{ $option }}">
                                    {{ $option }}
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

    <div style="margin-top:10px;">
        <a href="{{ route('frontend.products.index') }}">@lang('wncms::word.back')</a>
    </div>
</div>

@endsection
