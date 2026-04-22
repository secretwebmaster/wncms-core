@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")

        <section class="lg:col-span-9 space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.my_account')</h1>
                    <a href="{{ route('frontend.users.profile.edit') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">@lang('wncms::word.edit')</a>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach(['id','first_name','last_name','nickname','email','username','email_verified_at','last_login_at','created_at','referrer_id'] as $field)
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">{{ $field }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-900">{{ $user->{$field} ?: __('wncms::word.n_a') }}</p>
                    </div>
                    @endforeach
                </div>

                @php($hookRows = array_filter(\Illuminate\Support\Facades\Event::dispatch('wncms.view.frontend.users.profile.show.fields', [$user])))
                @foreach($hookRows as $hookRow)
                    <div class="mt-4 rounded-lg border border-slate-200 p-3">{!! $hookRow !!}</div>
                @endforeach
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-slate-900">@lang('wncms::word.credits')</h2>
                @if($user->credits)
                    @if($user->credits->isEmpty())
                        <p class="mt-3 text-sm text-slate-600">@lang('wncms::word.no_credits')</p>
                    @else
                        <div class="table-container mt-3">
                            <table>
                                <thead>
                                    <tr>
                                        <th>@lang('wncms::word.credit_type')</th>
                                        <th>@lang('wncms::word.amount')</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($user->credits as $credit)
                                    <tr>
                                        <td>@lang('wncms::word.' . $credit->type)</td>
                                        <td>{{ number_format($credit->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif
            </div>
        </section>
    </div>
</main>
@endsection
