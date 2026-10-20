<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once '../php/db_config.php';
require_once '../api/activity_logs.php';
require_once '../api/branchInventory.php';

if (basename($_SERVER['SCRIPT_FILENAME']) === 'branch.php') {
    handleBranchRequest($conn);
}

function getBranchAddress($conn, $branchID)
{
    $sql = "SELECT Address, Longitude, Latitude FROM branches WHERE BranchID = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['error' => 'Prepare failed: ' . $conn->error];
    }

    $stmt->bind_param("i", $branchID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        $stmt->close();
        return $result->fetch_assoc(); // Removed redundant array_values()
    }

    $stmt->close();
    return ['error' => 'Query failed: ' . $conn->error];
}

function getAllBranch($conn)
{
    $stmt = $conn->prepare("SELECT * FROM branches");
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result) {
        $branches = [];

        while ($row = $result->fetch_assoc()) {
            $branches[] = $row;
        }

        $stmt->close();
        return $branches;
    }

    return ['error' => 'Query failed: ' . $conn->error];
}

function getBranch($conn, $branchID)
{
    $stmt = $conn->prepare("SELECT * FROM branches WHERE BranchID = ?");
    $stmt->bind_param(
        "i",
        $branchID
    );

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc();
    }

    return ['error' => 'Query failed: ' . $conn->error];
}

function updateBranch($conn) {
    $branchId = trim($_POST['branch_id'] ?? '');
    $branchName = trim($_POST['branch_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $userID = $_SESSION['user_id'];

    $oldBranchData = getBranch($conn, $branchId);

    try {
        $sql = "UPDATE branches SET BranchName = ?, Email = ?, Address = ?, ContactNumber = ?, Latitude = ?, Longitude = ? WHERE BranchID = ?";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Query preparation trace failed: " . $conn->error);
        }

        $stmt->bind_param('ssssssi', $branchName, $email, $address, $contactNumber, $latitude, $longitude, $branchId);

        if ($stmt->execute()) {

            $logOldData = [
                'Branch Name' => $oldBranchData['BranchName'],
                'Email' => $oldBranchData['Email'],
                'Address' => $oldBranchData['Address'],
                'Contact Number' => $oldBranchData['ContactNumber'],
                'Latitude' => $oldBranchData['Latitude'],
                'Longitude' => $oldBranchData['Longitude'],
            ];

            $logNewData = [
                'Branch Name' => $branchName,
                'Email' => $email,
                'Address' => $address,
                'Contact Number' => $contactNumber,
                'Latitude' => $latitude,
                'Longitude' => $longitude,
            ];

            createActivityLog(
                $conn,
                'UPDATE',
                'Branch',
                $branchId,
                $userID,
                $branchId,
                "Updated branch {$branchName}",
                $logOldData,
                $logNewData
            );
            echo json_encode(['success' => true, 'message' => 'Branch record successfully updated!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution tracking update failure occurred: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database processing exception occurred: ' . $e->getMessage()]);
    }

    $conn->close();
    exit();
}

function addedBranch($conn) {
    $branchName = trim($_POST['branch_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');

    $userID = $_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        $sql = "INSERT INTO branches
                    (BranchName, Email, Address, ContactNumber, Latitude, Longitude)
                    VALUES
                    (? ,?, ?, ? ,?, ?)"
        ;
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Query preparation trace failed: " . $conn->error);
        }

        $stmt->bind_param('ssssss', $branchName, $email, $address, $contactNumber, $latitude, $longitude);

        if ($stmt->execute()) {
            $branchID = $conn->insert_id;
            
            $logNewData = [
                'Branch Name' => $branchName,
                'Email' => $email,
                'Address' => $address,
                'Contact Number' => $contactNumber,
                'Latitude' => $latitude,
                'Longitude' => $longitude,
            ];

            createActivityLog(
                $conn,
                'CREATE',
                'Branch',
                $branchID,
                $userID,
                $branchID,
                "Created branch {$branchName}",
                null,
                $logNewData
            );
            
            $conn->commit();
            addAllProductsForNewBranch($conn, $branchID);
            echo json_encode(['success' => true, 'message' => 'Branch record successfully added!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database execution tracking update failure occurred: ' . $stmt->error]);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database processing exception occurred: ' . $e->getMessage()]);
    }

    $conn->close();
    exit();
}

function handleBranchRequest($conn)
{
    header('Content-Type: application/json; charset=utf-8');
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method == 'GET') {
        $branches = getAllBranch($conn);
        echo json_encode($branches);
    } else if ($method == 'POST') {
        $branchId = trim($_POST['branch_id'] ?? '');
        if ($branchId) {
            $updatedBranch = updateBranch($conn);
            echo json_encode($updatedBranch);
        } else {
            $addedBranch = addedBranch($conn);
            echo json_encode($addedBranch);
        }
    }
}
