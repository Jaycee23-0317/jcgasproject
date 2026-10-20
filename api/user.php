<?php
include_once '../php/db_config.php';
require_once '../api/activity_logs.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (basename($_SERVER['SCRIPT_FILENAME']) === 'user.php') {
    handleUserRequest($conn);
}

function getUserAddress($conn, $userID)
{
    $sql = "SELECT Address, Longitude, Latitude FROM user WHERE UserID = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }

    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $stmt->close();
        return $result->fetch_assoc(); // Removed redundant array_values()
    }

    $stmt->close();
    return ['error' => 'Query failed: ' . $conn->error];
}

function getUserInfo($conn, $userID)
{
    $sql = "SELECT * FROM user WHERE UserID = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }

    $stmt->bind_param("i", $userID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $data = $result->fetch_assoc();
        $stmt->close();

        if (!$data) {
            return ['error' => 'User records not found in database registry.'];
        }

        return $data;
    }

    $stmt->close();
    return ['error' => 'Query execution sequence tracking failure: ' . $conn->error];
}

function updateUserByAdmin($conn, $user, $actionUserID) {
    $userOldData = getUserPreviousData($conn, $user['userID']);

    $sql = "UPDATE user 
        SET BranchID = ?, 
            Username = ?, 
            Password = ?
        WHERE UserID = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Prepare failed: ' . $conn->error
        ];
    }

    $stmt->bind_param(
        "issi",
        $user['branchID'],
        $user['username'],
        $user['password'],
        $user['userID']
    );

    if ($stmt->execute()) {

        $stmt->close();

        $logOldData = [
            'Branch' => $userOldData['BranchName'],
            'Username' => $userOldData['Username'],
            'Password' => $userOldData['Password'],
        ];

        $logNewData = [
            'Branch' => $user['branchName'],
            'Username' => $user['username'],
            'Password' => $user['password'],
        ];

        $description = "Updated account of {$user['username']} by Admin";

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $user['userID'],
            $actionUserID,
            $user['branchID'],
            $description,
            $logOldData,
            $logNewData
        );

        return [
            'success' => true,
            'message' => 'Staff credentials has been updated'
        ];

    } else {

        $error = $stmt->error;
        $stmt->close();

        return [
            'success' => false,
            'message' => 'Query failed: ' . $error
        ];
    }
}

function updateUser($conn, $user, $actionUserID) {

    $userOldData = getUserPreviousData($conn, $user['userID']);

    $sql = "UPDATE user 
            SET FullName = ?, 
                Address = ?, 
                ContactNumber = ?, 
                Latitude = ?, 
                Longitude = ?
            WHERE UserID = ?";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Prepare failed: ' . $conn->error
        ];
    }

    $stmt->bind_param(
        "sssddi",
        $user['fullName'],
        $user['address'],
        $user['contactNumber'],
        $user['latitude'],
        $user['longitude'],
        $user['userID']
    );

    if ($stmt->execute()) {

        $stmt->close();

        $logOldData = NULL;

        $logNewData = [
            'FullName' => $user['fullName'],
            'Address' => $user['address'],
            'ContactNumber' => $user['contactNumber'],
            'Latitude' => $user['latitude'],
            'Longitude' => $user['longitude'],
        ];

        $action = 'UPDATE';
        $description = NULL;
        $actionMessage = 'updated';

        if (isset($_GET['createAccount'])) {
            $action = 'CREATE';
            $actionMessage = 'added';
            $description = "Created account {$userOldData['Username']}";
        } else {
            $action = 'UPDATE';
            $description = "Updated account {$userOldData['Username']}";
            $logOldData = [
                'FullName' => $userOldData['FullName'],
                'Address' => $userOldData['Address'],
                'ContactNumber' => $userOldData['ContactNumber'],
                'Latitude' => $userOldData['Latitude'],
                'Longitude' => $userOldData['Longitude'],
            ];
        }

        createActivityLog(
            $conn,
            $action,
            'Account',
            $user['userID'],
            $actionUserID,
            NULL,
            $description,
            $logOldData,
            $logNewData
        );

        return [
            'success' => true,
            'message' => "Account profile has been updated {$actionMessage}"
        ];

    } else {

        $error = $stmt->error;
        $stmt->close();

        return [
            'success' => false,
            'message' => 'Query failed: ' . $error
        ];
    }
}

function updateUserLoginCredentials($conn, $user, $actionUserID) {
    $userOldData = getUserPreviousData($conn, $actionUserID);

    $sql = "UPDATE user 
        SET Email = ?, 
            Username = ?, 
            Password = ?
        WHERE UserID = ?
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'message' => 'Prepare failed: ' . $conn->error
        ];
    }

    $stmt->bind_param(
        "sssi",
        $user['email'],
        $user['username'],
        $user['password'],
        $actionUserID
    );

    try {
        $stmt->execute();
        $stmt->close();

        $logOldData = [
            'Email' => $userOldData['Email'],
            'Username' => $userOldData['Username'],
            'Password' => $userOldData['Password'],
        ];

        $logNewData = [
            'Email' => $user['email'],
            'Username' => $user['username'],
            'Password' => $user['password'],
        ];

        $description = "Updated account of {$user['username']}";

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $actionUserID,
            $actionUserID,
            null,
            $description,
            $logOldData,
            $logNewData
        );

        return [
            'success' => true,
            'message' => 'Staff credentials has been updated'
        ];
    } catch (mysqli_sql_exception $e) {
        $stmt->close();

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

function getUserPreviousData($conn, $userID) {

    $sql = "SELECT
                u.*,
                b.BranchName
            FROM `user` u
            LEFT JOIN branches b ON u.BranchID = b.BranchID        
            WHERE UserID = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "i",
        $userID
    );

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return  $result->fetch_assoc();
    } else {
        $error = $stmt->error;
        $stmt->close();

        return null;
    }
}

function getAllBranchStaff($conn) {
    $cashiersResult = $conn->query("
        SELECT u.UserID, u.Username, u.FullName, u.Email, u.ContactNumber, u.Role, u.CreatedAt, u.BranchID, b.BranchName, u.Address
        FROM user u LEFT JOIN branches b ON u.BranchID = b.BranchID WHERE u.Role = 'Cashier' ORDER BY b.BranchName, u.FullName
    ");
    $cashiers = [];

    while ($row = $cashiersResult->fetch_assoc()) {
        $cashiers[] = $row;
    }

    $conn->close();

    return $cashiers;
}

function getAllCustomer($conn) {
    $customersResult = $conn->query("
        SELECT UserID, FullName, Email, ContactNumber, Address, CreatedAt
        FROM user WHERE Role = 'Customer' ORDER BY CreatedAt DESC
    ");
    $customers = [];
    while ($row = $customersResult->fetch_assoc()) {
        $customers[] = $row;
    }

    $conn->close();

    return $customers;
}

function handleUserRequest($conn)
{
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];
    $userID = $_SESSION['user_id'];

    if ($method === 'GET') {
        if (isset($_GET['getBranchStaffByAdmin'])) {
            $data = getAllBranchStaff($conn);
            echo json_encode($data);
        } else if (isset($_GET['getAllCustomerByAdmin'])) {
            $data = getAllCustomer($conn);
            echo json_encode($data);
        } else {
            $user = getUserInfo($conn, $userID);
    
            // Sets dynamic network error states cleanly if something broke inside the function mapping
            if (isset($user['error'])) {
                http_response_code(400);
            }
    
            echo json_encode($user);
        }
    } else if ($method === 'POST') {
        $role = $_SESSION['role'];
        if (isset($_GET['userId']) && $role === 'Admin') {
            $user = [
                'userID'      => trim($_POST['user_id'] ?? ''),
                'branchID'      => trim($_POST['branch_id'] ?? ''),
                'branchName'      => trim($_POST['branch_name'] ?? ''),
                'username'      => trim($_POST['user_name'] ?? ''),
                'password'      => trim($_POST['password'] ?? '')
            ];
            echo json_encode(updateUserByAdmin($conn, $user, $userID));
        } else if (isset($_GET['user_id'])) { 
            $user = [
                'email'      => trim($_POST['form_email'] ?? ''),
                'username'      => trim($_POST['form_username'] ?? ''),
                'password'      => trim($_POST['form_password'] ?? ''),
            ];
            echo json_encode(updateUserLoginCredentials($conn, $user, $userID));
        } else {
            $user = [
                'userID'        => $userID,
                'fullName'      => trim($_POST['fullName'] ?? ''),
                'contactNumber' => trim($_POST['contactNumber'] ?? ''),
                'address'       => trim($_POST['address'] ?? ''),
                'latitude'      => !empty($_POST['latitude'])
                                    ? floatval($_POST['latitude'])
                                    : null,
                'longitude'     => !empty($_POST['longitude'])
                                    ? floatval($_POST['longitude'])
                                    : null
            ];
    
            // header('Content-Type: application/json');
            echo json_encode(updateUser($conn, $user, $userID));
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'HTTP Method Request Routing Allocation Forbidden.']);
    }
}
