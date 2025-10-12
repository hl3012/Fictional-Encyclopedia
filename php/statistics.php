<?php
    /*image path need to change*/
    session_start();
    include 'db_connection.php';

    if (!isset($_SESSION['user'])) {
        header("Location: ../php/login.php");
        exit;
    }

 
     //1. DIVISION query: show entry commented by all users
     if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_entry_commented_by_all_users'])) {
        //DIVISION query
        //return false if there is a user with no comment on the eid, remove entry
        $sql__commented_by_all_users = "SELECT e.eid, e.name, e.content FROM Entry e 
        WHERE NOT EXISTS (SELECT * FROM Users u
        WHERE NOT EXISTS (SELECT * FROM Comments c WHERE c.eid = e.eid AND c.userId = u.userId))";

        $stid_entry = oci_parse($conn, $sql__commented_by_all_users);
        oci_execute($stid_entry);

        $entry = [];
        // each row from the query
        while ($each = oci_fetch_assoc($stid_entry)) {
            $entry[] = $each;
        }
        
        if (empty($entry)) {
            echo "Invalid: No Such Entry";
            oci_free_statement($stid_entry);
            exit;
        }
        //free rescource
        oci_free_statement($stid_entry);
    }
    
    // 2. Category Above Average: Find categories that contain more entries than the average number of entries per category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_category_above_avg'])) {
        $sql_above_avg = "SELECT i.caID, c.name, COUNT(*) 
                          FROM IsIn i, Categories c 
                          WHERE i.caID = c.caID 
                          GROUP BY i.caID, c.name 
                          HAVING COUNT(*) > (SELECT AVG(cnt) 
                                             FROM (SELECT COUNT(*) cnt 
                                                   FROM IsIn 
                                                   GROUP BY caID))";
        $stid_cat = oci_parse($conn, $sql_above_avg);
        oci_execute($stid_cat);
    
        $above_avg_categories = [];
        while ($row = oci_fetch_assoc($stid_cat)) {
            $above_avg_categories[] = $row;
        }
        oci_free_statement($stid_cat);
    }

    // 3. filter greater than or equals to n
    //    3.1 get input integer
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show_ge_n_categories'])) {
        $n = intval($_POST['n']); // 3.2 make it INT value

        // 3.3 FROM -> JOIN -> GROUP BY -> HAVING -> SELECT
        $sql = "SELECT i.caID, c.name, COUNT(*) AS ENTRY_COUNT
                FROM IsIn i
                JOIN Categories c ON i.caID = c.caID
                GROUP BY i.caID, c.name
                HAVING COUNT(*) >= :n";

        $stid = oci_parse($conn, $sql);
        oci_bind_by_name($stid, ":n", $n);
        oci_execute($stid);

        $ge_n_categories = [];
        while ($row = oci_fetch_assoc($stid)) {
            $ge_n_categories[] = $row;
        }
        oci_free_statement($stid);

    }

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Statistics</title>
    </head>
    
    <style>
        /* Centered content box */
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(87, 81, 81, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
        }
        .title {
            font-size: 30px;
            font-weight: bold;
        }
        /* Button styles */
        button {
            padding: 8px 16px;
            font-size: 14px;
            border: none;
            border-radius: 6px;
            background-color: #007bff;
            color: white;
            cursor: pointer;
            margin-left: 10px;
        }

        .button-header button {
            font-size: 16px
        }

        .button-part {
            margin-top: 050px;
            text-align: center;
        }

        .button-part form button{
            width: 100%;
            font-size: 26px
        }

    </style>
    <body>
        <div class="container">
            <!-- Header section with title and buttons -->
            <div class="header">
                <div class="title">
                    <strong>STATISTICS</strong>
                <div>
                <div class="button-header">
                    <button onclick="location.href='../welcome.php'">Return to Main</button>
                </div>
            </div>
            
            <!-- DIVISION button show entries commented by all users-->
            <div class="button-part">
                <form method = "POST">
                    <button type="submit" name="show_entry_commented_by_all_users">Show entries commented by all users</button>
                </form>
            </div>
            <?php if (!empty($entry)):?>
                <?php foreach($entry as $each):?>
                    <div>
                        <strong>ID: </strong><?=$each['EID']?><br>
                        <strong>Name: </strong><?=htmlspecialchars($each['NAME'])?><br>
                        <strong>Content: </strong><?=htmlspecialchars($each['CONTENT'])?><br>
                    </div>
                <?php endforeach;?>
            <?php endif;?>

            <!-- Average Button: show categories with above average number of entries -->
            <div class="button-part">
                <form method="POST">
                    <button type="submit" name="show_category_above_avg">
                        Show categories with above average number of entries
                    </button>
                </form>
            </div>
        
            <!-- Result of categories with above average entries -->
            <?php if (!empty($above_avg_categories)): ?>
                <h3>Categories with Above Average Entries:</h3>
                <?php foreach ($above_avg_categories as $cat): ?>
                    <div>
                        <strong>Category ID:</strong> <?= $cat['CAID'] ?><br>
                        <strong>Category Name:</strong> <?= htmlspecialchars($cat['NAME']) ?><br>
                        <strong>Entry Count:</strong> <?= $cat['COUNT(*)'] ?><br>
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>  



            <!-- Filter categories >= n -->
            <div class="button-part">
                <form method="POST">
                    <label for="n">Show categories with entries >= </label>
                    <input type="number" name="n" id="n" min="0" required>
                    <button type="submit" name="show_ge_n_categories">
                        Show Categories
                    </button>
                </form>
            </div>

            <!-- Result -->
            <?php if (!empty($ge_n_categories)): ?>
                <h3>Categories with Entries >= <?= htmlspecialchars($n) ?>:</h3>
                <?php foreach ($ge_n_categories as $cat): ?>
                    <div>
                        <strong>Category ID:</strong> <?= $cat['CAID'] ?><br>
                        <strong>Category Name:</strong> <?= htmlspecialchars($cat['NAME']) ?><br>
                        <strong>Entry Count:</strong> <?= $cat['ENTRY_COUNT'] ?><br>
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </body>
</html>

