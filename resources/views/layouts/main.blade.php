<html>
    @include('layouts.head')

    <body class="hold-transition sidebar-mini layout-fixed som-pos">
        @auth
            <div class="crm-app-shell">
                @include('layouts.sidebar')
                <div class="crm-app-main">
                    @include('layouts.header')
                    <main class="crm-app-content">
                        @yield('content')
                    </main>
                </div>
            </div>
        @endauth

        @guest
            <main class="crm-guest-shell">
                @yield('content')
            </main>
        @endguest

        @include('layouts.footer')
    </body>
</html>
