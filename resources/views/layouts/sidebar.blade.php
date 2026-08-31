@auth
    <aside class="crm-sidebar">
        <a href="{{ route('dashboard') }}" class="crm-sidebar-brand">
            <span class="crm-brand-mark">J</span>
            <span class="crm-brand-copy">
                <span class="crm-brand-title">Jeweal CRM</span>
                <span class="crm-brand-subtitle">Sales operations</span>
            </span>
        </a>

        <div class="crm-sidebar-section">Workspace</div>
        <ul class="crm-sidebar-nav">
            <li>
                <a href="{{ route('dashboard') }}" class="crm-sidebar-link {{ request()->routeIs('dashboard') ? 'crm-active' : '' }}">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="{{ route('enquiry.index') }}" class="crm-sidebar-link {{ request()->routeIs('enquiry.index') ? 'crm-active' : '' }}">
                    <i class="fas fa-inbox"></i>
                    <span>Enquiries</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gisEnquiry') }}" class="crm-sidebar-link {{ request()->routeIs('gisEnquiry') ? 'crm-active' : '' }}">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>GIS Enquiries</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gis-fair.leads.index') }}" class="crm-sidebar-link {{ request()->routeIs('gis-fair.*') ? 'crm-active' : '' }}">
                    <i class="fas fa-ticket-alt"></i>
                    <span>GIS Fair Funnel</span>
                </a>
            </li>
            <li>
                <a href="{{ route('gms-enquiries.index') }}" class="crm-sidebar-link {{ request()->routeIs('gms-enquiries.*') ? 'crm-active' : '' }}">
                    <i class="fas fa-gem"></i>
                    <span>GMS Enquiries</span>
                </a>
            </li>
            @if(Auth::user()->hasCrmPermission('email.view'))
                <li>
                    <a href="{{ route('email.dashboard') }}" class="crm-sidebar-link {{ request()->routeIs('email.*') ? 'crm-active' : '' }}">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Email Management</span>
                    </a>
                </li>
            @endif
            @if(Auth::user()->hasCrmPermission('whatsapp.view'))
                <li>
                    <a href="{{ route('whatsapp.messages.index') }}" class="crm-sidebar-link {{ request()->routeIs('whatsapp.*') ? 'crm-active' : '' }}">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp Delivery</span>
                    </a>
                </li>
            @endif
        </ul>

        @if(Auth::user()->hasCrmPermission('user.view') || Auth::user()->hasCrmPermission('security.ip.view'))
            <div class="crm-sidebar-section">Administration</div>
            <ul class="crm-sidebar-nav">
                @if(Auth::user()->hasCrmPermission('user.view'))
                    <li>
                        <a href="{{ route('users.index') }}" class="crm-sidebar-link {{ request()->routeIs('users.*') ? 'crm-active' : '' }}">
                            <i class="fas fa-users-cog"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                @endif
                @if(Auth::user()->hasCrmPermission('security.ip.view'))
                    <li>
                        <a href="{{ route('security.ip.index') }}" class="crm-sidebar-link {{ request()->routeIs('security.ip.*') ? 'crm-active' : '' }}">
                            <i class="fas fa-shield-alt"></i>
                            <span>IP Security</span>
                        </a>
                    </li>
                @endif
            </ul>
        @endif

        <div class="crm-sidebar-footer">
            Jeweal internal CRM
        </div>
    </aside>
@endauth
