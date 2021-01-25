<div id="registration-box" class="container">
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

                    <form action="javascript:void(0);" onsubmit="hcore.Auth.registration('{registration_url}', '{redirect_url}');">
                        <div class="error-container">{error}</div>
                        <input type="hidden" name="csrf" value="{csrf_token}">
                        <!--BeginSezDomainHidden-->
                        <input type="hidden" name="domain" value="{domain_name}">
                        <!--EndSezDomainHidden-->

                        <div class="form-group">
                            <label for="emailaddress">{_Username}</label>
                            <input class="form-control" name="username" type="text" id="emailaddress" required="" placeholder="{_Enter your email or username or tel}">
                        </div>

                        <div class="form-group">
                            <label for="password">{_Password}</label>
                            <input class="form-control" name="password" type="password" required="" id="password" placeholder="{_Enter your password}">
                        </div>

                        <div class="form-group" >
                            <label>{_Password confirm}</label>
                            <input class="form-control" type="password" placeholder="{_Confirm password}*" name='confirm-password' required=""/>
                        </div>

                        <!--BeginSezEmail-->
                        <div class="form-group">
                            <label>{_Email}</label>
                            <input class="form-control" name="email" type="email" required="" placeholder="{_Email}*">
                        </div>
                        <!--EndSezEmail-->

                        <!--BeginSezPhone-->
                        <div class="form-group" >
                            <label>{_Phone}</label>
                            <input class="form-control" type="tel" placeholder="{_Insert your cell or phone number}*" name='tel' required=""/>
                        </div>
                        <!--EndSezPhone-->

                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">{_Register}</button>
                        </div>

                    </form>
                </div> <!-- end card-body -->
            </div>
        </div>
    </div>
</div>
