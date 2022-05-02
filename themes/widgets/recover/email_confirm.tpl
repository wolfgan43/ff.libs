<div id="recover-box" class="container">
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
                        <!--BeginSezTitle-->
                        <h4 class="text-dark-50 text-center mt-0 font-weight-bold">{title}</h4>
                        <!--EndSezTitle-->
                        <!--BeginSezDescription-->
                        <p class="text-muted mb-4">{description}</p>
                        <!--EndSezDescription-->
                    </div>

                    <form method="post" class="cm-xhr" data-component="#recover-box">
                        <!--BeginSezError-->
                        <div class="alert alert-warning">{error}</div>
                        <!--EndSezError-->
                        <div class="form-group" >
                            <label for="email">{_New email}</label>
                            <input id="email" class="form-control" type="email" placeholder="{_Insert a new email}*" name='email' required="required" autocomplete="off" />
                        </div>

                        <div class="form-group" >
                            <label for="cemail">{_New email confirm}</label>
                            <input id="cemail" class="form-control" type="email" placeholder="{_Confirm new email}*" name='confirm-email' required="required" autocomplete="off" />
                        </div>

                        <div class="form-group">
                            <label for="ccode">{_Code}</label>
                            <input id="ccode" class="form-control" type="number" placeholder="{_Insert your code}" name="code-confirm" required="required" autocomplete="off" />

                            <a class="resend-code cm-xhr" href="{resend_code}">Resend code</a>
                        </div>

                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">INVIA</button>
                        </div>
                    </form>
                </div> <!-- end card-body -->
            </div>
        </div>
    </div>
</div>
