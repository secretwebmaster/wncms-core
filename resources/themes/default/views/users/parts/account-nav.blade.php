<aside class="lg:col-span-3">
    <div class="sticky top-24 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="mb-3 text-sm font-semibold tracking-wide text-slate-900">@lang('wncms::word.dashboard')</h2>
        <nav class="space-y-1">
            @if(\Illuminate\Support\Facades\Route::has('frontend.users.dashboard'))
                <a href="{{ route('frontend.users.dashboard') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.dashboard')</a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('frontend.users.profile'))
                <a href="{{ route('frontend.users.profile') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.my_account')</a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('frontend.users.profile.edit'))
                <a href="{{ route('frontend.users.profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.edit')</a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('frontend.orders.index'))
                <a href="{{ route('frontend.orders.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.orders')</a>
            @endif

            @if(wncms()->isPackageActive('wncms-ecommerce') && \Illuminate\Support\Facades\Route::has('frontend.users.subscriptions.index'))
                <a href="{{ route('frontend.users.subscriptions.index') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.subscriptions')</a>
            @endif

            @if(wncms()->isPackageActive('wncms-ecommerce') && \Illuminate\Support\Facades\Route::has('frontend.users.card'))
                <a href="{{ route('frontend.users.card') }}" class="block rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100 hover:text-slate-900">@lang('wncms::word.card_recharge')</a>
            @endif

            @if(\Illuminate\Support\Facades\Route::has('frontend.users.logout'))
                <a href="{{ route('frontend.users.logout') }}" class="mt-3 block rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-700">@lang('wncms::word.logout')</a>
            @endif
        </nav>
    </div>
</aside>
