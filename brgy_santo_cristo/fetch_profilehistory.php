<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'db_connect.php';

// Check user session
if (empty($_SESSION['user_id'])) {
    echo "<tr><td colspan='4'>No user session found. Please log in.</td></tr>";
    return;
}

$user_id = $_SESSION['user_id'];

/**
 * Handle cancellation requests
 * Only process if this script is accessed directly via POST
 * (Not when included in profilepage.php)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {

    if (!isset($_POST['id'], $_POST['type'])) {
        header("Location: structure/profilepage.php?error=Invalid+request");
        exit;
    }

    $id = intval($_POST['id']);
    $type = $_POST['type'];

    // Check status
    if ($type === 'business') {
        $stmt = $conn->prepare("SELECT status FROM business_request WHERE form_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT status FROM clearance_request WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['flash_error'] = "Request not found.";
        header("Location: structure/profilepage.php");
        exit;
    }
    
    $row = $result->fetch_assoc();
    if ($row['status'] === 'For Signature') {
        $_SESSION['flash_error'] = "Cannot cancel request marked For Signature.";
        header("Location: structure/profilepage.php");
        exit;
    }
    
    // Delete request
    if ($type === 'business') {
        $delete_stmt = $conn->prepare("DELETE FROM business_request WHERE form_id = ? AND user_id = ?");
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM clearance_request WHERE id = ? AND user_id = ?");
    }
    
    $delete_stmt->bind_param("ii", $id, $user_id);
    $delete_stmt->execute();
    
    $_SESSION['flash_success'] = "Request canceled successfully";
    header("Location: structure/profilepage.php");
    exit;

    // Delete request
    if ($type === 'business') {
        $delete_stmt = $conn->prepare("DELETE FROM business_request WHERE form_id = ? AND user_id = ?");
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM clearance_request WHERE id = ? AND user_id = ?");
    }

    $delete_stmt->bind_param("ii", $id, $user_id);
    $delete_stmt->execute();

    header("Location: structure/profilepage.php?success=Request+canceled+successfully");
    exit;
}

/**
 * Otherwise: Display history table rows
 */

// -------- Fetch Business Requests --------
$business_sql = "SELECT form_id, application_type, date_submitted, status 
                 FROM business_request 
                 WHERE user_id = ? 
                 ORDER BY date_submitted DESC";
$business_stmt = $conn->prepare($business_sql);
$business_stmt->bind_param("i", $user_id);
$business_stmt->execute();
$business_result = $business_stmt->get_result();

while ($row = $business_result->fetch_assoc()) {
    $type = htmlspecialchars($row['application_type']);
    $status = htmlspecialchars($row['status']);
    $date = date("F j, Y", strtotime($row['date_submitted']));
    $form_id = $row['form_id'];

    echo "<tr>";
    echo "<td>{$type} (Business)</td>";
    echo "<td><a href='previewbusiness.php?form_id={$form_id}&type=business' target='_blank'>Preview</a></td>";
    echo "<td>{$date}</td>";
    echo "<td>";

    // Show cancel button if not "For Signature"
    if ($status === 'For Signature' || $status === 'Approved' || $status === 'Rejected') {
        echo $status;
    } else {
        echo "
            <form method='POST' action='../fetch_profilehistory.php' style='display:inline'>
                <input type='hidden' name='id' value='{$form_id}'>
                <input type='hidden' name='type' value='business'>
                <button type='submit' class='cancel-btn'>Cancel</button>
            </form>
        ";
    }
    

    echo "</td>";
    echo "</tr>";
}
$business_stmt->close();

// -------- Fetch Individual Clearance Requests --------
$clearance_sql = "SELECT id, purpose, date_submitted, status 
                  FROM clearance_request 
                  WHERE user_id = ? 
                  ORDER BY date_submitted DESC";
$clearance_stmt = $conn->prepare($clearance_sql);
$clearance_stmt->bind_param("i", $user_id);
$clearance_stmt->execute();
$clearance_result = $clearance_stmt->get_result();

while ($row = $clearance_result->fetch_assoc()) {
    $type = htmlspecialchars($row['purpose']);
    $status = htmlspecialchars($row['status']);
    $date = date("F j, Y", strtotime($row['date_submitted']));
    $id = $row['id'];

    echo "<tr>";
    echo "<td>{$type}</td>";
    echo "<td><a href='previewpage.php?id={$id}&type=clearance' target='_blank'>Preview</a></td>";
    echo "<td>{$date}</td>";
    echo "<td>";

    // Show cancel button if not "For Signature"
    if ($status === 'For Signature' || $status === 'Approved' || $status === 'Rejected') {
        echo $status;
    } else {
        echo "
            <form method='POST' action='../fetch_profilehistory.php' style='display:inline'>
                <input type='hidden' name='id' value='{$id}'>
                <input type='hidden' name='type' value='clearance'>
                <button type='submit' class='cancel-btn'>Cancel</button>
            </form>
        ";
    }
    
    echo "</td>";
    echo "</tr>";
}
$clearance_stmt->close();

$conn->close();
?>
