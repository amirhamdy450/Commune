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
    <link rel="preload" href="<?php echo $PATH ?>Imgs/Imgs/EyeOn.svg" as="image">


    <title>Login</title>
</head>
<body class="Login">
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



        <div class="AuthBox Login ">
            <form  id="LoginForm">
                
                
            <h1>Login</h1>


                <div class="TextField">
                    <input type="text" name="email" placeholder="Email" >

                </div>  
                <div class="TextField Pass">
                    <div class="IconCont">
                            <input type="password" placeholder="password" name="pr_pass" id="protected_pass" >
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="">
                    </div>


                </div>  



               <a href="index.php?redirect=forgot-password" class="FormLink">Forgot your password?</a>
                <input type="submit" name="Login" value="Login" class="BrandBtn">


                <p class="SecondaryAction">Don't have an account ? <a href="<?php echo $PATH ?>index.php?redirect=reg">Register</a></p>
            </form>
        </div>

    </div>

<script  type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>

</body>
</html>