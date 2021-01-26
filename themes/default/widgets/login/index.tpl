<div id="login-box" class="container">
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

                <form action="javascript:void(0);" onsubmit="hcore.security.login('{login_url}', '{redirect_url}');" enctype="multipart/form-data">
                    <div class="error-container">{error}</div>
                    <input type="hidden" name="csrf" value="{csrf_token}">
                    <!--BeginSezDomainHidden-->
                    <input type="hidden" name="domain" value="{domain_name}">
                    <!--EndSezDomainHidden-->
                    <!--BeginSezDomain-->
                    <div class="form-group">
                        <label for="domain">{_Domain}</label>
                        <input class="form-control" name="domain" id="domain" value="{domain}" placeholder="{_Enter domain}">
                    </div>
                    <!--EndSezDomain-->

                    <div class="form-group">
                        <!--BeginSezRecoverAccount-->
                        <a href="{recover_account_url}" class="text-muted float-right" tabindex="-1"><small>{_Forgot your account?}</small></a>
                        <!--EndSezRecoverAccount-->
                        <label for="emailaddress">{_Account}</label>
                        <input class="form-control" name="username" type="text" id="emailaddress" required="" placeholder="{_Enter your email or username or tel}" autocomplete="username">
                    </div>

                    <div class="form-group">
                        <!--BeginSezRecoverPassword-->
                        <a href="{recover_password_url}" class="text-muted float-right" tabindex="-1"><small>{_Forgot your password?}</small></a>
                        <!--EndSezRecoverPassword-->
                        <label for="password">{_Password}</label>
                        <input class="form-control" name="password" type="password" required="" id="password" placeholder="{_Enter your password}" autocomplete="current-password">
                    </div>
                    <!--BeginSezStayConnect-->
                    <div class="form-group mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="checkbox-signin" checked="">
                            <label class="custom-control-label" for="checkbox-signin">{_Remember me}</label>
                        </div>
                    </div>
                    <!--EndSezStayConnect-->

                    <div class="form-group mb-0 text-center">
                        <button class="btn btn-primary" type="submit">{_Log In}</button>
                    </div>
                    <!--BeginSezSocialLogin-->
                    <div class="text-center mt-4">
                        <p class="text-muted font-16">{_Sign in with}</p>
                        <ul class="social-list list-inline mt-3">
                            <!--BeginSezSocialLoginFacebook-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-primary text-primary"><span class="{social_icon}"></span></a>
                            </li>
                            <!--EndSezSocialLoginFacebook-->
                            <!--BeginSezSocialLoginGplus-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-danger text-danger"><span class="mdi mdi-google"></span></a>
                            </li>
                            <!--EndSezSocialLoginGplus-->
                            <!--BeginSezSocialLoginTwitter-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-info text-info"><span class="{social_icon}"></span></a>
                            </li>
                            <!--EndSezSocialLoginTwitter-->
                            <!--BeginSezSocialLoginLinkedin-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-secondary text-secondary"><span class="{social_icon}"></span></a>
                            </li>
                            <!--EndSezSocialLoginLinkedin-->
                            <!--BeginSezSocialLoginDribbble-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-secondary text-secondary"><span class="{social_icon}"></span></a>
                            </li>
                            <!--EndSezSocialLoginDribbble-->
                            <!--BeginSezSocialLoginOther-->
                            <li class="list-inline-item">
                                <a href="{social_url}" class="social-list-item border-primary text-primary"><span class="{social_icon}"></span></a>
                            </li>
                            <!--EndSezSocialLoginOther-->
                        </ul>
                    </div>
                    <!--EndSezSocialLogin-->
                </form>
            </div> <!-- end card-body -->
            <!--BeginSezRegistration-->
            <div class="card-footer">
                <div class="col-12 text-center mt-2">
                    <p class="text-muted">{_Don't have an account?}<a href="{registration_url}" class="text-dark ml-1"><strong>{_Sign Up}</strong></a></p>
                </div>
            </div>
            <!--EndSezRegistration-->
        </div>
        </div>
    </div>
</div>
