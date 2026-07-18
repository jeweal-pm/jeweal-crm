@extends('layouts.UserLayout')
 
@section('head')
@section('title', 'SOM POS JEWEAL ADMIN TEAM')
@endsection


@section('content')
    <div class="row pt-4">
    <div class="container-fluid">
          <div class="row">
            <div class="col-6">
                <ul class="pl-0 page-label" >
                  <li>User</li>
                  <li class="active">Create Company</li>

                </ul>
            </div>
            <div class="col-6"> 
                <div class="action-box">
                    <ul class="list-unstyled">
                    
                      <li><button  class="create reset-btn">Cancel</button></li>
                      <li><button class="create">Create</button></li>
                    </ul>
                </div>

            </div>
</div>
          </div>

    </div>

<div class="card">

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
      <form class="create_form" id="create_form">

        <div class="row">
        <h2 class="page-header h-line-after">Create Company</h2>
        <div class="card-body">
                  <div class="form-group row">
                    <label for="company_name" class="col-sm-2 col-form-label">Company Name*</label>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Company Name" required>
                    </div>
                  </div>
             
                  <div class="form-group row">
                    <label for="company_store_name" class="col-sm-2 col-form-label">Company Store Name*</label>
                    <div class="col-sm-10">
                      <input type="text" class="form-control" id="company_store_name" name="company_store_name" placeholder="Company Store Name" required>
                    </div>
                  </div>

                </div>
          <!-- /.col -->
        </div>

        </form>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>

</div>


@endsection

@section("footer")
  @section('footer_script')
 
    <script>
        jQuery(document).ready(function($){
           $('.create').on('click',function(){
            

           
            axios.post('/company',{
                company_name:$('#company_name').val(),
                company_store_name:$('#company_store_name').val()
            }).then((resp)=>{
              console.log(resp);
              if(resp.data.message=="complete"){
                jQuery('<div class="alert-process-data">เพิ่มบริษัทเสร็จสมบูรณ์</div>').insertAfter('.page-header')
              }
            })
           }) 
        });
    </script>
  @endsection
@endsection
