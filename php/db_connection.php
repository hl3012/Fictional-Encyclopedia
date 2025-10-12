<?php
// db_connection.php
$db_host = 'oracle';   
$db_port = '1521';      
$db_sid = 'XE';
$db_user = 'system';
$db_pass = 'Oracle123';



$conn = oci_connect($db_user, $db_pass, $db_host . ':' . $db_port . '/' . $db_sid);

if (!$conn) {
    $e = oci_error();
    error_log("Oracle connection failed: " . $e['message']);
}

?>
