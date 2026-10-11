    <?php
    // Include the PHPMailer configuration file

    include 'db_connect.php';
    require 'vendor/autoload.php';
    
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Debugging: Check database connection
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    } else {
        error_log("Database connected successfully");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get POST data
        $status = $_POST['status'];
        $formId = $_POST['form_id'];
        $type = $_POST['type'];
        
        // Check if the status is "Rejected" and get the reason if provided
        $reason = isset($_POST['reason']) ? $_POST['reason'] : null;

        // Debugging: Check the received data
        error_log("Status: $status, Form ID: $formId, Type: $type, Reason: $reason");

        // Determine the table name based on the request type
        $table = ($type === 'business') ? 'business_request' : 'clearance_request';
        $idColumn = ($type === 'business') ? 'form_id' : 'id';

        // Debugging: Log table name and ID column
        error_log("Table: $table, ID Column: $idColumn");

        // Prepare the SQL query to update the status
        $sql = "UPDATE $table SET status = ? WHERE $idColumn = ?";
        
        // Debugging: Log the SQL query and the bindings
        error_log("SQL Query: $sql");
        error_log("Bindings: status = $status, formId = $formId");

        if ($stmt = $conn->prepare($sql)) {
            // Binding parameters to the prepared statement
            $stmt->bind_param('si', $status, $formId);

            // Check if the binding was successful
            if ($stmt->error) {
                error_log("Binding error: " . $stmt->error);
            }

            // Execute the statement
            if ($stmt->execute()) {
                // Debugging: Check how many rows were affected
                if ($stmt->affected_rows > 0) {
                    error_log("Rows affected: " . $stmt->affected_rows);
                } else {
                    error_log("No rows affected. This may be because the ID is incorrect or the status value is the same.");
                }

                // If the status is rejected, send an email
                if ($status === "Rejected" && $reason) {
                    // Get the email of the user associated with the request
                    $emailSql = "SELECT u.email FROM users u JOIN $table t ON u.user_id = t.user_id WHERE t.$idColumn = ?";
                    if ($emailStmt = $conn->prepare($emailSql)) {
                        $emailStmt->bind_param('i', $formId);  // Use form_id for business, id for clearance
                        $emailStmt->execute();
                        $result = $emailStmt->get_result();

                        // Debugging: Check if the email query returns a result
                        if ($result->num_rows > 0) {
                            if ($row = $result->fetch_assoc()) {
                                $userEmail = $row['email'];

                                // Send email with PHPMailer
                                $mail = new PHPMailer(true);  // Enable exceptions
                                try {
                                    $mail->isSMTP();
                                    $mail->Host = 'smtp.gmail.com';
                                    $mail->SMTPAuth = true;
                                    $mail->Username = 'your-email@gmail.com';  // Admin's email
                                    $mail->Password = 'your-app-password-here';  // App password
                                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                                    $mail->Port = 587;

                                    $mail->setFrom('your-email@gmail.com', 'Barangay Santo Cristo Online Clearance');
                                    $mail->addAddress($userEmail);  // Send to the user
                                    $mail->Subject = 'Your Request Has Been Rejected';
                                    $mail->Body    = "Your request has been rejected.\n\nReason: $reason";

                                    $mail->send();
                                    error_log("Rejection email sent to $userEmail.");
                                    echo 'Status updated and rejection email sent.';
                                } catch (Exception $e) {
                                    error_log("Failed to send email: " . $mail->ErrorInfo);
                                    echo "Failed to send email: " . $mail->ErrorInfo;
                                }
                            }
                        } else {
                            error_log('No email found for this request.');
                            echo 'No email found for this request.';
                        }
                    } else {
                        error_log('Error preparing email query: ' . $conn->error);
                        echo 'Error preparing email query: ' . $conn->error;
                    }
                } else {
                    error_log('Status updated successfully.');
                    echo 'Status updated successfully.';
                }
            } else {
                // Log the execution error
                error_log("Error executing statement: " . $stmt->error);
                echo 'Error executing statement: ' . $stmt->error;
            }
        } else {
            // Log the prepare error
            error_log("Error preparing statement: " . $conn->error);
            echo 'Error preparing statement: ' . $conn->error;
        }
    } else {
        echo 'Invalid request method.';
        error_log('Invalid request method.');
    }

    $conn->close();
    ?>
