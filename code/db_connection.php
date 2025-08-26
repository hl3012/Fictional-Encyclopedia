<?php
// db_connection.php
$db_username = ""; 
$db_password = ""; 
$db_connection_string = ""; 

$conn = oci_connect($db_username, $db_password, $db_connection_string);

if (!$conn) {
    $e = oci_error();
    die("Oracle Connection Error: " . htmlentities($e['message']));
}
?>
