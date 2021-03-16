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

                    <form action="javascript:void(0);" onsubmit="hcore.security.registration('{registration_url}', '{redirect_url}');">
                        <div class="error-container">{error}</div>
                        <input type="hidden" name="csrf" value="{csrf_token}">

                        <div class="form-group">
                            <label for="username">{_Username}</label>
                            <input class="form-control" name="username" type="text" id="username" required="required" placeholder="{_Enter your email or username or tel}">
                        </div>

                        <div class="form-group">
                            <label for="password">{_Password}</label>
                            <input id="password" class="form-control" name="password" type="password" required="required" placeholder="{_Enter your password}">
                        </div>

                        <div class="form-group" >
                            <label for="username">{_Password confirm}</label>
                            <input id="username" class="form-control" type="password" placeholder="{_Confirm password}*" name='confirm-password' required="required"/>
                        </div>

                        <!--BeginSezEmail-->
                        <div class="form-group">
                            <label for="email">{_Email}</label>
                            <input id="email" class="form-control" name="email" type="email" required="required" placeholder="{_Email}*">
                        </div>
                        <!--EndSezEmail-->

                        <!--BeginSezPhone-->
                        <div class="form-group" >
                            <label for="tel">{_Phone}</label>
                            <input id="tel" class="form-control" type="tel" placeholder="{_Insert your cell or phone number}*" name='tel' required="required"/>
                        </div>
                        <!--EndSezPhone-->

                        <!--BeginSezModel-->
                        <div class="form-group">
                            <label for="{field_name}">{field_label}</label>
                            <input class="{field_class}" name="{field_name}" type="{field_type}" id="{field_name}"{field_properties}>
                        </div>
                        <!--EndSezModel-->

                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">{_Register}</button>
                        </div>

                    </form>
                </div> <!-- end card-body -->
            </div>
        </div>
    </div>
</div>
