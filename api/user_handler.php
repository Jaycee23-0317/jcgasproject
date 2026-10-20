<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once '../php/db_config.php';
require_once '../api/activity_logs.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit();
}

$action = $_POST['action'] ?? '';

if ($action === 'check_username') {
    checkUsername($pdo);
} elseif ($action === 'create_user') {
    createUser($pdo, $conn);
} elseif ($action === 'update_user_field') {
    updateUserField($pdo, $conn);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);
    exit();
}

function checkUsername($pdo)
{
    $username = trim($_POST['username'] ?? '');

    if (empty($username)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username is required.'
        ]);
        exit();
    }

    if (strlen($username) < 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Username must be at least 3 characters.'
        ]);
        exit();
    }

    $stmt = $pdo->prepare("
        SELECT UserID
        FROM user
        WHERE Username = ?
        LIMIT 1
    ");

    $stmt->execute([$username]);

    $available = $stmt->fetch(PDO::FETCH_ASSOC) === false;

    echo json_encode([
        'success' => true,
        'available' => $available,
        'message' => $available
            ? 'Username is available.'
            : 'Username is already taken.'
    ]);

    exit();
}

function createUser($pdo, $conn)
{
    // if (empty($_SESSION['verified_email'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Please verify your Gmail address first.'
    //     ]);
    //     exit();
    // }

    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';

    if (
        empty($email) ||
        empty($username) ||
        empty($password) ||
        empty($fullName) ||
        empty($contactNumber) ||
        empty($address) ||
        $latitude === '' ||
        $longitude === ''
    ) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be completed.'
        ]);
        exit();
    }

    if (strlen($username) < 3) {
        echo json_encode([
            'success' => false,
            'message' => 'Username must be at least 3 characters.'
        ]);
        exit();
    }

    if (strlen($password) < 6) {
        echo json_encode([
            'success' => false,
            'message' => 'Password must be at least 6 characters.'
        ]);
        exit();
    }

    if (!is_numeric($latitude) || !is_numeric($longitude)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid location coordinates.'
        ]);
        exit();
    }

    // Check username
    $checkUsername = $pdo->prepare("
        SELECT UserID
        FROM user
        WHERE Username = ?
        LIMIT 1
    ");

    $checkUsername->execute([$username]);

    if ($checkUsername->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => false,
            'message' => 'Username is already taken.'
        ]);
        exit();
    }

    // Check email
    $checkEmail = $pdo->prepare("
        SELECT UserID
        FROM user
        WHERE Email = ?
        LIMIT 1
    ");

    $checkEmail->execute([$email]);

    if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => false,
            'message' => 'This Gmail account is already registered.'
        ]);
        exit();
    }

    $role = 'Customer';

    $stmt = $pdo->prepare("
        INSERT INTO user
        (
            FullName,
            Username,
            Address,
            Password,
            Role,
            Email,
            ContactNumber,
            Latitude,
            Longitude
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    try {
        $stmt->execute([
            $fullName,
            $username,
            $address,
            $password,
            $role,
            $email,
            $contactNumber,
            (float) $latitude,
            (float) $longitude
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' || $e->errorInfo[1] == 1062) {
            echo json_encode([
                'success' => false,
                'message' => 'Username or email is already registered.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create account. ' . $e->getMessage()
            ]);
        }

        exit();
    }

    $userID = $pdo->lastInsertId();

    unset($_SESSION['verified_email']);

    echo json_encode([
        'success' => true,
        'message' => 'Account created successfully.',
        'user_id' => (int) $userID
    ]);

    $logNewData = [
        'Email' => $email,
        'FullName' => $username,
        'Address' => $address,
        'ContactNumber' => $contactNumber,
        'Latitude' => $latitude,
        'Longitude' => $longitude,
    ];

    createActivityLog(
        $conn,
        'CREATE',
        'Account',
        (int) $userID,
        (int) $userID,
        NULL,
        "Created account {$username}",
        NULL,
        $logNewData
    );

    exit();
}

function updateUserField($pdo, $conn)
{
    $userID = (int) ($_POST['user_id'] ?? 0);
    $field = trim($_POST['field'] ?? '');

    if ($userID <= 0 || empty($field)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid update request.'
        ]);
        exit();
    }

    $allowedFields = [
        'Username',
        'Email',
        'Password',
        'FullName',
        'ContactNumber',
        'Address'
    ];

    if (!in_array($field, $allowedFields, true)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid field.'
        ]);
        exit();
    }

    $checkUser = $pdo->prepare("
        SELECT UserID
        FROM user
        WHERE UserID = ?
        LIMIT 1
    ");

    $checkUser->execute([$userID]);

    if (!$checkUser->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode([
            'success' => false,
            'message' => 'User account not found.'
        ]);
        exit();
    }

    /*
     * USERNAME
     */
    if ($field === 'Username') {
        $value = trim($_POST['value'] ?? '');

        if (strlen($value) < 3) {
            echo json_encode([
                'success' => false,
                'message' => 'Username must be at least 3 characters.'
            ]);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT UserID
            FROM user
            WHERE Username = ?
            AND UserID != ?
            LIMIT 1
        ");

        $stmt->execute([
            $value,
            $userID
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => false,
                'message' => 'Username is already taken.'
            ]);
            exit();
        }

        $oldStmt = $pdo->prepare("
            SELECT Username
            FROM user
            WHERE UserID = ?
        ");

        $oldStmt->execute([$userID]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        $update = $pdo->prepare("
            UPDATE user
            SET Username = ?
            WHERE UserID = ?
        ");

        $update->execute([
            $value,
            $userID
        ]);

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $userID,
            $userID,
            NULL,
            "Updated username",
            $oldData,
            ['Username' => $value]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Username updated successfully.',
            'field' => 'Username',
            'value' => $value
        ]);

        exit();
    }

    /*
     * EMAIL
     */
    if ($field === 'Email') {
        $value = trim($_POST['value'] ?? '');

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Please enter a valid email address.'
            ]);
            exit();
        }

        $stmt = $pdo->prepare("
            SELECT UserID
            FROM user
            WHERE Email = ?
            AND UserID != ?
            LIMIT 1
        ");

        $stmt->execute([
            $value,
            $userID
        ]);

        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode([
                'success' => false,
                'message' => 'This email address is already registered.'
            ]);
            exit();
        }

        $oldStmt = $pdo->prepare("
            SELECT Email
            FROM user
            WHERE UserID = ?
        ");

        $oldStmt->execute([$userID]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        $update = $pdo->prepare("
            UPDATE user
            SET Email = ?
            WHERE UserID = ?
        ");

        $update->execute([
            $value,
            $userID
        ]);

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $userID,
            $userID,
            NULL,
            "Updated email",
            $oldData,
            ['Email' => $value]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Email updated successfully.',
            'field' => 'Email',
            'value' => $value
        ]);

        exit();
    }

    /*
     * PASSWORD
     */
    if ($field === 'Password') {
        $newPassword = $_POST['value'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (strlen($newPassword) < 6) {
            echo json_encode([
                'success' => false,
                'message' => 'Password must be at least 6 characters.'
            ]);
            exit();
        }

        if ($newPassword !== $confirmPassword) {
            echo json_encode([
                'success' => false,
                'message' => 'Passwords do not match.'
            ]);
            exit();
        }

        // $hashedPassword = password_hash(
        //     $newPassword,
        //     PASSWORD_DEFAULT
        // );

        $update = $pdo->prepare("
            UPDATE user
            SET Password = ?
            WHERE UserID = ?
        ");

        $update->execute([
            $newPassword,
            $userID
        ]);

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $userID,
            $userID,
            NULL,
            "Updated password",
            NULL,
            ['Password' => $newPassword]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Password updated successfully.',
            'field' => 'Password'
        ]);

        exit();
    }

    /*
     * ADDRESS
     */
    if ($field === 'Address') {
        $value = trim($_POST['value'] ?? '');
        $latitude = $_POST['latitude'] ?? '';
        $longitude = $_POST['longitude'] ?? '';

        if (empty($value)) {
            echo json_encode([
                'success' => false,
                'message' => 'Address is required.'
            ]);
            exit();
        }

        if (!is_numeric($latitude) ||
            !is_numeric($longitude)) {
            echo json_encode([
                'success' => false,
                'message' => 'Valid location coordinates are required.'
            ]);
            exit();
        }

        $oldStmt = $pdo->prepare("
            SELECT Address, Latitude, Longitude
            FROM user
            WHERE UserID = ?
        ");

        $oldStmt->execute([$userID]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        $update = $pdo->prepare("
            UPDATE user
            SET Address = ?,
                Latitude = ?,
                Longitude = ?
            WHERE UserID = ?
        ");

        $update->execute([
            $value,
            (float) $latitude,
            (float) $longitude,
            $userID
        ]);

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $userID,
            $userID,
            NULL,
            "Updated address",
            $oldData,
            [
                'Address' => $value,
                'Latitude' => $latitude,
                'Longitude' => $longitude
            ]
        );

        echo json_encode([
            'success' => true,
            'message' => 'Address updated successfully.',
            'field' => 'Address',
            'value' => $value,
            'latitude' => (float) $latitude,
            'longitude' => (float) $longitude
        ]);

        exit();
    }

    /*
     * FULL NAME / CONTACT NUMBER
     */
    if ($field === 'FullName' ||
        $field === 'ContactNumber') {

        $value = trim($_POST['value'] ?? '');

        if (empty($value)) {
            echo json_encode([
                'success' => false,
                'message' => 'Value cannot be empty.'
            ]);
            exit();
        }

        $oldStmt = $pdo->prepare("
            SELECT {$field}
            FROM user
            WHERE UserID = ?
        ");

        $oldStmt->execute([$userID]);
        $oldData = $oldStmt->fetch(PDO::FETCH_ASSOC);

        $update = $pdo->prepare("
            UPDATE user
            SET {$field} = ?
            WHERE UserID = ?
        ");

        $update->execute([
            $value,
            $userID
        ]);

        createActivityLog(
            $conn,
            'UPDATE',
            'Account',
            $userID,
            $userID,
            NULL,
            "Updated {$field}",
            $oldData,
            [$field => $value]
        );

        echo json_encode([
            'success' => true,
            'message' => "{$field} updated successfully.",
            'field' => $field,
            'value' => $value
        ]);

        exit();
    }
}