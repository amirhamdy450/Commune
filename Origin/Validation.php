<?php


function validateEmail($Email): bool
{

    if (filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        return true;
    }
    
    return false;
}


function validatePassword(string $password): bool
{
    // 1. Check if password is at least 8 characters long.
    if (strlen($password) < 8) {
        return false;
    }

    // 2. Check if it contains at least one uppercase letter.
    if (!preg_match('/[A-Z]/', $password)) {
        return false;
    }

    // 3. Check if it contains at least one lowercase letter.
    if (!preg_match('/[a-z]/', $password)) {
        return false;
    }

    // 4. Check if it contains at least one number.
    if (!preg_match('/\\d/', $password)) {
        return false;
    }

    // If all checks pass, the password is valid.
    return true;
}

function validateDate(string $date): bool
{
    // Create a DateTime object from the specified format.
    $d = DateTime::createFromFormat('Y-m-d', $date);


    return $d && $d->format('Y-m-d') === $date;
}


function validateName(string $name): bool
{
    $name = trim($name);
    
    // Check if the name is empty after trimming.
    if (empty($name)) {
        return false;
    }
    

    if (!preg_match('/^[a-zA-Z\\s]+$/', $name)) {
        return false;
    }
    
    return true;
}




function validateBoolean($value): bool
{

    if ($value === '' || $value === null) {
        return false;
    }
    
    
    if ($value === 0 || $value === 1 || $value === '0' || $value === '1' || $value === true || $value === false) {
        return true;
    }
    
    return false;
}






function validateBirthYear($Date){

    //first validate date with validateDate function
    if(!validateDate($Date)){
        return false;
    }

    $year = date('Y', strtotime($Date));
    $currentYear = date('Y');

    if($year < 1900 || $year > $currentYear){
        return false;
    }



    return true;


}




function validateCountryCode($code): bool
{
    $code = trim($code);
    
    // Check if the code is empty after trimming.
    if (empty($code)) {
        return false;
    }

    if (preg_match('/^[A-Z]{2}$/', $code)) {
        return true;
    }
    
    return false;
}


function ValidateCsrf() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit();
    }
}


?>