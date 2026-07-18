@auth
    @php
        $nameParts = collect(explode(' ', trim(Auth::user()->name)))
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
        $roleName = Auth::user()->primaryRoleName();
    @endphp

    <header class="crm-topnav">
        <div class="crm-topnav-title">
            {{ now()->format('d M Y') }} / CRM Workspace
        </div>

        <div class="crm-topnav-user">
            <div class="crm-topnav-avatar">{{ $nameParts ?: 'U' }}</div>
            <div class="crm-user-copy">
                <div class="crm-topnav-name">{{ Auth::user()->name }}</div>
                <div class="crm-topnav-role">{{ $roleName ? ucwords(str_replace('_', ' ', $roleName)) : 'No role' }}</div>
            </div>
            <a href="{{ route('logout') }}" class="crm-logout" title="Logout">
                <i class="fa fa-power-off"></i>
            </a>
        </div>
    </header>
@endauth
