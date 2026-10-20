<?php

include_once '../php/db_config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
//     http_response_code(419);
//     echo json_encode(['success' => false, 'error' => 'Unauthorized session profile configuration.']);
//     exit();
// }

function getAllLogs($conn) {

    $sql = "
        SELECT
            al.LogID,
            al.Action,
            al.EntityType,
            al.EntityID,
            u.FullName AS CreatedBy,
            b.BranchName as BranchName,
            al.Description,
            al.OldData,
            al.NewData,
            al.CreatedAt
        FROM `activity_logs` al
        LEFT JOIN `user` u
            ON al.ActorUserID = u.UserID
        LEFT JOIN `branches` b
            ON al.BranchID = b.BranchID
        ORDER BY al.CreatedAt DESC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            'success' => false,
            'error' => 'Prepare failed: ' . $conn->error
        ];
    }

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {

        $flatRows = $result->fetch_all(MYSQLI_ASSOC);

        $stmt->close();

        return [
            'success' => true,
            'data' => $flatRows
        ];
    }

    $stmt->close();

    return [
        'success' => false,
        'error' => 'Query failed: ' . $conn->error
    ];
}

function createActivityLog(
    $conn,
    $action,
    $entityType,
    $entityID = null,
    $actorUserID = null,
    $branchID = null,
    $description = null,
    $oldData = null,
    $newData = null
) {

    $action = trim($action);
    $entityType = trim($entityType);

    if ($action === '') {

        throw new Exception(
            'Action is required.'
        );
    }

    if ($entityType === '') {

        throw new Exception(
            'EntityType is required.'
        );
    }

    if ($oldData !== null && !is_string($oldData)) {

        $oldData = json_encode(
            $oldData,
            JSON_UNESCAPED_UNICODE
        );

        if ($oldData === false) {

            throw new Exception(
                'Failed to encode OldData as JSON.'
            );
        }
    }

    if ($newData !== null && !is_string($newData)) {

        $newData = json_encode(
            $newData,
            JSON_UNESCAPED_UNICODE
        );

        if ($newData === false) {

            throw new Exception(
                'Failed to encode NewData as JSON.'
            );
        }
    }

    $entityID = (
        $entityID === ''
        ? null
        : $entityID
    );

    $actorUserID = (
        $actorUserID === ''
        ? null
        : $actorUserID
    );

    $branchID = (
        $branchID === ''
        ? null
        : $branchID
    );

    $sql = "
        INSERT INTO `activity_logs`
        (
            Action,
            EntityType,
            EntityID,
            ActorUserID,
            BranchID,
            Description,
            OldData,
            NewData
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ";


    $stmt = $conn->prepare($sql);

    if (!$stmt) {

        throw new Exception(
            'Prepare failed: ' . $conn->error
        );
    }


    $stmt->bind_param(
        "ssiiisss",
        $action,
        $entityType,
        $entityID,
        $actorUserID,
        $branchID,
        $description,
        $oldData,
        $newData
    );


    if (!$stmt->execute()) {

        $error = $stmt->error;

        $stmt->close();

        throw new Exception(
            'Insert failed: ' . $error
        );
    }


    $logID = $stmt->insert_id;

    $stmt->close();


    return [
        'success' => true,
        'message' => 'Activity log created successfully.',
        'LogID' => $logID
    ];
}

function handleLogsRequest($conn) {

    header(
        'Content-Type: application/json; charset=utf-8'
    );

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {

        $logs = getAllLogs($conn);

        echo json_encode($logs);

        return;
    }

    if ($method === 'POST') {

        $input = json_decode(
            file_get_contents("php://input"),
            true
        );


        if (!is_array($input)) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => 'Invalid JSON request body.'
            ]);

            return;
        }


        try {

            $result = createActivityLog(
                $conn,
                $input['Action'] ?? '',
                $input['EntityType'] ?? '',
                $input['EntityID'] ?? null,
                $input['ActorUserID'] ?? null,
                $input['BranchID'] ?? null,
                $input['Description'] ?? null,
                $input['OldData'] ?? null,
                $input['NewData'] ?? null
            );


            echo json_encode($result);

        } catch (Exception $e) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }

        return;
    }


    http_response_code(405);

    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.'
    ]);
}

if (
    basename($_SERVER['SCRIPT_FILENAME'])
    === basename(__FILE__)
) {

    handleLogsRequest($conn);
}

?>