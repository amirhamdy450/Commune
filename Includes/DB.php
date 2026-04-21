<?php
require_once 'Config.php';
date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$DBservername = DB_Servername;
$DBusername = DB_Username;
$DBpassword = DB_Password;
$DBName = DB_Name;

try {
  $pdo = new PDO(
    "mysql:host=$DBservername;dbname=$DBName;charset=utf8mb4",
    $DBusername,
    $DBpassword,
    array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4")
  );
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  //echo "Connected successfully";
} catch(PDOException $e) {
  
  echo "Connection failed: " . $e->getMessage();
}


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




?>
