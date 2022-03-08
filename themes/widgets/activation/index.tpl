<div id="activation-box" class="container">
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

                    <form method="post" class="cm-xhr" data-component="#activation-box">
                        <!--BeginSezError-->
                        <div class="alert alert-warning">{error}</div>
                        <!--EndSezError-->
                        <div class="form-group">
                            <label for="identifier">{_Email}</label>
                            <input id="identifier" class="form-control" name="identifier" type="email" required="required" placeholder="{_Email}">
                        </div>

                        <div class="form-group mb-0 text-center">
                            <button class="btn btn-primary" type="submit">INVIA</button>
                        </div>

                    </form>
                </div> <!-- end card-body -->
                <div class="card-footer">
                    <div class="col-12 text-center mt-2">
                        <span>{_Check your Junk Mail folder if yuou don't find our mail. If you need help contact us: }<a href='mailto:{help_mail}'>{help_mail}</a>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
