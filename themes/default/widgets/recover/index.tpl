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

                    <form action="javascript:void(0);" onsubmit="hcore.security.recover('{recover_url}', '{redirect_url}');" >
                        <div class="error-container">{error}</div>
                        <input type="hidden" name="csrf" value="{csrf_token}">
                        <!--BeginSezDomainHidden-->
                        <input type="hidden" name="domain" value="{domain_name}">
                        <!--EndSezDomainHidden-->

                        <div class="form-group">
                            <label>{_Email}</label>
                            <input class="form-control" name="username" type="email" required="" placeholder="{_Email}">
                        </div>

                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">INVIA</button>
                        </div>
                    </form>
                </div> <!-- end card-body -->
                <div class="card-footer">
                    <div class="col-12 text-center mt-2">
                        <span>{_Check your mailbox for code. If you need help send a mail to} <a href='{help_mail}'>{help_mail}</a></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
