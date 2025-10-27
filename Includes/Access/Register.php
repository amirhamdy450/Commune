<?php 
//check if path not set !
if(!isset($PATH)){
    die("Path not set");
}


include_once $PATH."Includes/DB.php";
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
            <form id="RegisterForm" novalidate>
                <h1>Create Your Account</h1>

                <!-- Progress Bar -->
                <div class="ProgressBar">
                    <div class="ProgressStep active" data-title="Personal"></div>
                    <div class="ProgressStep" data-title="Account"></div>
                    <div class="ProgressStep" data-title="Details"></div>
                </div>

                <!-- Step 1: Personal Info -->
                <div class="FormStep active">
                    <div class="TextField">
                        <input type="text" name="fname" placeholder="First Name">
                    </div>  
                    <div class="TextField">
                        <input type="text" name="lname" placeholder="Last Name">
                    </div>  
                    <div class="TextField">
                        <label for="bday">Birthday</label>
                        <input type="date" name="bday" id="bday" placeholder="Birthday">
                    </div>
                </div>

                <!-- Step 2: Account Details -->
                <div class="FormStep">
                    <div class="TextField">
                        <input type="text" name="email" placeholder="Email">
                    </div>  
                    <div class="TextField Pass">
                        <div class="IconCont">
                            <input type="password" name="pass" placeholder="Password">
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="Toggle Password Visibility">
                        </div>
                    </div>  
                    <div class="TextField Pass">
                        <div class="IconCont">
                            <input type="password" name="cpass" placeholder="Confirm Password">
                            <img src="<?php echo $PATH ?>Imgs/Icons/EyeOff.svg" alt="Toggle Password Visibility">
                        </div>
                    </div>  
                </div>

                <!-- Step 3: Additional Details -->
                <div class="FormStep">
                    <div class="TextField">
                         <label for="country">Country</label>
                        <select name="country" id="country">
                            <option value="">Select a country...</option>

                            <!-- Select Countries From Database -->

                            <?php
                            $sql = "SELECT Code, Name FROM countries ORDER BY Name ASC";

                            $stmt = $pdo->prepare($sql);
                            $stmt->execute();

                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $row['Code'] . '">' . $row['Name'] . '</option>';
                            }


                            ?>
<!--                             <option value="EG">Egypt</option>
                            <option value="US">United States</option>
                            <option value="GB">United Kingdom</option>
                            <option value="CA">Canada</option> -->
                        </select>
                    </div>
                    <div class="TextField">
                        <label>Gender</label>
                        <div class="RadioGroup">
                            <label><input type="radio" name="gender" value="0"> Male</label>
                            <label><input type="radio" name="gender" value="1"> Female</label>
                        </div>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="FormNavigation">
                    <button type="button" class="BrandBtn Dark hidden" id="BackBtn" >Back</button>
                    <button type="button" class="BrandBtn" id="NextBtn">Next</button>
                    <input type="submit" name="Register" value="Register" class="BrandBtn hidden" id="SubmitBtn" >
                </div>

                <p class="SecondaryAction">Already have an account? <a href="<?php echo $PATH ?>index.php">Login</a></p>
            </form>
        </div>
    </div>
    
    <script type="module" src="<?php echo $PATH ?>Scripts/Auth.js"></script>
</body>
</html>
