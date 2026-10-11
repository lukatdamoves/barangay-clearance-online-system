<?php
require_once 'vendor/autoload.php';
use PhpOffice\PhpWord\TemplateProcessor;
require 'db_connect.php';  // Ensure this is included properly

$form_id = $_POST['form_id'];
$user_id = $_POST['user_id'];
$type = $_POST['type'];

if (is_null($user_id)) {
    echo json_encode(["status" => "error", "message" => "User ID cannot be null"]);
    exit;
}

if (!$form_id || !$user_id || !$type) {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit;
}

// Choose the table and template based on form type
switch ($type) {
    case 'business':
        $table = 'business_request';
        $form_id_column = 'form_id';  // form_id for business_request
        $templatePath = 'docx_templates/business_template.docx';
        break;
    case 'clearance':
        $table = 'clearance_request';
        $form_id_column = 'id';  // id for clearance_request
        $templatePath = 'docx_templates/clearance_template.docx';
        break;
    default:
        echo json_encode(["status" => "error", "message" => "Unknown form type"]);
        exit;
}

// Fetch data based on dynamic form column
$stmt = $conn->prepare("SELECT * FROM $table WHERE $form_id_column = ? AND user_id = ?");
$stmt->bind_param("ii", $form_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Form not found"]);
    exit;
}

// Get the business_id or clearance_id from $data
$business_id = $data['form_id'] ?? null;
$clearance_id = $data['id'] ?? null;


// Check if the file already exists for the given clearance_id or business_id
if ($type === 'clearance' && !is_null($clearance_id)) {
    $check_stmt = $conn->prepare("SELECT * FROM outputed_docx WHERE clearance_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $clearance_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "A clearance document already exists for this user."]);
        exit;
    }
}

if ($type === 'business' && !is_null($business_id)) {
    $check_stmt = $conn->prepare("SELECT * FROM outputed_docx WHERE business_id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $business_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "A business document already exists for this user."]);
        exit;
    }
}

$template = new TemplateProcessor($templatePath);

// Set values for the template placeholders based on form type
if ($type === 'business') {
    // Set business-related fields
    $template->setValue('application_type', $data['application_type'] ?? '');
    $template->setValue('tin_no', $data['tin_no'] ?? '');
    $template->setValue('owner_name', $data['owner_name'] ?? '');
    $template->setValue('business_name', $data['business_name'] ?? '');
    $template->setValue('business_address', $data['business_address'] ?? '');
    $template->setValue('issued_at', $data['issued_at'] ?? '');
    $template->setValue('issued_on', $data['issued_on'] ?? '');
    $template->setValue('date_submitted', $data['date_submitted'] ?? '');
} elseif ($type === 'clearance') {
    $template->setValue('date_submitted', $data['date_submitted'] ?? '');
    $template->setValue('first_name', $data['first_name'] ?? '');
    $template->setValue('middle_name', $data['middle_name'] ?? '');
    $template->setValue('last_name', $data['last_name'] ?? '');
    $template->setValue('name_suffix', $data['name_suffix'] ?? '');
    $template->setValue('address', $data['address'] ?? '');
    $template->setValue('since_when', $data['since_when'] ?? '');
    $template->setValue('birth_date', $data['birth_date'] ?? '');
    $template->setValue('civil_status', $data['civil_status'] ?? '');
    $template->setValue('purpose', $data['purpose'] ?? '');
    
    // Set applicant photo for clearance only
    $template->setImageValue('applicant_photo', 
        [
            'path' => $data['applicant_photo'],
            'width' => 153,  // Width in inches
            'height' => 171 // Height in inches
        ]
    );
}

// Save to DOCX
$outputFile = "output_docx/{$type}_{$form_id}.docx";
$template->saveAs($outputFile);

// Insert the DOCX path to the database (outputed_docx table)
if ($type === 'clearance') {
    // Insert with clearance_id
    $stmt = $conn->prepare("INSERT INTO outputed_docx (user_id, type, file_path, clearance_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $type, $outputFile, $clearance_id);
} elseif ($type === 'business') {
    // Insert with business_id
    $stmt = $conn->prepare("INSERT INTO outputed_docx (user_id, type, file_path, business_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $type, $outputFile, $business_id);
}

$stmt->execute();

echo json_encode(["status" => "success", "file_path" => $outputFile]);
exit;
?>
