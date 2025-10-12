<?php
include 'db_connection.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? '';
    $userId = trim($_POST['userId'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // verify user
    if ($role && $userId && $password) {
        $sql = "SELECT * FROM USERS 
                WHERE USERID = :bv_uid 
                AND PASSWORD = :bv_pwd";

        $stid = oci_parse($conn, $sql);

        // check syntax
        if (!$stid) {
            $e = oci_error($conn);
            die("SQL Parse Error: " . htmlentities($e['message']));
        }

        oci_bind_by_name($stid, ":bv_uid", $userId);
        oci_bind_by_name($stid, ":bv_pwd", $password);

        // check execution
        if (!oci_execute($stid)) {
            $e = oci_error($stid);
            die("SQL Execute Error: " . htmlentities($e['message']));
        }

        $row = oci_fetch_assoc($stid);

        // check if there are any matched data
        if ($row) {
            session_regenerate_id(true);
            $_SESSION = [];
            $_SESSION['user'] = [
                'userid' => intval($row['USERID']),
                'username' => $row['NAME']
            ];

            // keep in localStorage
            echo "<script>
                localStorage.setItem('userid', " . json_encode($_SESSION['user']['userid']) . ");
                localStorage.setItem('username', " . json_encode($_SESSION['user']['username']) . ");
                window.location.href = 'welcome.php';
            </script>";
            exit;
        } else {
            echo "<p style='color:red; text-align:center;'>Invalid userId or password.</p>";
        }

    } else {
        echo "<p style='color:red; text-align:center;'>Please fill all fields.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: linear-gradient(135deg, #a8edea, #fed6e3);
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .login-container {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        width: 350px;
    }
    h2 {
        text-align: center;
        color: #333;
    }
    label {
        font-weight: bold;
        display: block;
        margin-top: 15px;
    }
    select, input {
        width: 100%;
        padding: 10px;
        margin-top: 8px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }
    button {
        margin-top: 20px;
        width: 100%;
        padding: 12px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
    }
    button:hover {
        background: #45a049;
    }
</style>
</head>
<body>
<div class="login-container">
    <h2>Login</h2>
    <form method="POST">
        <label for="role">Select Role</label>
        <select id="role" name="role" required>
            <option value="">-- Select --</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <label for="userId">User ID</label>
        <input type="number" id="userId" name="userId" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
