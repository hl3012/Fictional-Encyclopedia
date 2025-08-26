<?php
    session_start();
    
    require 'db_connection.php';

    // if there is no 'user' in the session, redirect to login.php
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }

    // store isAdmin in session
    $_SESSION['isAdmin'] = ($_SESSION['user']['userid'] == 1);

    $isAdmin = $_SESSION['isAdmin'];
    $userId = $_SESSION['user']['userid'];
    $username = htmlspecialchars($_SESSION['user']['username']);
    $identity = $isAdmin ? "Admin" : "User";


    // --- Delete Entry (admin only) ---
    if ($isAdmin && isset($_GET['delete_entry'])) {
        $eid = intval($_GET['delete_entry']);
        // will trigger CASCADE in ContributeTo and IsIn
        $delStmt = oci_parse($conn, "DELETE FROM Entry WHERE eid = :eid");
        // treat as input, rather than concatenating directly
        oci_bind_by_name($delStmt, ":eid", $eid);
        oci_execute($delStmt);
    }


    // --- Delete All Entries in a Category (admin only) ---
    if ($isAdmin && isset($_GET['delete_category'])) {
        $caid = intval($_GET['delete_category']);

        // for a specific caID
        $eidStmt = oci_parse($conn, "SELECT eid FROM IsIn WHERE caID = :caid");
        oci_bind_by_name($eidStmt, ":caid", $caid);
        oci_execute($eidStmt);

        // delete every fetched row
        while ($row = oci_fetch_assoc($eidStmt)) {
            $eid = intval($row['EID']);
            $delStmt = oci_parse($conn, "DELETE FROM Entry WHERE eid = :eid");
            oci_bind_by_name($delStmt, ":eid", $eid);
            oci_execute($delStmt);
        }
    }


    // --- Get all categories ---
    $catStmt = oci_parse($conn, "SELECT caID, name FROM Categories ORDER BY name");
    oci_execute($catStmt);
    $categories = [];
    while ($row = oci_fetch_assoc($catStmt)) {
        $categories[] = $row;
    }

    // --- Get selected categories ---
    $selectedCats = $_GET['categories'] ?? [];
    $entries = [];

    if (!empty($selectedCats)) {
        $placeholders = [];
        foreach ($selectedCats as $i => $catId) {
            $placeholders[] = ":cat" . $i;
        }
        $inClause = implode(',', $placeholders);

        // FROM -> JOIN -> WHERE -> ORDER -> SELECT
        $sql = "SELECT DISTINCT e.eid, e.name
                FROM Entry e
                JOIN IsIn i ON e.eid = i.eid
                WHERE i.caID IN ($inClause)
                ORDER BY e.name";

        $entryStmt = oci_parse($conn, $sql);
        foreach ($selectedCats as $i => $catId) {
            $catId = intval($catId);
            oci_bind_by_name($entryStmt, ":cat" . $i, $catId);
        }
        oci_execute($entryStmt);
    } else {
        $entryStmt = oci_parse($conn, "SELECT eid, name FROM Entry ORDER BY name");
        oci_execute($entryStmt);
    }

    while ($row = oci_fetch_assoc($entryStmt)) {
        $entries[] = $row;
    }


    // --- Add Entry ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_entry'])) {
        $name = trim($_POST['name']);
        $content = trim($_POST['content']);
        $image_url = trim($_POST['image_url']);
        $selectedCatsAdd = $_POST['new_categories'] ?? [];

        if (!empty($name) && !empty($content) && !empty($selectedCatsAdd)) {
            // 1. get new unique EID
            //    NVL(val1, val2), if not null, return val1, return val2 otherwise
            $sql_new_eid = "SELECT NVL(MAX(eid), 0) + 1 AS EID FROM Entry";
            $stid_new_eid = oci_parse($conn, $sql_new_eid);
            oci_execute($stid_new_eid);
            $row_new_eid = oci_fetch_array($stid_new_eid, OCI_ASSOC);
            $newEid = $row_new_eid['EID'];

            // id from session (default 1 if it is not set)
            $userId = $_SESSION['userId'] ?? 1;

            // 2. insert Entry
            $insertEntry = oci_parse($conn, 
                "INSERT INTO Entry (eid, name, content, image_url) 
                VALUES (:eid_val, :name_val, :content_val, :img_val)");
            oci_bind_by_name($insertEntry, ":eid_val", $newEid);
            oci_bind_by_name($insertEntry, ":name_val", $name);
            oci_bind_by_name($insertEntry, ":content_val", $content);
            oci_bind_by_name($insertEntry, ":img_val", $image_url);
            oci_execute($insertEntry);

            // 3. insert IsIn (iterative)
            foreach ($selectedCatsAdd as $catId) {
                $catId = intval($catId);
                $insertIsIn = oci_parse($conn, 
                    "INSERT INTO IsIn (caID, eid) VALUES (:caid_val, :eid_val)");
                oci_bind_by_name($insertIsIn, ":caid_val", $catId);
                oci_bind_by_name($insertIsIn, ":eid_val", $newEid);
                oci_execute($insertIsIn);
            }

            // 4. insert ContributesTo
            $insertContrib = oci_parse($conn, 
                "INSERT INTO ContributesTo (userId, eid, conDate) 
                VALUES (:u_val, :e_val, SYSDATE)");
            oci_bind_by_name($insertContrib, ":u_val", $userId);
            oci_bind_by_name($insertContrib, ":e_val", $newEid);
            oci_execute($insertContrib);

            oci_commit($conn);
            // redirect to itself. refresh
            header("Location: " . $_SERVER['PHP_SELF']);
        } else {
            echo "<pre>Validation failed - name/content/categories missing</pre>";
        }
    }

?>

<!DOCTYPE html>
<html>
<head>
<title>Welcome</title>
<style>
    
    body {
        background: linear-gradient(135deg, #fff8f0, #fcefe6);
        font-family: 'Segoe UI', Arial, sans-serif;
        padding: 20px;
        color: #4a3f35;
        margin: 0;
    }

    
    h2 {
        text-align: center;
        color: #8b4513;
        margin-bottom: 30px;
    }
    form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
        justify-content: center;
    }
    label {
        background-color: #fff;
        padding: 6px 12px;
        border-radius: 20px;
        border: 1px solid #d9b99b;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        cursor: pointer;
        transition: all 0.2s ease-in-out;
    }
    label:hover {
        background-color: #fbe9d0;
    }
    button {
        padding: 6px 14px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        font-size: 14px;
        transition: all 0.2s ease-in-out;
    }
    .btn-filter {
        background-color: #d97742;
        color: white;
    }
    .btn-filter:hover {
        background-color: #c46834;
    }
    .btn-del {
        background-color: #d9534f;
        color: white;
        margin-left: auto;
    }
    .btn-del:hover {
        background-color: #c9302c;
    }
    .entry {
        display: flex;
        align-items: center;
        background-color: #fff;
        padding: 8px 12px;
        border-radius: 8px;
        margin: 5px 0;
        box-shadow: 0px 2px 5px rgba(0,0,0,0.05);
        transition: transform 0.1s ease-in-out;
    }
    .entry:hover {
        transform: translateY(-2px);
    }
    .square {
        width: 20px;
        height: 20px;
        background-color: #d97742;
        margin-right: 10px;
        border-radius: 4px;
    }
</style>
</head>
<body>

<h2>Welcome, <?= $username ?> (<?= $identity ?>)</h2>

<!-- Category filter form -->
<form method="GET">
    <?php foreach ($categories as $cat): ?>
        <label>
            <input type="checkbox" name="categories[]" value="<?= $cat['CAID'] ?>"
                <?= in_array($cat['CAID'], $selectedCats) ? 'checked' : '' ?>>
            <?= htmlspecialchars($cat['NAME']) ?>
        </label>
        <?php if ($isAdmin): ?>
            <a href="?delete_category=<?= $cat['CAID'] ?>"
               onclick="return confirm('Delete ALL entries in this category?')">
               <button type="button" class="btn-del">delete all</button>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
    <button type="submit" class="btn-filter">Filter</button>
</form>


<!-- Add Entry Form -->
<h3>Add Entry</h3>
<form method="POST" action="" class="add-entry-form">
    <div class="form-group-inline">
        <label>Name:</label>
        <input type="text" name="name" required>
        
        <label>Image URL:</label>
        <input type="text" name="image_url" placeholder="entry_image/xxx.png">
    </div>

    <div class="form-group">
        <label>Content:</label>
        <textarea name="content" rows="3" required></textarea>
    </div>

    <div class="form-group">
        <label>Categories:</label>
        <div class="category-options">
            <?php foreach ($categories as $cat): ?>
                <label class="cat-label">
                    <input type="checkbox" name="new_categories[]" value="<?= $cat['CAID'] ?>">
                    <?= htmlspecialchars($cat['NAME']) ?>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="form-group center">
        <button type="submit" name="add_entry" class="btn-add">Add Entry</button>
    </div>
</form>

<style>
.add-entry-form {
    background-color: #fff8f0;
    padding: 15px;
    border-radius: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 12px;
}

.form-group-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
}

.form-group-inline input[type="text"] {
    flex: 1;
    padding: 5px;
}

textarea {
    width: 100%;
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.category-options {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 6px;
}

.cat-label {
    background-color: #fff;
    padding: 5px 12px;
    border: 1px solid #d9b99b;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}

.cat-label:hover {
    background-color: #fbe9d0;
}

.center {
    text-align: center;
}

.btn-add {
    background-color: #d97742;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.2s ease-in-out;
}

.btn-add:hover {
    background-color: #c46834;
}
</style>



<!-- Entries -->
<h3>Entries</h3>
<?php foreach ($entries as $entry): ?>
    <div class="entry">
        <div class="square"></div>
        <a href="detail.php?eid=<?= $entry['EID'] ?>" style="text-decoration:none; color:inherit;">
            <?= htmlspecialchars($entry['NAME']) ?>
        </a>
        <?php if ($isAdmin): ?>
            <a href="?delete_entry=<?= $entry['EID'] ?>" onclick="return confirm('Delete this entry?')">
                <button class="btn-del">delete</button>
            </a>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


<!-- Statistics tab and home tab -->
<style>
.nav-tabs {
    display: flex;
    justify-content: center;
    margin-bottom: 25px;
    gap: 8px;
}

.nav-tabs a {
    padding: 10px 20px;
    border-radius: 25px;
    background-color: #fff;
    border: 1px solid #d9b99b;
    color: #4a3f35;
    font-weight: 500;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s ease-in-out;
}

.nav-tabs a:hover {
    background-color: #fbe9d0;
    transform: translateY(-2px);
}

.nav-tabs a.active {
    background-color: #d97742;
    color: white;
    border-color: #d97742;
    font-weight: bold;
}
</style>

<div class="nav-tabs">
    <a href="login.php" class="active">Home</a>
    <a href="statistics.php">Statistics</a>
</div>


</body>
</html>

