
<?php
session_start();
include("db.php");
require_once __DIR__ . "/config/iprog.php";

header("Content-Type: application/json; charset=UTF-8");

function respond($success, $message){
    echo json_encode([
        "success" => $success,
        "message" => $message
    ]);
    exit();
}

if(!isset($_SESSION['applicant_id']) || (int)$_SESSION['applicant_id'] <= 0){
    respond(false, "Session expired. Please log in again.");
}

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    respond(false, "Invalid request.");
}

$csrf = $_POST['csrf_token'] ?? '';

if(
    empty($_SESSION['phone_otp_csrf']) ||
    !hash_equals($_SESSION['phone_otp_csrf'], $csrf)
){
    respond(false, "Invalid security token. Refresh the page.");
}

$applicant_id = (int)$_SESSION['applicant_id'];

$stmt = mysqli_prepare($conn, "
    SELECT phone, phone_verified
    FROM applicant_accounts
    WHERE id = ? AND is_verified = 1
    LIMIT 1
");

mysqli_stmt_bind_param($stmt, "i", $applicant_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$applicant = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if(!$applicant){
    respond(false, "Applicant account not found.");
}

if((int)$applicant['phone_verified'] === 1){
    respond(false, "Your mobile number is already verified.");
}

/* Convert registered number to 639XXXXXXXXX */

$phone = preg_replace('/[\s\-()]/', '', $applicant['phone']);

if(preg_match('/^09\d{9}$/', $phone)){
    $phone = '63' . substr($phone, 1);
}elseif(preg_match('/^\+639\d{9}$/', $phone)){
    $phone = substr($phone, 1);
}

if(!preg_match('/^639\d{9}$/', $phone)){
    respond(false, "Invalid registered Philippine mobile number.");
}

if(empty($IPROG_SMS_API_TOKEN) || empty($IPROG_SMS_BASE_URL)){
    respond(false, "IPROG API configuration is incomplete.");
}

$action = $_POST['action'] ?? '';

if($action === 'send'){

    $last_sent = (int)($_SESSION['phone_otp_last_sent'] ?? 0);

    if(time() - $last_sent < 60){
        $remaining = 60 - (time() - $last_sent);
        respond(false, "Please wait {$remaining} seconds before requesting another OTP.");
    }

    $payload = [
        "api_token" => $IPROG_SMS_API_TOKEN,
        "phone_number" => $phone
    ];

    $ch = curl_init(rtrim($IPROG_SMS_BASE_URL, '/') . "/otp/send_otp");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if($response === false){
        error_log("IPROG SEND ERROR: " . $curl_error);
        respond(false, "Unable to connect to IPROG SMS.");
    }

    $data = json_decode($response, true);

    if(
        $http_code >= 200 &&
        $http_code < 300 &&
        is_array($data) &&
        ($data['status'] ?? '') === 'success'
    ){
        $_SESSION['phone_otp_last_sent'] = time();
        $_SESSION['phone_otp_pending'] = $phone;

        respond(true, "OTP sent successfully to your registered mobile number.");
    }

    error_log("IPROG SEND RESPONSE: " . $response);
    respond(false, $data['message'] ?? "Unable to send OTP.");
}

if($action === 'verify'){

    $otp = trim($_POST['otp'] ?? '');

    if(!preg_match('/^\d{6}$/', $otp)){
        respond(false, "Please enter the 6-digit OTP.");
    }

    if(
        empty($_SESSION['phone_otp_pending']) ||
        $_SESSION['phone_otp_pending'] !== $phone
    ){
        respond(false, "Please request an OTP first.");
    }

    $payload = [
        "api_token" => $IPROG_SMS_API_TOKEN,
        "phone_number" => $phone,
        "otp" => $otp
    ];

    $ch = curl_init(rtrim($IPROG_SMS_BASE_URL, '/') . "/otp/verify_otp");

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Accept: application/json"
        ],
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if($response === false){
        error_log("IPROG VERIFY ERROR: " . $curl_error);
        respond(false, "Unable to connect to IPROG SMS.");
    }

    $data = json_decode($response, true);

    if(
        $http_code >= 200 &&
        $http_code < 300 &&
        is_array($data) &&
        ($data['status'] ?? '') === 'success'
    ){

        $update = mysqli_prepare($conn, "
            UPDATE applicant_accounts
            SET phone_verified = 1,
                phone_verified_at = NOW()
            WHERE id = ?
            AND is_verified = 1
            AND phone_verified = 0
        ");

        mysqli_stmt_bind_param($update, "i", $applicant_id);
        $updated = mysqli_stmt_execute($update);
        mysqli_stmt_close($update);

        if(!$updated){
            respond(false, "Unable to save verification status.");
        }

        unset(
            $_SESSION['phone_otp_pending'],
            $_SESSION['phone_otp_last_sent']
        );

        respond(true, "Your mobile number has been successfully verified!");
    }

    error_log("IPROG VERIFY RESPONSE: " . $response);
    respond(false, $data['message'] ?? "Incorrect or expired OTP.");
}

respond(false, "Invalid action.");
