import  * as Forms from "./Forms.js";    


//mapping of names to validation rules
const AuthValidationMap={
    "fname":Forms.ValidateName,
    "lname":Forms.ValidateName,
    "email":Forms.ValidateEmail,
    "bday":Forms.ValidateDate,
    "pass":Forms.ValidatePassword,
    "cpass":Forms.ValidatePassword
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
                             field.classList.remove("Error");
                             const fieldError = field.querySelector(".FieldError");
                             if (fieldError) fieldError.innerHTML = "";
                        }
                    }
                 }
            });
            return errors === 0;
        };

        nextBtn.addEventListener("click", () => {
            if (validateStep(currentStep)) {
                currentStep++;
                showStep(currentStep);
            }
        });

        backBtn.addEventListener("click", () => {
            currentStep--;
            showStep(currentStep);
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (validateStep(currentStep)) {
                let formData = new FormData(form);
                formData.append("ReqType", 1);
                
                let res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                if (res.status) {
                    form.innerHTML = `<div class="AuthBoxMessage">
                        <h1>Check Your Email</h1>
                        <p>${res.message}</p>
                        <a href="index.php" class="BrandBtn">Go to Login</a>
                    </div>`;
                } else {
                    alert(res.message || "An unexpected error occurred.");
                }
            }
        });

        showStep(currentStep);

    }else if(document.body.classList.contains("Login")){

        //select form
        let Loginform = document.getElementById("LoginForm");
        console.log(Loginform);
        if(Loginform){
        //submit form
            Loginform.addEventListener("submit", async (e)=>{
                //validate form
                e.preventDefault();
                
                let Errors = Forms.ValidateTextFields(Loginform,AuthValidationMap);

                console.log("Errors count: ",Errors);


                let protected_pass=document.getElementById('protected_pass');
                
                if(!protected_pass){
                    return;
                }




                //checking if password is empty 
                //this the only check that delivers specific error message (for better UX)
                //the rest of the error messages are vague
                if(protected_pass.value.trim() == ""){

                    let ErrorsHTML=[["Empty Password","Please enter your Password"]]
                    Forms.PopulateFieldError(protected_pass.parentElement.parentElement,ErrorsHTML);
                    return;
                }else{
                    //remove error
                    protected_pass.parentElement.parentElement.classList.remove("Error");
                    let FieldError = protected_pass.parentElement.parentElement.getElementsByClassName("FieldError")[0];
                    if(FieldError){
                        FieldError.innerHTML = "";
                    }
                }


                //validating password format without specifying the exact error (for security reasons)
                let pr_Res=Forms.ValidatePassword(protected_pass.value);
                if(!pr_Res.IsValid){
                    console.log("Invalid password format: ",protected_pass.value);
                    console.log(pr_Res.Errors);
                    
                    Errors++;
                    //keep it vague unlike register 
                    let Parent = protected_pass.parentElement.parentElement;

                let msg=`<p><b>Invalid Credentials:</b> Email or Password is incorrect </p>`;
                FillFormResponse(Parent,msg);
                }else{
                    let ResponseError = protected_pass.parentElement.parentElement.getElementsByClassName("ResponseError")[0];
                    //remove error
                    if(ResponseError){
                        ResponseError.remove();
                    }
                }




                if(Errors == 0 ){
                    //submit form
                    let formData = new FormData(Loginform);
                    formData.append("ReqType", 2);
                    console.log(formData);
                    let Res=await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                    if(Res.status){
                        window.location.href = "index.php";
                    }else{
                        console.log(Res);
                        let Parent = protected_pass.parentElement.parentElement;
                        FillFormResponse(Parent,Res.message);
                        
                    }
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
            resetPasswordForm.addEventListener("submit", async (e) => {
                e.preventDefault();
                const formResponse = resetPasswordForm.querySelector(".FormResponse");
                formResponse.innerHTML = "";
                
                const passField = resetPasswordForm.querySelector("input[name='pass']");
                const cpassField = resetPasswordForm.querySelector("input[name='cpass']");

                // Client-side validation
                let errors = 0;
                const passRes = Forms.ValidatePassword(passField.value);
                if (!passRes.IsValid) {
                    Forms.PopulateFieldError(passField.parentElement.parentElement, passRes.Errors);
                    errors++;
                }
                if (passField.value !== cpassField.value) {
                    Forms.PopulateFieldError(cpassField.parentElement.parentElement, [["Passwords Don't Match: ", "The passwords do not match."]]);
                    errors++;
                }
                
                if (errors > 0) return;

                let formData = new FormData(resetPasswordForm);
                formData.append("ReqType", 4);
                
                let res = await Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
                
                formResponse.innerHTML = `<p>${res.message}</p>`;
                formResponse.className = `FormResponse ${res.status ? 'Success' : 'Error'}`;

                if (res.status) {
                    // Success! Redirect to login after 3 seconds
                    setTimeout(() => {
                        window.location.href = "index.php";
                    }, 3000);
                }
            });
        }
        // --- END NEW LOGIC ---

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
                    setTimeout(() => { window.location.href = "index.php"; }, 2500);
                } else {
                    verifyBtn.disabled = false;
                }
            });
        }

    }else{
        console.warn("No Auth");
    }


})