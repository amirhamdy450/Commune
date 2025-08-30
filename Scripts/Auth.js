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
        console.log("Register");
        //select form
        let form = document.getElementById("RegisterForm");

        //submit form
        form.addEventListener("submit", (e)=>{
            e.preventDefault();


            let Errors = Forms.ValidateTextFields(form,AuthValidationMap);


            if(Errors == 0){
                //submit form
                let formData = new FormData(form);
                formData.append("ReqType", 1);
                console.log(formData);
                Forms.Submit("POST", "Origin/Auth/Auth.php", formData);
            }
        });

    }else if(document.body.classList.contains("Login")){

        //select form
        let form = document.getElementById("LoginForm");
        console.log(form);
        //submit form
        form.addEventListener("submit", async (e)=>{
            //validate form
            e.preventDefault();
            
            let Errors = Forms.ValidateTextFields(form,AuthValidationMap);

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
                let formData = new FormData(form);
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

    }else{
        console.warn("No Auth");
    }


})