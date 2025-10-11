<?php 
$PATH="../../";
include $PATH.'Includes/UserValidation.php';  //include validation to get user data


function RowExists($Table,$Column,$Value){ //check if row exists

    global $pdo; // Use the global PDO instance


    // Ensure $Column and $Value are treated as arrays for consistent processing
    $Columns = is_array($Column) ? $Column : [$Column];
    $Values = is_array($Value) ? $Value : [$Value];

    if (count($Columns) !== count($Values)) {
        // You might want to throw an exception or return false with an error message
        // For simplicity here, we'll just return false.
        echo "RowExists: Mismatch between number of columns and values.";
        return false;
    }

    // Prepare the SQL query with placeholders for each column
    $conditions = [];
    $boundParams = [];

    for ($i = 0; $i < count($Columns); $i++) {
        $conditions[] = "`" . $Columns[$i] . "` = ?"; // Add backticks for column names for safety
        $boundParams[] = $Values[$i];
    }

    // Join conditions with 'AND'
    $whereClause = implode(' AND ', $conditions);

    //check if valid in DB by only retrieving one row
    $query = "SELECT 1 FROM `$Table` WHERE " . $whereClause . " LIMIT 1";
    $stmt = $pdo->prepare($query);
    $stmt->execute($boundParams);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return true; // Row exists
    } else {
        return false; // Row does not exist
    }
}




if($_SERVER['REQUEST_METHOD']==='POST'){

    $UID=$User['id'];

    if($_POST['ReqType']==1){ //update cover photo
    }
    if($_POST['ReqType']==2){ //update profile photo
    }
    if($_POST['ReqType']==3){ //update personal info
    }

}