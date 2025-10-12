<html>
<?php
// Connect to Oracle database
include 'db_connection.php';

echo "<h2>Your Tables, Schema, and Data:</h2>";

// Get all table names for the current user
$sql_tables = "SELECT table_name FROM user_tables";
$stid_tables = oci_parse($conn, $sql_tables);
oci_execute($stid_tables);

$found = false;
while ($row_table = oci_fetch_array($stid_tables, OCI_ASSOC+OCI_RETURN_NULLS)) {
    $found = true;
    $table_name = $row_table['TABLE_NAME'];

    echo "<h3>Table: {$table_name}</h3>";

    // ===== Get schema =====
    $sql_schema = "SELECT column_name, data_type, data_length 
                   FROM user_tab_columns 
                   WHERE table_name = :tbl
                   ORDER BY column_id";
    $stid_schema = oci_parse($conn, $sql_schema);
    oci_bind_by_name($stid_schema, ":tbl", $table_name);
    oci_execute($stid_schema);

    echo "<strong>Schema:</strong><br>";
    echo "<ul>";
    while ($row_schema = oci_fetch_array($stid_schema, OCI_ASSOC+OCI_RETURN_NULLS)) {
        echo "<li>{$row_schema['COLUMN_NAME']} ({$row_schema['DATA_TYPE']}({$row_schema['DATA_LENGTH']}))</li>";
    }
    echo "</ul>";
    oci_free_statement($stid_schema);

    // ===== Get all rows from the table =====
    $sql_data = "SELECT * FROM {$table_name}";
    $stid_data = oci_parse($conn, $sql_data);
    oci_execute($stid_data);

    echo "<strong>Data:</strong><br>";
    echo "<table border='1' cellpadding='5'>";

    // Print table headers
    $ncols = oci_num_fields($stid_data);
    echo "<tr>";
    for ($i = 1; $i <= $ncols; $i++) {
        $colname = oci_field_name($stid_data, $i);
        echo "<th>{$colname}</th>";
    }
    echo "</tr>";

    // Print table data
    while ($row_data = oci_fetch_array($stid_data, OCI_ASSOC+OCI_RETURN_NULLS)) {
        echo "<tr>";
        foreach ($row_data as $item) {
            echo "<td>" . ($item !== null ? htmlspecialchars($item) : "&nbsp;") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table><br>";

    oci_free_statement($stid_data);
}

if (!$found) {
    echo "(No tables found)";
}

// Clean up
oci_free_statement($stid_tables);
oci_close($conn);
?>
</html>
