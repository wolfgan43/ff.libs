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
                            <input id="email" class="form-control" type="email" placeholder="{_Insert a new email}*" name='email' required="required"/>
                        </div>

                        <div class="form-group" >
                            <label for="confirm-email">{_New email confirm}</label>
                            <input id="confirm-email" class="form-control" type="email" placeholder="{_Confirm new email}*" name='confirm-email' required="required"/>
                        </div>

                        <div class="form-group">
                            <label for="code-confirm">{_Code}</label>
                            <input id="code-confirm" class="form-control" name="code-confirm" type="number" required="required" placeholder="{_Insert your code}">

                            <a class="resend-code" href="javascript:void(0);" onclick="hcore.security.recover('{recover_url}', '{redirect_url}', true);">Resend code</a>
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
