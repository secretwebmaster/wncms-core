@extends("$themeId::layouts.app")

@section('content')
<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-6 lg:grid-cols-12">
        @include("$themeId::users.parts.account-nav")

        <section class="lg:col-span-9">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-slate-900">@lang('wncms::word.edit') @lang('wncms::word.my_account')</h1>

                <form method="POST" action="{{ route('frontend.users.profile.update') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    @foreach(['first_name','last_name','nickname','email','username'] as $field)
                        <div class="@if($field === 'email') sm:col-span-2 @endif">
                            <label class="mb-1 block text-sm font-medium text-slate-700">{{ $field }}</label>
                            <input type="@if($field === 'email')email@else text @endif" name="{{ $field }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100" value="{{ old($field, $user->{$field}) }}" @if($field === 'username') readonly @endif>
                        </div>
                    @endforeach

                    <div class="sm:col-span-2 flex flex-wrap gap-2">
                        <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">@lang('wncms::word.save_changes')</button>
                        <a href="{{ route('frontend.users.profile') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700">@lang('wncms::word.cancel')</a>
                    </div>
                </form>
            </div>
        </section>
    </div>
</main>
@endsection
