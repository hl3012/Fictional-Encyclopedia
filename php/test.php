<?php
include 'db_connection.php';

echo "<h1>Connection test page</h1>";

// test Oracle connection
if ($conn) {
    echo "<p style='color: green;'> Oracle connected</p>";
    
    $stmt = oci_parse($conn, "SELECT * FROM USER_TABLES");
    if (oci_execute($stmt)) {
        echo "<p style='color: green;'> Oracle query executed</p>";
    }
} else {
    echo "<p style='color: red;'> Oracle connection failed</p>";
}

// test ElasticSearch
require_once 'es_service.php';
$esService = new ElasticSearchService();
if ($esService->client) {
    echo "<p style='color: green;'>ElasticSearch connected</p>";
} else {
    echo "<p style='color: orange;'> ElasticSearch not connected</p>";
}
?>