
<?php 

//check if path not set !
if(!isset($PATH)){
    die("Path not set");
}

    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Global.css">
    <link rel="stylesheet" href="<?php echo $PATH ?>Styles/Auth.css">

    <title>Register</title>
</head>
<body class="Register">
    <div class="AuthContainer">


        <div class="BrandPanel">
            <div class="HeroContainer">
                <div class="Logo" href="index.php">
                    <img src="<?php echo $PATH ?>Imgs/Logo/logo.svg" alt="Logo">
                    OMMUNE
                </div>

                <p>The place where you can truly connect with people all over the world and share your thoughts </p>
            </div>

        </div>



        <div class="AuthBox Reg">
            <form  id="RegisterForm">
                
                
            <h1>Register</h1>

                <div class="TextField">
                    <input type="text" name="fname" placeholder="First Name"   >

                </div>  
                <div class="TextField">
                    <input type="text" name="lname" placeholder="Last Name" >

                </div>  
                
                <div class="TextField">
                    <input type="date" name="bday" placeholder="Birthday"  >

                </div>

                <div class="TextField">
                    <input type="text" name="email" placeholder="Email"  >

                </div>  
                <div class="TextField Pass">
                    <div class="IconCont">
                            <input type="password" name="pass" placeholder="password" >
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="">
                    </div>


                </div>  
                <div class="TextField Pass">
                    <div class="IconCont">

                        <input type="password" name="cpass" placeholder="Confirm Password"  >
                        <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="">
                    </div>


                </div>  

                <input type="submit" name="Register" value="Register" class="BrandBtn">

                <p class="SecondaryAction">Already have an account? <a href="<?php echo $PATH ?>index.php">Login</a></p>
            </form>
        </div>





    </div>

    
    <script  type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>