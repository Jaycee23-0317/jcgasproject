<?php

require_once __DIR__ . "/../config/database.php";

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Only POST requests are allowed."
    ]);

    exit;
}


// Get JSON sent by Flutter
$input = json_decode(file_get_contents("php://input"), true);

$username = trim($input["username"] ?? "");
$password = $input["password"] ?? "";


// Validate input
if ($username === "" || $password === "") {

    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "Username and password are required."
    ]);

    exit;
}


try {

    /*
     * `user` is enclosed in backticks because USER can conflict
     * with MySQL system/reserved identifiers.
     */

    $sql = "
        SELECT
            UserID,
            FullName,
            Username,
            Address,
            Password,
            Role,
            BranchID,
            Email,
            ContactNumber,
            EmailVerified,
            Latitude,
            Longitude,
            CreatedAt
        FROM `user`
        WHERE Username = :username AND Role = 'CUSTOMER'
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":username" => $username
    ]);

    $user = $stmt->fetch();


    // User doesn't exist
    if (!$user) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password."
        ]);

        exit;
    }


    /*
     * TEMPORARY PASSWORD CHECK
     *
     * Your current database stores passwords as plain text.
     *
     * Example:
     * Database password = 123456
     * Flutter password  = 123456
     */

    if (!hash_equals((string)$user["Password"], (string)$password)) {

        http_response_code(401);

        echo json_encode([
            "success" => false,
            "message" => "Invalid username or password."
        ]);

        exit;
    }


    /*
     * IMPORTANT:
     * Never send the password back to Flutter.
     */

    unset($user["Password"]);


    // Successful login
    echo json_encode([
        "success" => true,
        "message" => "Login successful.",
        "user" => $user
    ]);

} catch (PDOException $e) {

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "An error occurred while processing the login."
    ]);
}