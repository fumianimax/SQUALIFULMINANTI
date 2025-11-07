<?php
session_start();
$api_base = "http://127.0.0.1:8000";

if (!isset($_SESSION["token"])) {
    header("Location: index.php");
    exit();
}

$token = $_SESSION["token"];
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["start_quiz"])) {
        $options = [
            "http" => [
                "header"  => "Authorization: Bearer $token\r\n",
                "method"  => "GET"
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents("$api_base/quiz/start", false, $context);
        $message = "Quiz: " . $result;
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz - Area Quiz</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Benvenuto nel quiz XRPL!</h1>
  <form method="POST">
    <button type="submit" name="start_quiz">Inizia Quiz</button>
  </form>

  <p><?= htmlspecialchars($message) ?></p>

  <form action="logout.php" method="POST">
    <button type="submit">Logout</button>
  </form>
</body>
</html>
