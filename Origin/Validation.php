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

?>