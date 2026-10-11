<?php
require_once __DIR__ . '/vendor/autoload.php';
include 'db_connect.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
    die("Unauthorized access.");
}

use Dompdf\Dompdf;
use Dompdf\Options;

// Configure Dompdf
$options = new Options();
$options->set('defaultFont', 'Arial');
$options->set('isRemoteEnabled', true); // Required for loading local file paths
$dompdf = new Dompdf($options);
$uploadDir = __DIR__ . '/uploads';

// Sanitize inputs
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : '';
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : '';
$clearanceFilters = isset($_GET['clearanceFilters']) ? (array) $_GET['clearanceFilters'] : [];
$businessFilters = isset($_GET['businessFilters']) ? (array) $_GET['businessFilters'] : [];

$clearanceFilters = array_filter($clearanceFilters, function($value) {
    return !empty(trim($value));
});
$businessFilters = array_filter($businessFilters, function($value) {
    return !empty(trim($value));
});

$displayPeriod = (!empty($selectedMonth) && !empty($selectedYear))
    ? date("F", mktime(0, 0, 0, $selectedMonth, 10)) . " " . $selectedYear
    : "All Time";

$displayClearanceFilters = !empty($clearanceFilters) ? implode(", ", $clearanceFilters) : "None";
$displayBusinessFilters = !empty($businessFilters) ? implode(", ", $businessFilters) : "None";

function buildFilterCondition($column, $filters) {
    if (empty($filters)) {
        return "1=1";
    }
    $safe = array_map('addslashes', $filters);
    $quoted = array_map(fn($val) => "'$val'", $safe);
    return $column . " IN (" . implode(',', $quoted) . ")";
}

// Get Total Registration Count
$registrationSql = "SELECT COUNT(*) as total_registration FROM users WHERE 1";

if ($selectedMonth && $selectedYear) {
    $registrationSql .= " AND MONTH(date_created) = $selectedMonth AND YEAR(date_created) = $selectedYear";
}

$registrationResult = mysqli_query($conn, $registrationSql);
$totalRegistration = 0;

if ($registrationResult) {
    $registrationRow = mysqli_fetch_assoc($registrationResult);
    $totalRegistration = $registrationRow['total_registration'];
}

// Start HTML
$html = '
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h2, p { text-align: center; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #000; padding: 6px; text-align: center; }
    th { background-color: #f2f2f2; }
    img { width: 100px; height: 100px; object-fit: cover; }
    .section-title { margin-top: 20px; margin-bottom: 10px; text-align: center; }
    .no-data { text-align: center; font-style: italic; padding: 20px; }
    .total-count { text-align: right; font-style: italic; margin-top: 5px; }
</style>

<h2>Barangay Santo Cristo - Analytics Report</h2>
<p><strong>Period:</strong> ' . $displayPeriod . '</p>
<p><strong>Total Registrations:</strong> ' . $totalRegistration . '</p> 
<p><strong>Clearance Filters:</strong> ' . $displayClearanceFilters . ' | <strong>Business Filters:</strong> ' . $displayBusinessFilters . '</p>
';

$hasData = false;

// Clearance Requests
if (!empty($clearanceFilters) || (empty($clearanceFilters) && empty($businessFilters))) {
    $sql = "SELECT * FROM clearance_request";
    $conditions = [];

    if (!empty($clearanceFilters)) {
        $conditions[] = buildFilterCondition("purpose", $clearanceFilters);
    }

    if ($selectedMonth && $selectedYear) {
        $conditions[] = "MONTH(date_submitted) = $selectedMonth AND YEAR(date_submitted) = $selectedYear";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY date_submitted DESC LIMIT 100";
    $result = mysqli_query($conn, $sql);

    $html .= '<h3 class="section-title">Clearance Requests</h3>';

    if (mysqli_num_rows($result) > 0) {
        $hasData = true;
        $html .= '<table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Surname</th>
                    <th>First Name</th>
                    <th>Middle Name</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Date Applied</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

            while ($row = mysqli_fetch_assoc($result)) {
                $photo = $row['applicant_photo'];
                $filename = '';
    
                if (strpos($photo, 'file=') !== false) {
                    parse_str(parse_url($photo, PHP_URL_QUERY), $params);
                    $filename = $params['file'] ?? '';
                } else {
                    $filename = basename($photo);
                }
    
                $imagePath = $uploadDir . '/' . $filename;
                $photoSrc = '';
    
                if (file_exists($imagePath)) {
                    $imageData = file_get_contents($imagePath);
                    $mimeType = mime_content_type($imagePath);
                    $photoSrc = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                } else {
                    $photoSrc = '';
                }
    
                $html .= '<tr>';
                $html .= '<td><img src="' . $photoSrc . '" width="100"></td>';
                $html .= '<td>' . htmlspecialchars($row['last_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['first_name']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['middle_name']) . '</td>';
                $html .= '<td>Clearance</td>';
                $html .= '<td>' . htmlspecialchars($row['purpose']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['date_submitted']) . '</td>';
                $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
                $html .= '</tr>';
            }          

        $html .= '</tbody></table>';
        $html .= '<p class="total-count">Total Clearance Requests: ' . mysqli_num_rows($result) . '</p>';
    } else {
        $html .= '<p class="no-data">No clearance requests found for selected filters.</p>';
    }
}

// Business Requests
if (!empty($businessFilters) || (empty($clearanceFilters) && empty($businessFilters))) {
    $sql = "SELECT * FROM business_request";
    $conditions = [];

    if (!empty($businessFilters)) {
        $conditions[] = buildFilterCondition("application_type", $businessFilters);
    }

    if ($selectedMonth && $selectedYear) {
        $conditions[] = "MONTH(date_submitted) = $selectedMonth AND YEAR(date_submitted) = $selectedYear";
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $sql .= " ORDER BY date_submitted DESC LIMIT 100";
    $result = mysqli_query($conn, $sql);

    $html .= '<h3 class="section-title">Business Requests</h3>';

    if (mysqli_num_rows($result) > 0) {
        $hasData = true;
        $html .= '<table>
            <thead>
                <tr>
                    <th>Business Name</th>
                    <th>Owner</th>
                    <th>Address</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Date Applied</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

        while ($row = mysqli_fetch_assoc($result)) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['business_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['owner_name']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['business_address']) . '</td>';
            $html .= '<td>Business</td>';
            $html .= '<td>' . htmlspecialchars($row['application_type']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['date_submitted']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['status']) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<p class="total-count">Total Business Requests: ' . mysqli_num_rows($result) . '</p>';
    } else {
        $html .= '<p class="no-data">No business requests found for selected filters.</p>';
    }
}

if (!$hasData) {
    $html .= '<p class="no-data">No data found for the selected criteria.</p>';
}

// Generate PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('letter', 'landscape');
$dompdf->render();
$dompdf->stream("analytics_report.pdf", ["Attachment" => false]);
exit;
?>
