<?php
    /*resume current session if there is the user key
    connect database*/
    session_start();
    include 'db_connection.php';

    if (!isset($_SESSION['user'])) {
        header("Location: ../php/login.php");
        exit;
    }

    /*SELECT: select entry per the eid (prevent SQL injection)*/
    // get eid passed by url;if no eid return 0
     if(isset($_GET['eid'])) {
        $eid = intval($_GET['eid']);
    } else {
        $eid = 0;
        echo "Invalid: No Such Entry";
        exit;
    }

    /*PROJECTION: show image or not*/
    // whether show the image passed by url
    $show_image = false;
    if(isset($_GET['show_image'])) {
        if (intval($_GET['show_image'])===1) {
            $show_image = true;
        }
    }

    //PROJECTION SQL query 
    $sql_entry = $show_image? "SELECT * FROM Entry WHERE eid = :eid":
                "SELECT eid, name, content FROM Entry WHERE eid = :eid";

    $stid = oci_parse($conn, $sql_entry);
    oci_bind_by_name($stid, ':eid', $eid);
    oci_execute($stid);
    // return next row from the query
    $entry = oci_fetch_assoc($stid); 
    if ($entry == FALSE) {
        echo "Invalid: No Such Entry";
        oci_free_statement($stid);
        exit;
    }
    //free rescource
    oci_free_statement($stid);

    /*SELECT comments and username per the eid (prevent SQL injection)*/
    $sql_comment = "SELECT c.coID, c.content, c.commentdate, u.name, c.replyTo
     FROM Comments c, Users u 
     WHERE c.eid = :eid AND c.userId = u.userId";

    $stid_comment = oci_parse($conn, $sql_comment);
    oci_bind_by_name($stid_comment, ':eid', $eid);
    oci_execute($stid_comment);
    //collect each row
    $allComments = [];
    while ($each = oci_fetch_assoc($stid_comment)) {
        $allComments[] = $each;
    }
    //free rescource
    oci_free_statement($stid_comment);

    /*SELECT entry contributor query*/
    $sql_contributor = "SELECT  DISTINCT u.name
     FROM ContributesTo c, Users u 
     WHERE c.eid = :eid AND c.userId = u.userId";
    
    $stid_contributor = oci_parse($conn, $sql_contributor);
    oci_bind_by_name($stid_contributor, ':eid', $eid);
    oci_execute($stid_contributor);
    //collect row
    $contributor = oci_fetch_assoc($stid_contributor);
    //free rescource
    oci_free_statement($stid_contributor);

    /*SELECT entry category query*/
    $sql_category = "SELECT DISTINCT c.name
    FROM IsIn i, Categories c 
    WHERE i.eid = :eid AND i.caID = c.caID";
    
    $stid_category = oci_parse($conn, $sql_category);
    oci_bind_by_name($stid_category, ':eid', $eid);
    oci_execute($stid_category);
    //collect row
    $category = oci_fetch_assoc($stid_category);
    //free rescource
    oci_free_statement($stid_category);
    
    /*UPDATE entry non-primary attributes, name is UNIQUE*/
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
        //variables passed
        $updated_name = $_POST['name'];
        $updated_content = $_POST['content'];
    
        //UPDATE SQL query
        $sql_update = "UPDATE Entry SET name = :name, content = :content WHERE eid = :eid";

        $stid_update = oci_parse($conn, $sql_update);
        oci_bind_by_name($stid_update, ':name', $updated_name);
        oci_bind_by_name($stid_update, ':content', $updated_content);
        oci_bind_by_name($stid_update, ':eid', $eid);
        oci_execute($stid_update);
        
        header("Location: detail.php?eid=$eid");
        oci_free_statement($stid_update);
        exit;                     
    }  
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Entry Detail</title>

  <!-- Load Inter font from Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet" />

  <style>
    /* Page body styling */
    body {
      font-family: 'Inter', sans-serif;
      background-color:rgb(140, 138, 138);
      color: #333;
      margin: 0;
      padding: 50px;
    }

    /* Centered content box */
    .container {
      max-width: 800px;
      margin: auto;
      background: white;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(87, 81, 81, 0.1);
    }

    /* Header layout with title and buttons */
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
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

    button:hover {
      background-color: #0056b3;
    }

    /* Image styling - hidden by default */
    #entry-image {
      width: 100%;
      max-width: 400px;
      height: auto;
      margin: 20px auto;
      border-radius: 10px;
    }

    /* Edit popup modal */
    .popup {
      display: none;
      position: fixed;
      top: 20%;
      left: 50%;
      transform: translateX(-50%);
      background: white;
      padding: 20px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      z-index: 100;
    }

    .popup.show {
      display: block;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: bold;
    }

    input, textarea {
      width: 100%;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 6px;
      margin-bottom: 16px;
    }

    /* Comment list styling */
    #comment-list {
      background-color:rgb(251, 245, 245);
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 10px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 20%);
      height: auto;
    }
  </style>
</head>

<body>
  <div class="container">
    <!-- Header section with entry title and buttons -->
    <div class="header">
      <h1 id="entry-name"><?=htmlspecialchars($entry['NAME'])?></h1>
      <div>
        <button onclick="showEditPopup()">Edit</button>
        <button onclick="location.href='../welcome.php'">Return to Main</button>
      </div>
    </div>

    <!-- Entry details section -->
    <p><strong>Entry ID:</strong> <span id="entry-id"><?=$entry['EID']?></span></p>
    <p><strong>Category:</strong> <span id="entry-category"><?=htmlspecialchars($category['NAME'])?></span></p>
    <p><strong>Content:</strong> <span id="entry-content"><?=htmlspecialchars($entry['CONTENT'])?></span></p>
    <p><strong>Contributors:</strong> <span id="entry-contributors"><?=htmlspecialchars($contributor['NAME'])?></span></p>

    <!-- Button to load and display image -->
    <button onclick="expandImage()">Show Image</button>
    <button onclick="closeImage()">Hide Image</button>
    <?php if($show_image):?>
        <?php if(!empty($entry['IMAGE_URL'])):?>
            <img id="entry-image" src="../code/entry_image/<?=htmlspecialchars($entry['IMAGE_URL'])?>" alt="entry image">
        <?php else:?> 
            <small><em>No image.</em></small><br>
        <?php endif;?> 
    <?php endif;?> 

    <!-- Comments section -->
    <h2>Comments</h2>
    <div id="comment-list">
        <?php foreach($allComments as $c):?>
            <small>coID:</small><?=$c['COID']?>
            <strong>&nbsp;&nbsp;&nbsp;&nbsp;<?=htmlspecialchars($c['CONTENT'])?></strong>
            <small>&nbsp;&nbsp;&nbsp;&nbsp;<?=($c['COMMENTDATE'])?></small>
            <small>&nbsp;&nbsp;&nbsp;&nbsp;<?=htmlspecialchars($c['NAME'])?></small><br>
            <?php if($c['REPLYTO']):?>
                <small>&nbsp;&nbsp;&nbsp;<em>ReplytoCoID:<?=htmlspecialchars($c['REPLYTO'])?></em></small><br>
            <?php endif;?> 
        <?php endforeach;?>
        <?php if(count($allComments)===0):?>
                <small><em>No comments now.</em></small><br>
        <?php endif;?> 
    </div>

    <!-- Edit popup form -->
    <div id="edit-popup" class="popup">
      <h3>Edit Entry</h3>
      <form method="POST" onsubmit="return submitEdit()"> 
        <input type="hidden" name="update" value ="1">     
        <label>Name: 
            <input id="edit-name" name = "name" value="<?=htmlspecialchars($entry['NAME'])?>"/>
        </label><br><br>
        <label>Content: 
            <textarea id="edit-content" name = "content" rows="4" cols="50"><?=htmlspecialchars($entry['CONTENT'])?></textarea>
        </label><br><br>
        <button type="submit">Submit</button>
        <button type="button" onclick="hideEditPopup()">Cancel</button>
      </form>
    </div>
  </div>

  <script>
    // When user clicks "Show Image", reload page with show_image=1 in URL
    function expandImage() {
      const url = new URL(window.location.href);
      url.searchParams.set("show_image", "1");
      window.location.href = url.toString();
    }

    // When user clicks "Show Image", reload page with show_image=0 in URL
    function closeImage() {
      const url = new URL(window.location.href);
      url.searchParams.set("show_image", "0");
      window.location.href = url.toString();
    }

    // Show the edit popup with current values pre-filled
    function showEditPopup() {
      document.getElementById("edit-popup").classList.add("show");
    }

    // Hide the edit popup
    function hideEditPopup() {
      document.getElementById("edit-popup").classList.remove("show");
    }

    // Send edit request to backend
    function submitEdit() {
      const name = document.getElementById("edit-name").value.trim();
      const content = document.getElementById("edit-content").value.trim();
      if (!name || !content) {
        alert("Both are needed");
        return false;
      }
      return true;
    }
  </script>
</body>
</html>