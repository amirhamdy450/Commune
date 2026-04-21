import  * as Forms from "./Forms.js";    


//mapping of names to validation rules
const AuthValidationMap={
    "fname":Forms.ValidateName,
    "lname":Forms.ValidateName,
    "email":Forms.ValidateEmail,
    "bday":Forms.ValidateDate,
    "pass":Forms.ValidatePassword,
    // cpass is intentionally omitted — only a match check is needed, not format validation
}



/* function ValidateTextFields(form,RuleMap){

    let Errors = 0;


    //iterate over Textfields and validate them
    let TextFields = form.getElementsByClassName("TextField"); 


    [...TextFields].forEach(textfield => {
        let name = textfield.getElementsByTagName("input")[0].getAttribute("name");
        let value = textfield.getElementsByTagName("input")[0].value;

        if(!RuleMap[name]){
            return;
        }


        let Res = RuleMap[name](value);
        let IsValid = Res.IsValid;
        //<div class="FieldError"><p><b>Empty Name: </b>Please enter your Name</p></div> (example)
        if(!IsValid){
            let ErrorsHTML = Res.Errors;
            textfield.classList.add("Error");

            //CHECK IF FIELD ERROR DOESN'T EXIST
            let FieldError = textfield.getElementsByClassName("FieldError")[0];
            if(!FieldError){
                textfield.insertAdjacentHTML("beforeend",`<div class="FieldError"></div>`)
                FieldError = textfield.getElementsByClassName("FieldError")[0];

            }

            FieldError.innerHTML = "";

            //ITERATE OVER ERRORS
            [...ErrorsHTML].forEach(ErrorHTML => {
                FieldError.insertAdjacentHTML("beforeend",`<p><b>${ErrorHTML[0]} </b>${ErrorHTML[1]}</p>`)
                Errors++;

            })
            
            
        }else{
            textfield.classList.remove("Error");
            //REMOVE FIELD ERROR
            let FieldError = textfield.getElementsByClassName("FieldError")[0];
            if(FieldError){
                FieldError.innerHTML = "";
            }
        }

    })


    return Errors;



} */

function FillFormResponse(field,msg){
    let ResponseError = field.getElementsByClassName("ResponseError")[0];
    if(ResponseError){
        ResponseError.innerHTML = "";

        ResponseError.innerHTML=`${msg}`;

    }else{


        field.insertAdjacentHTML("beforeend",`<div class="ResponseError">
            ${msg}
        </div>`)
    }

}


document.addEventListener("DOMContentLoaded", function () {
    if(document.body.classList.contains("Register")){
        const form = document.getElementById("RegisterForm");
        const steps = Array.from(form.getElementsByClassName("FormStep"));
        const nextBtn = document.getElementById("NextBtn");
        const backBtn = document.getElementById("BackBtn");
        const submitBtn = document.getElementById("SubmitBtn");
        const progressSteps = Array.from(form.getElementsByClassName("ProgressStep"));
        let currentStep = 0;

        const showStep = (stepIndex) => {
            steps.forEach((step, index) => {
                step.classList.toggle("active", index === stepIndex);
            });
            progressSteps.forEach((step, index) => {
                step.classList.toggle("active", index <= stepIndex);
            });

            if(stepIndex > 0){
                backBtn.classList.remove("hidden");
            }else{
                backBtn.classList.add("hidden");
            }

            if(stepIndex < steps.length - 1){
                nextBtn.classList.remove("hidden");
            }else{
                nextBtn.classList.add("hidden");
            }

            if(stepIndex === steps.length - 1){
                submitBtn.classList.remove("hidden");
            }else{
                submitBtn.classList.add("hidden");
            }

/*             backBtn.style.display = stepIndex > 0 ? "block" : "none";
            nextBtn.style.display = stepIndex < steps.length - 1 ? "block" : "none";
            submitBtn.style.display = stepIndex === steps.length - 1 ? "block" : "none"; */
        };

        const clearFieldError = (field) => {
            field.classList.remove("Error");
            const fieldError = field.querySelector(".FieldError");
            if (fieldError) fieldError.innerHTML = "";
        };

        const validateStep = (stepIndex) => {
            const currentStepFields = steps[stepIndex].getElementsByClassName("TextField");
            let errors = 0;

            [...currentStepFields].forEach(field => {
                const input = field.querySelector("input:not([type=radio]), select");
                if (input) {
                    const name = input.getAttribute("name");
                    if (AuthValidationMap[name]) {
                        const res = AuthValidationMap[name](input.value);
                        if (!res.IsValid) {
                            errors++;
                            Forms.PopulateFieldError(field, res.Errors);
                        } else {
                            clearFieldError(field);
                        }
                    }
                }
            });

            // Step 2: confirm password must match password
            if (stepIndex === 1) {
                const passField = steps[1].querySelector("input[name='pass']");
                const cpassField = steps[1].querySelector("input[name='cpass']");
                const cpassWrapper = cpassField.closest(".TextField");
                if (passField.value !== cpassField.value) {
                    errors++;
                    Forms.PopulateFieldError(cpassWrapper, [["Passwords don't match: ", "Both passwords must be identical."]]);
                }
            }

            // Step 3: gender radio must be selected
            if (stepIndex === 2) {
                const genderField = steps[2].querySelector(".TextField:has(.RadioGroup)");
                const genderSelected = steps[2].querySelector("input[name='gender']:checked");
                if (!genderSelected) {
                    errors++;
                    Forms.PopulateFieldError(genderField, [["Required: ", "Please select a gender."]]);
                } else {
                    clearFieldError(genderField);
                }
            }

            return errors === 0;
        };

        const formResponse = document.getElementById("RegisterFormResponse");
        const registerLoader = document.getElementById("RegisterLoader");
        const registerLoaderMsg = document.getElementById("RegisterLoaderMsg");

        function showRegisterError(msg) {
            formResponse.innerHTML = `<p>${msg}</p>`;
            formResponse.className = "FormResponse Error";
        }

        function clearRegisterResponse() {
            formResponse.innerHTML = "";
            formResponse.className = "FormResponse";
        }

        function setRegisterLoading(loading, msg) {
            if (loading) {
                nextBtn.classList.add("hidden");
                submitBtn.classList.add("hidden");
                backBtn.disabled = true;
                registerLoaderMsg.textContent = msg || "Please wait…";
                registerLoader.classList.remove("hidden");
            } else {
                registerLoader.classList.add("hidden");
                backBtn.disabled = false;
                showStep(currentStep);
            }
        }

        nextBtn.addEventListener("click", async () => {
            clearRegisterResponse();
            if (!validateStep(currentStep)) return;

            // Step 1 (index 1) = email + password — check email availability before advancing
            if (currentStep === 1) {
                const emailVal = steps[1].querySelector("input[name='email']").value.trim();
                setRegisterLoading(true, "Checking email…");
                try {
                    const checkData = new FormData();
                    checkData.append("ReqType", 7);
                    checkData.append("email", emailVal);
                    const res = await Forms.Submit("POST", "Origin/Auth/Auth.php", checkData);
                    if (!res.available) {
                        setRegisterLoading(false);
                        const emailField = steps[1].querySelector("input[name='email']").closest(".TextField");
                        Forms.PopulateFieldError(emailField, [["Email taken: ", res.message || "This email is already registered."]]);
                        return;
                    }
                } catch (_) {
                    setRegisterLoading(false);
                    showRegisterError("Could not verify email. Please try again.");
                    return;
                }
                setRegisterLoading(false);
            }

            currentStep++;
            showStep(currentStep);
        });

        backBtn.addEventListener("click", () => {
            clearRegisterResponse();
            currentStep--;
            showStep(currentStep);
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            clearRegisterResponse();
            if (!validateStep(currentStep)) return;

            const formData = new FormData(form);
            formData.append("ReqType", 1);

            setRegisterLoading(true, "Creating your account…");
            try {
                const res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                if (res.status) {
                    const email = encodeURIComponent(formData.get("email"));
                    window.location.href = `index.php?redirect=pending-verification&email=${email}`;
                } else {
                    setRegisterLoading(false);
                    showRegisterError(res.message || "An unexpected error occurred.");
                }
            } catch (_) {
                setRegisterLoading(false);
                showRegisterError("A network error occurred. Please try again.");
            }
        });

        showStep(currentStep);

    }else if(document.body.classList.contains("Login")){

        // Show success banner if redirected after email verification
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get("verified") === "1") {
            const loginBox = document.querySelector(".AuthBox");
            loginBox.insertAdjacentHTML("afterbegin", `<div class="FormResponse Success" style="margin-bottom:16px;"><p>Email verified! You can now log in.</p></div>`);
        }

        let Loginform = document.getElementById("LoginForm");
        if(Loginform){
            const loginSubmitBtn = document.getElementById("LoginSubmitBtn");
            const loginLoader = document.getElementById("LoginLoader");
            const loginFormResponse = document.getElementById("LoginFormResponse");

            function showLoginError(msg) {
                loginFormResponse.innerHTML = `<p>${msg}</p>`;
                loginFormResponse.className = "FormResponse Error";
            }

            function setLoginLoading(loading) {
                if (loading) {
                    loginSubmitBtn.classList.add("hidden");
                    loginLoader.classList.remove("hidden");
                    loginFormResponse.innerHTML = "";
                    loginFormResponse.className = "FormResponse";
                } else {
                    loginLoader.classList.add("hidden");
                    loginSubmitBtn.classList.remove("hidden");
                }
            }

            Loginform.addEventListener("submit", async (e) => {
                e.preventDefault();

                const protected_pass = document.getElementById('protected_pass');
                if (!protected_pass) return;

                // Clear previous errors
                loginFormResponse.innerHTML = "";
                loginFormResponse.className = "FormResponse";

                let errors = Forms.ValidateTextFields(Loginform, AuthValidationMap);

                if (protected_pass.value.trim() === "") {
                    Forms.PopulateFieldError(protected_pass.parentElement.parentElement, [["Empty Password", "Please enter your Password"]]);
                    return;
                } else {
                    protected_pass.parentElement.parentElement.classList.remove("Error");
                    const fieldError = protected_pass.parentElement.parentElement.getElementsByClassName("FieldError")[0];
                    if (fieldError) fieldError.innerHTML = "";
                }

                const pr_Res = Forms.ValidatePassword(protected_pass.value);
                if (!pr_Res.IsValid) {
                    errors++;
                    showLoginError("Invalid credentials — please check your email and password.");
                } else {
                    const responseError = protected_pass.parentElement.parentElement.getElementsByClassName("ResponseError")[0];
                    if (responseError) responseError.remove();
                }

                if (errors > 0) return;

                setLoginLoading(true);
                try {
                    const formData = new FormData(Loginform);
                    formData.append("ReqType", 2);
                    const Res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                    if (Res.status) {
                        window.location.href = "index.php";
                    } else if (Res.code === 54) {
                        const email = encodeURIComponent(formData.get("email"));
                        window.location.href = `index.php?redirect=pending-verification&email=${email}`;
                    } else {
                        setLoginLoading(false);
                        showLoginError(Res.message || "Invalid credentials.");
                    }
                } catch (_) {
                    setLoginLoading(false);
                    showLoginError("A network error occurred. Please try again.");
                }







                

            });
        }


                    // --- START NEW LOGIC ---

        // REQUEST RESET FORM
        const requestResetForm = document.getElementById("RequestResetForm");
        if (requestResetForm) {
            const loader = requestResetForm.querySelector(".Loader");
            const submitBtn = requestResetForm.querySelector("input[type='submit']");

            const formContainer = document.getElementById("RequestResetForm");
            const successView = document.getElementById("ResetSuccessView");

            requestResetForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                const formResponse = requestResetForm.querySelector(".FormResponse");
                formResponse.innerHTML = ""; // Clear previous messages
                
                const emailField = requestResetForm.querySelector("input[name='email']").parentElement;
                const emailVal = requestResetForm.querySelector("input[name='email']").value;
                
                // Client-side validation
                const emailRes = Forms.ValidateEmail(emailVal);
                if (!emailRes.IsValid) {
                    Forms.PopulateFieldError(emailField, emailRes.Errors);
                    return;
                }
                
                let formData = new FormData(requestResetForm);
                formData.append("ReqType", 3);

                loader.classList.remove("hidden");
                submitBtn.disabled = true;
                
                try {
                    let res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                
                    if (res.status) {
                        // SUCCESS: Hide form, show success message
                        formContainer.classList.add("hidden");
                        successView.classList.remove("hidden");
                    } else {
                        // FAILURE: Show error message on the form
                        formResponse.innerHTML = `<p>${res.message}</p>`;
                        formResponse.className = `FormResponse Error`;
                    }
                
                } catch (error) {
                    console.error("Error during password reset request:", error);
                    formResponse.innerHTML = `<p>An unexpected network error occurred.</p>`;
                    formResponse.className = `FormResponse Error`;
                } finally {
                    // --- START NEW CODE ---
                    // Always hide loader and re-enable button
                    loader.classList.add("hidden");
                    submitBtn.disabled = false;
                    // --- END NEW CODE ---
                }
            });
        }

        // RESET PASSWORD FORM
        const resetPasswordForm = document.getElementById("ResetPasswordForm");
        if (resetPasswordForm) {
            const resetSubmitBtn = document.getElementById("ResetSubmitBtn");
            const resetLoader = document.getElementById("ResetLoader");

            resetPasswordForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                const formResponse = resetPasswordForm.querySelector(".FormResponse");
                formResponse.innerHTML = "";
                formResponse.className = "FormResponse";

                const passField = resetPasswordForm.querySelector("input[name='pass']");
                const cpassField = resetPasswordForm.querySelector("input[name='cpass']");

                let errors = 0;
                const passRes = Forms.ValidatePassword(passField.value);
                if (!passRes.IsValid) {
                    Forms.PopulateFieldError(passField.parentElement.parentElement, passRes.Errors);
                    errors++;
                }
                if (passField.value !== cpassField.value) {
                    Forms.PopulateFieldError(cpassField.parentElement.parentElement, [["Passwords don't match: ", "Both passwords must be identical."]]);
                    errors++;
                }

                if (errors > 0) return;

                resetSubmitBtn.classList.add("hidden");
                resetLoader.classList.remove("hidden");

                try {
                    const formData = new FormData(resetPasswordForm);
                    formData.append("ReqType", 4);
                    const res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);

                    formResponse.innerHTML = `<p>${res.message}</p>`;
                    formResponse.className = `FormResponse ${res.status ? 'Success' : 'Error'}`;

                    if (res.status) {
                        setTimeout(() => { window.location.href = "index.php"; }, 3000);
                    } else {
                        resetLoader.classList.add("hidden");
                        resetSubmitBtn.classList.remove("hidden");
                    }
                } catch (_) {
                    resetLoader.classList.add("hidden");
                    resetSubmitBtn.classList.remove("hidden");
                    formResponse.innerHTML = "<p>A network error occurred. Please try again.</p>";
                    formResponse.className = "FormResponse Error";
                }
            });
        }
        // --- END NEW LOGIC ---

    }else if(document.body.classList.contains("PendingVerification")){

        // Populate email from URL param
        const urlParams = new URLSearchParams(window.location.search);
        const pendingEmail = urlParams.get("email") || "";
        const emailDisplay = document.getElementById("PendingEmail");
        if (emailDisplay && pendingEmail) {
            emailDisplay.textContent = decodeURIComponent(pendingEmail);
        }

        const resendBtn = document.getElementById("ResendVerificationBtn");
        const resendResponse = document.getElementById("ResendResponse");
        if (resendBtn) {
            resendBtn.addEventListener("click", async () => {
                resendBtn.disabled = true;
                resendBtn.textContent = "Sending...";
                resendResponse.innerHTML = "";

                const formData = new FormData();
                formData.append("ReqType", 6);
                formData.append("email", decodeURIComponent(pendingEmail));

                const res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);

                resendResponse.innerHTML = `<p>${res.message}</p>`;
                resendResponse.className = `FormResponse ${res.status ? 'Success' : 'Error'}`;
                resendBtn.textContent = "Resend Email";

                // Disable for 30s to prevent spam
                setTimeout(() => { resendBtn.disabled = false; }, 30000);
            });
        }

    }else if(document.body.classList.contains("VerifyEmail")){

        const verifyBtn = document.getElementById("VerifyEmailBtn");
        if (verifyBtn) {
            verifyBtn.addEventListener("click", async () => {
                const token = verifyBtn.getAttribute("data-token");
                const formResponse = document.querySelector(".FormResponse");
                verifyBtn.disabled = true;

                const formData = new FormData();
                formData.append("ReqType", 5);
                formData.append("token", token);

                let res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);

                formResponse.innerHTML = `<p>${res.message}</p>`;
                formResponse.className = `FormResponse ${res.status ? 'Success' : 'Error'}`;

                if (res.status) {
                    verifyBtn.remove();
                    setTimeout(() => { window.location.href = "index.php?verified=1"; }, 2000);
                } else {
                    verifyBtn.disabled = false;
                }
            });
        }

    }else{
        console.warn("No Auth");
    }


})