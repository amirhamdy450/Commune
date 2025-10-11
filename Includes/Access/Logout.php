<?php
// Expire the cookies by setting their expiration time to the past, effectively logging the user out.
setcookie("user_token", "", time() - 3600, "/", "localhost", false, true);
setcookie("user_token2", "", time() - 3600, "/", "localhost", false, true);

// Redirect the user back to the login page.
header("Location: index.php");
exit();
?>
