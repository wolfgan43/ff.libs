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
                    <p class="text-muted mb-4">{_Thank You for visit}</p>
                    <!--BeginSezError-->
                    <div class="alert alert-warning">{error}</div>
                    <!--EndSezError-->
                    <div class="form-group mb-0 text-center">
                        <a href="{login_path}" class="btn btn-primary">{_login}</a>
                        <a href="/" class="btn btn-primary">{_Home}</a>
                    </div>
                </div> <!-- end card-body-->
            </div>
            <!-- end card-->
            <!-- end row -->

        </div> <!-- end col -->
    </div>
    <!-- end row -->
</div>
<!-- end container -->
