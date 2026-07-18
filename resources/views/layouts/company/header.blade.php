@auth


<header class="header_login">
<div class="container-fluid">
        <div class="row">

            <div class="col-md-8">
             <img src="images/logopos.png" class="logo">
            </div>
            <div class="col-md-4">
            <div class="user-box">
                    <nav class="main-header navbar">
    <!-- Left navbar links -->


    <!-- Right navbar links -->
    <ul class="navbar-nav">
     

      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
          <img src="dist/img/user2-160x160.jpg" class="user-image img-circle elevation-2" alt="User Image">
          <span class="d-none d-md-inline">{{Auth::guard('company_users')->user()->name}}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <!-- User image -->
          <li class="user-header bg-primary">
            <img src="../../dist/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">

            <p>
              Alexander Pierce - Web Developer
              <small>Member since Nov. 2012</small>
            </p>
          </li>
          <!-- Menu Body -->
          <li class="user-body">
            <div class="row">
              <div class="col-4 text-center">
                <a href="#">Followers</a>
              </div>
              <div class="col-4 text-center">
                <a href="#">Sales</a>
              </div>
              <div class="col-4 text-center">
                <a href="#">Friends</a>
              </div>
            </div>
            <!-- /.row -->
          </li>
          <!-- Menu Footer-->
          <li class="user-footer">
            <a href="#" class="btn btn-default btn-flat">Profile</a>
            <a href="/logout" class="btn btn-default btn-flat float-right">Sign out</a>
          </li>
        </ul>
      </li>
 
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

      </ul>
                    </div>

                    <div class="lang-box">
                    <input type="checkbox" onText="ddd" class="lang" name="lang-checkbox" checked >

                    </div>
              
            
            </div>
        </div>
    </div>
</header>
@endauth
 
@guest

<header class="header_login">
        <div class="container-fluid">
        <div class="row-fluid">

            <div class="col-md-12">
             <img src="images/jeweal.png" class="logo">
            </div>
        </div>
    </div>
</header>
@endguest

