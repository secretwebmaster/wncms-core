@extends("$themeId::layouts.app")

@section('content')
<div class="card-recharge-container">
    <h2>@lang('wncms::word.card_recharge')</h2>

    {{-- Card Recharge Form --}}
    <form method="POST" action="{{ route('frontend.users.card.use') }}">
        @csrf
        <table class="card-recharge-table mb-3">
            <tr>
                <th>@lang('wncms::word.card_code')</th>
                <td>
                    <input type="text" name="code" id="code" class="form-control" placeholder="{{ __('wncms::word.enter_card_code') }}" required>
                </td>
            </tr>
            <tr>
                <th>@lang('wncms::word.balance')</th>
                <td>
                    <span>{{ $user->balance }}</span>
                </td>
            </tr>
        </table>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">@lang('wncms::word.recharge')</button>
        </div>
    </form>

    {{-- Card List --}}
    <h2>@lang('wncms::word.test')</h2>
    <table class="card-list-table">
        <tr>
            <th>@lang('wncms::word.card_code')</th>
            <th>@lang('wncms::word.status')</th>
            <th>@lang('wncms::word.type')</th>
            <th>@lang('wncms::word.value')</th>
            <th>@lang('wncms::word.product')</th>
            <th>@lang('wncms::word.plan')</th>
            <th>@lang('wncms::word.user')</th>
            <th>@lang('wncms::word.redeemed_at')</th>
            <th>@lang('wncms::word.expired_at')</th>
        </tr>
        @foreach(\Secretwebmaster\WncmsEcommerce\Models\Card::limit(10)->get() as $card)
        <tr>
            <td>{{ $card->code }}</td>
            <td>{{ $card->status }}</td>
            <td>{{ $card->type }}</td>
            <td>{{ $card->value }}</td>
            <td>{{ $card->product?->name ?? '-' }}</td>
            <td>{{ $card->plan?->name ?? '-' }}</td>
            <td>{{ $card->user?->username }}</td>
            <td>{{ $card->redeemed_at }}</td>
            <td>{{ $card->expired_at ?? '-' }}</td>
        </tr>
        @endforeach
    </table>

</div>
@endsection
