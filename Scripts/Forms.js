export const CsrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

export async function Submit( ReqMethod, ReqTarget, formData) {

    try {
      const response = await fetch(`${ReqTarget}`, {
        method: `${ReqMethod}`,
        headers: { 'X-CSRF-Token': CsrfToken },
        body: formData,
      });

      const data = await response.json();
      return data;
    } catch (error) {

      console.error("Error:", error);
      // Handle fetch errors here (optional)
      return { success: false, message: "Error submitting form" }; // Example error handling
    }
  
}



export function ValidateEmail(email) {
  //CHECK IF EMAIL IS NOT NULL (FAST RETURN)
  if(email.length===0){
    return {
      IsValid: false,
      Errors: [['Invalid Email: ' , 'Email cannot be empty.']]
    };
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  let Errors=[];
  if(!emailRegex.test(email)){
    Errors=[['Invalid Email: ' , 'Please enter a valid email address.']];

    return {
      IsValid: false,
      Errors: Errors
    };
  }else{
    return {
      IsValid: true
      };
  }
}


export function ValidatePassword(password) {
  let Errors=[];
  let IsValid=true;


  //CHECK IF PASSWORD NOT NULL (FAST RETURN)
  if(password.length===0){
    Errors.push(['Invalid Password: ' , 'Password cannot be empty.']);
    IsValid=false;

    return {
      IsValid: false,
      Errors: Errors
    };
  }

  //CHECK IF IT CONTAINS AT LEAST 8 CHARACTERS
  if(password.length<8){
    Errors.push(['Invalid Password: ' , 'Password must be at least 8 characters long.']);
    IsValid=false;
  }

  //CHECK IF IT CONTAINS AT LEAST 1 UPPERCASE LETTER
  if(!/[A-Z]/.test(password)){
    Errors.push(['Invalid Password: ' , 'Password must contain at least 1 uppercase letter.']);
    IsValid=false;
  }

  //CHECK IF IT CONTAINS AT LEAST 1 LOWERCASE LETTER
  if(!/[a-z]/.test(password)){
    Errors.push(['Invalid Password: ' , 'Password must contain at least 1 lowercase letter.']);
    IsValid=false;

  }

  //CHECK IF IT CONTAINS AT LEAST 1 NUMBER
  if(!/\d/.test(password)){
    Errors.push(['Invalid Password: ' , 'Password must contain at least 1 number.']);
    IsValid=false;

  }


  if(IsValid){
    return {
      IsValid: true
    };
  }else{
    return {
      IsValid: false,
      Errors: Errors
    };
  }

}


export function ValidateDate(date) {
  const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
  let IsValid=true;
  let Errors=[];
  //check if date is not null (Fast Return)
  if(date.length===0){
    Errors.push(['Invalid Date: ' , 'Date cannot be empty.']);
    IsValid=false;

    return {
      IsValid: false,
      Errors: Errors
    };
  }


  // Check if the date string matches the format "YYYY-MM-DD"
  if(!dateRegex.test(date)) {
    Errors.push(['Invalid Date: ' , 'Please enter a valid date in the format YYYY-MM-DD.']);
    IsValid=false;
    return {
      IsValid: false,
      Errors: Errors
    };
  }else{
    const parsed = new Date(date);
    const minAge = new Date();
    minAge.setFullYear(minAge.getFullYear() - 13);
    const minYear = new Date('1900-01-01');

    if (parsed > minAge) {
      Errors.push(['Invalid Date: ', 'You must be at least 13 years old.']);
      IsValid = false;
    } else if (parsed < minYear) {
      Errors.push(['Invalid Date: ', 'Please enter a valid birth year.']);
      IsValid = false;
    }

    return { IsValid, Errors };
  }

}

export function ValidateName(name) {
  let Errors=[];
  let IsValid=true;
  
  //check if name does not contain special characters
  const specialChars = /[`!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~]/;
  if (specialChars.test(name)) {
    Errors.push(['Invalid Name: ' , 'Name cannot contain special characters.']);
    IsValid=false;

  }
 
  //check if name is not empty (Fast Return)
  if(name.length===0){
    Errors.push(['Invalid Name: ' , 'Name cannot be empty.']);
    IsValid=false;

    return {
      IsValid: false,
      Errors: Errors
    };
  }

  //check if name does not contain numbers
  const numberRegex = /\d/;
  if (numberRegex.test(name)) {
    Errors.push(['Invalid Name: ' , 'Name cannot contain numbers.']);
    IsValid=false;
  }



  if(IsValid){
    return {
      IsValid: true
    };
  }else{
    return {
      IsValid: false,
      Errors: Errors
    };
  }


}





//HTML FORM HANDLING

export function PopulateFieldError(field,Errors){
  let ErrorsHTML = Errors;

  field.classList.add("Error");
  let FieldError = field.getElementsByClassName("FieldError")[0];
  if(!FieldError){
      field.insertAdjacentHTML("beforeend",`<div class="FieldError"></div>`)
      FieldError = field.getElementsByClassName("FieldError")[0];

  }

  FieldError.innerHTML = "";

  //ITERATE OVER ERRORS
  [...ErrorsHTML].forEach(ErrorHTML => {
      FieldError.insertAdjacentHTML("beforeend",`<p><b>${ErrorHTML[0]} </b>${ErrorHTML[1]}</p>`)
     // Errors++;

  })

}

export function ValidateTextFields(form,RuleMap){
	

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
/*             textfield.classList.add("Error");

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
             */
            PopulateFieldError(textfield,ErrorsHTML);
            Errors++;
            
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



}


export function TimeAgo(unixTimestamp) {
    const now = Math.floor(Date.now() / 1000);
    const diff = now - unixTimestamp;

    if (diff < 60) return diff <= 1 ? 'just now' : `${diff} seconds ago`;
    if (diff < 3600) { const m = Math.floor(diff / 60); return m === 1 ? '1 minute ago' : `${m} minutes ago`; }
    if (diff < 86400) { const h = Math.floor(diff / 3600); return h === 1 ? '1 hour ago' : `${h} hours ago`; }
    if (diff < 604800) { const d = Math.floor(diff / 86400); return d === 1 ? 'yesterday' : `${d} days ago`; }
    if (diff < 2592000) { const w = Math.floor(diff / 604800); return w === 1 ? '1 week ago' : `${w} weeks ago`; }
    if (diff < 31536000) { const mo = Math.floor(diff / 2592000); return mo === 1 ? '1 month ago' : `${mo} months ago`; }
    const y = Math.floor(diff / 31536000); return y === 1 ? '1 year ago' : `${y} years ago`;
}

export function InitPasswordViewers(){
	
	let PassFields=document.getElementsByClassName("Pass");
	
	[...PassFields].forEach(PassField=>{
		let Icon=PassField.getElementsByClassName("IconCont")[0].getElementsByTagName('img')[0];
		let input=PassField.getElementsByTagName("input")[0];
		
		Icon.addEventListener("click", ()=>{
			if(!Icon.classList.contains("shown")){
				input.type="text";
				Icon.src="Imgs/Icons/EyeOn.svg";
				
				Icon.classList.add("shown");
				
			}else{
				input.type="password";
				Icon.src="Imgs/Icons/EyeOff.svg";
				
				Icon.classList.remove("shown");
				
				
			}
		
		});
	});
	
}
InitPasswordViewers();
