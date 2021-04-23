<div class="container" id="logout-box">
      <div class="row justify-content-center h-100">
        <div class="col-lg-5">
            <div class="card mt-5">
              <!--BeginSezLogo-->
              <div class="card-header pt-4 pb-4 text-center bg-secondary">
                  <a href="{logo_url}">
                      <span><img src="{logo_path}" alt="{page_title}"></span>
                  </a>
              </div>
              <!--EndSezLogo-->

                <div class="card-body p-4">

                    <div class="text-center w-75 m-auto">
                        <img src="{user_avatar}" alt="{user_name}" title="{user_name}" height="88" class="rounded-circle shadow">
                        <h4 class="text-dark-50 text-center mt-3 font-weight-bold">{user_name} </h4>
                        <p class="text-muted mb-4">{_Click the button to sign out.}</p>
                    </div>

                    <form action="javascript:void(0);" onsubmit="hcore.security.logout('{logout_url}', '{redirect_url}');">
                        <!--BeginSezError-->
                        <div class="alert alert-warning">{error}</div>
                        <!--EndSezError-->
                        <input type="hidden" name="csrf" value="{csrf_token}">
                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">{_logout}</button>
                        </div>
                    </form>

                </div> <!-- end card-body-->
            </div>
            <!-- end card-->
            <!-- end row -->

        </div> <!-- end col -->
    </div>
    <!-- end row -->
</div>
<!-- end container -->
