<?php
$api_base = "http://127.0.0.1:8000";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["register"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $data = json_encode(["username" => $username, "password" => $password]);
        $options = [
            "http" => [
                "header"  => "Content-type: application/json\r\n",
                "method"  => "POST",
                "content" => $data
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents("$api_base/auth/register", false, $context);

        $message = "Registrazione: " . $result;
    }

    if (isset($_POST["login"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $data = json_encode(["username" => $username, "password" => $password]);
        $options = [
            "http" => [
                "header"  => "Content-type: application/json\r\n",
                "method"  => "POST",
                "content" => $data
            ]
        ];
        $context  = stream_context_create($options);
        $result = file_get_contents("$api_base/auth/login", false, $context);

        $json = json_decode($result, true);
        if (isset($json["access_token"])) {
            session_start();
            $_SESSION["token"] = $json["access_token"];
            header("Location: quiz.php");
            exit();
        } else {
            $message = "Errore di login: " . $result;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz - Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>XRPL Quiz</h1>

  <h2>Registrazione</h2>
  <form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="register">Registrati</button>
  </form>

  <h2>Login</h2>
  <form method="POST">
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" name="login">Login</button>
  </form>

  <p><?= htmlspecialchars($message) ?></p>
</body>
</html>
