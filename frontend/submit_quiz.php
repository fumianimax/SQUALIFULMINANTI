<?php
session_start();
if (!isset($_SESSION["token"])) {
    header("Location: index.php");
    exit();
}

$api_base = "http://127.0.0.1:8000";
$token = $_SESSION["token"];
$quiz_id = $_POST["quiz_id"] ?? null;

// DEBUG
error_log("submit_quiz.php - quiz_id: " . ($quiz_id ?? 'NULL'));

if (!$quiz_id) {
    $_SESSION['quiz_error'] = "Quiz ID missing. Retry.";
    header("Location: index.php");
    exit();
}

$ch = curl_init("$api_base/quiz/submit");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["quiz_id" => (int)$quiz_id]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

error_log("submit_quiz.php - HTTP: $http_code, Response: $response");

if ($http_code == 200) {
    $result = json_decode($response, true);
    $_SESSION['last_result'] = $result;
    $_SESSION['show_result'] = true;
} else {
    $err = json_decode($response, true);
    $_SESSION['quiz_error'] = $err['detail'] ?? "Errore server";
}

header("Location: index.php");
exit();
?>