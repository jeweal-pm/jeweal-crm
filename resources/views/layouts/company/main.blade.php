<html>
 
    @include('layouts.company.head')
    @if( Auth::guard('company_users')->check())
    @include('layouts.company.header')

    <body class="hold-transition sidebar-mini layout-fixed som-pos">
    <div class="wrapper">

       <div class="container-fluid box-panel">
          <div class="row">
            
        

            <div class="col-12 no-gutter">
            @yield('content')
            </div>

            </div>
         </div>

    


      
</div>
    </body>
    @endif
    @include('layouts.company.footer')
   
    
</html>