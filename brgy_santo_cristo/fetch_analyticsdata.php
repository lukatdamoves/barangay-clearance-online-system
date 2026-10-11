        <?php
        session_start();
        header('Content-Type: application/json');

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        include 'db_connect.php';

        if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != 1) {
            echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
            exit();
        }

        $month = $_POST['month'] ?? '';
        $page = max(1, intval($_POST['page'] ?? 1));
        $limit = min(100, max(10, intval($_POST['limit'] ?? 25)));
        $search = trim($_POST['search'] ?? '');
        $clearanceGroups = $_POST['clearanceGroups'] ?? [];
        $businessGroups = $_POST['businessGroups'] ?? [];
        $showAllTime = isset($_POST['showAllTime']) && $_POST['showAllTime'] == '1';

        // Handle date ranges differently for all-time vs monthly data
        if ($showAllTime) {
            $startDate = '1970-01-01'; // Very old date
            $endDate = '9999-12-31';   // Far future date
        } else {
            if ($month) {
                $month = substr($month, 0, 7); // trim to YYYY-MM
            }

            if (!$month || !preg_match('/^\d{4}-\d{2}$/', $month)) {
                echo json_encode(["status" => "error", "message" => "Invalid month format."]);
                exit();
            }

            $startDate = $month . "-01";
            $endDate = date("Y-m-t", strtotime($startDate));
        }

        $offset = ($page - 1) * $limit;

        // Get counts (all-time or filtered by month)
        $registrationCount = getCount("SELECT COUNT(*) as total FROM users WHERE date_created BETWEEN ? AND ?", [$startDate, $endDate]);
        $clearanceCount = !empty($clearanceGroups) ? getCountWithGroup("clearance_request", "purpose", $clearanceGroups, $startDate, $endDate) : 0;
        $businessCount = !empty($businessGroups) ? getCountWithGroup("business_request", "application_type", $businessGroups, $startDate, $endDate) : 0;

        // Get table data (always filtered by selected month, even when showing all-time totals)
        $tableStartDate = $showAllTime ? '1970-01-01' : $startDate;
        $tableEndDate = $showAllTime ? '9999-12-31' : $endDate;
        $tableData = getCombinedData($tableStartDate, $tableEndDate, $clearanceGroups, $businessGroups, $search, $limit, $offset);
        $totalRecords = countAllCombined($tableStartDate, $tableEndDate, $clearanceGroups, $businessGroups, $search);
        $totalPages = ceil($totalRecords / $limit);

        echo json_encode([
            "status" => "success",
            "registration" => $registrationCount,
            "clearance" => $clearanceCount,
            "business_clearance" => $businessCount,
            "tableData" => $tableData,
            "totalPages" => $totalPages,
            "totalRecords" => $totalRecords,
            "currentPage" => $page,
            "showingAllTime" => $showAllTime
        ]);

        function getCount($query, $params) {
            global $conn;
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        }

        function getCountWithGroup($table, $column, $groups, $start, $end) {
            global $conn;
            $placeholders = implode(',', array_fill(0, count($groups), '?'));
            $query = "SELECT COUNT(*) as total FROM $table WHERE $column IN ($placeholders) AND date_submitted BETWEEN ? AND ?";
            $params = array_merge($groups, [$start, $end]);
            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('s', count($groups)) . 'ss', ...$params);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return $result['total'] ?? 0;
        }

        function getCombinedData($start, $end, $clearanceGroups, $businessGroups, $search, $limit, $offset) {
            $data = [];

            if (!empty($clearanceGroups)) {
                $data = array_merge($data, getData("clearance", $clearanceGroups, $start, $end, $search));
            }

            if (!empty($businessGroups)) {
                $data = array_merge($data, getData("business", $businessGroups, $start, $end, $search));
            }

            usort($data, fn($a, $b) => strtotime($b['date_applied']) - strtotime($a['date_applied']));
            return array_slice($data, $offset, $limit);
        }

        function getData($type, $groups, $start, $end, $search) {
            global $conn;
            $placeholders = implode(',', array_fill(0, count($groups), '?'));
            $params = $groups;

            if ($type === "clearance") {
                $query = "SELECT 
                            cr.applicant_photo as photo,
                            u.lastname as surname,
                            u.firstname,
                            u.middlename,
                            'Clearance' as type,
                            cr.purpose as category,
                            cr.date_submitted as date_applied,
                            cr.status
                        FROM clearance_request cr
                        LEFT JOIN users u ON cr.user_id = u.user_id
                        WHERE cr.purpose IN ($placeholders) AND cr.date_submitted BETWEEN ? AND ?";
                $params = array_merge($params, [$start, $end]);

                if ($search) {
                    $query .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.middlename LIKE ?)";
                    $term = "%$search%";
                    $params = array_merge($params, [$term, $term, $term]);
                }

            } else {
                $query = "SELECT 
                            '../image/default-user.png' as photo,
                            u.lastname as surname,
                            u.firstname,
                            u.middlename,
                            'Business' as type,
                            br.application_type as category,
                            br.date_submitted as date_applied,
                            br.status
                        FROM business_request br
                        LEFT JOIN users u ON br.user_id = u.user_id
                        WHERE br.application_type IN ($placeholders) AND br.date_submitted BETWEEN ? AND ?";
                $params = array_merge($params, [$start, $end]);

                if ($search) {
                    $query .= " AND (u.firstname LIKE ? OR u.lastname LIKE ? OR u.middlename LIKE ?)";
                    $term = "%$search%";
                    $params = array_merge($params, [$term, $term, $term]);
                }
            }

            $stmt = $conn->prepare($query);
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
            $stmt->execute();
            $res = $stmt->get_result();

            $data = [];
            while ($row = $res->fetch_assoc()) {
                if ($type === "clearance") {
                    if (!empty($row['photo'])) {
                        // Extract only the filename from the full path
                        $filename = basename($row['photo']);  // e.g. 866375fe-ff55-4060-ae08-cd50420a003b.jpg
                        $row['photo'] = $filename; // just return the filename only
                    } else {
                        $row['photo'] = 'image/default-user.png';
                    }
                }
            
                $data[] = $row;
            }
            return $data;
            
        }

        function countAllCombined($start, $end, $clearanceGroups, $businessGroups, $search) {
            return count(getCombinedData($start, $end, $clearanceGroups, $businessGroups, $search, PHP_INT_MAX, 0));
        }
