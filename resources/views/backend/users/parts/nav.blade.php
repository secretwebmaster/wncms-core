{{-- Navs --}}
<ul class="nav border-transparent fs-5 fw-bold">
    @foreach([
            ['icon' => 'fa-solid fa-address-card','tab' => 'profile'],
            ['icon' => 'fa-solid fa-lock','tab' => 'security'],
            ['icon' => 'fa-solid fa-robot','tab' => 'api'],
            // ['icon' => 'fa-solid fa-crown','tab' => 'referral'],
            // ['icon' => 'fa-solid fa-file-invoice-dollar','tab' => 'invoices'],
            // ['icon' => 'fa-solid fa-dollar','tab' => 'transactions'],
            // ['icon' => 'fa-solid fa-file-lines','tab' => 'credit_records'],
        ] as $user_tab)
    <li class="nav-item">
        <a class="nav-link text-gray-600 text-active-primary text-hover-primary ms-0 mt-0 pt-0 pb-5 {{ wncms_route_is("users.account.{$user_tab['tab']}.show", 'active') }}" href="{{ route("users.account.{$user_tab['tab']}.show") }}">
            <i class="{{ $user_tab['icon'] }} {{ wncms_route_is("users.account.{$user_tab['tab']}.show", 'fa-beat') }}"></i>
            @lang("word.user_{$user_tab['tab']}")
        </a>
    </li>
    @endforeach
</ul>