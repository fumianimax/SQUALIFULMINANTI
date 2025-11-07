<?php
// OBBLIGATORIO: session_start() SEMPRE PRIMA
session_start();
$api_base = "http://127.0.0.1:8000";
$message = "";
$message_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- REGISTRAZIONE ---
    if (isset($_POST["register"])) {
        $username = trim($_POST["username"]);
        $password = substr($_POST["password"], 0, 72);

        $data = json_encode(["username" => $username, "password" => $password]);

        $ch = curl_init("$api_base/auth/register");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $json = json_decode($response, true);
            $message = "Registrazione OK! Indirizzo: " . $json['xrpl_address'];
            $message_class = "success";
        } else {
            $message = "Errore registrazione: " . htmlspecialchars($response);
            $message_class = "error";
        }
    }

    // --- LOGIN ---
    if (isset($_POST["login"])) {
        $username = trim($_POST["username"]);
        $password = substr($_POST["password"], 0, 72);

        $data = json_encode(["username" => $username, "password" => $password]);

        $ch = curl_init("$api_base/auth/login");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $json = json_decode($response, true);
            $_SESSION["token"] = $json["access_token"];
            $_SESSION["xrpl_address"] = $json["xrpl_address"] ?? "";
            header("Location: quiz.php");
            exit();
        } else {
            $message = "Login fallito: " . htmlspecialchars($response);
            $message_class = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz - Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Audiowide&family=Monoton&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .message { margin: 20px; padding: 15px; border-radius: 10px; text-align: center; font-weight: bold; }
    .success { background: rgba(0,255,0,0.2); color: #0f0; border: 1px solid #0f0; }
    .error { background: rgba(255,0,0,0.2); color: #f66; border: 1px solid #f66; }
  </style>
</head>
<body>
  <h1>XRPL QUIZ</h1>
  <div class="overlay"></div>

  <div class="banner">
    <div class="dots" id="dots"></div>
    <div class="pacman" id="pacman">
      <div class="pacman-mouth"></div>
    </div>
    <div class="banner-text" id="bannerText">
      Win up to 200 XRP! Learn Blockchain with XRPL! Compete with others!
    </div>
  </div>

  <script>
    const dotsContainer = document.getElementById("dots");
    const numDots = Math.ceil(window.innerWidth / 25);
    for (let i = 0; i < numDots; i++) {
      const dot = document.createElement("span");
      dot.classList.add("dot");
      dotsContainer.appendChild(dot);
    }

    const pacman = document.getElementById("pacman");
    const bannerText = document.getElementById("bannerText");
    const dots = document.querySelectorAll(".dot");
    let pacX = window.innerWidth + 40;
    const baseSpeed = 2.5;

    function animate() {
      pacX -= baseSpeed;
      pacman.style.left = pacX + "px";
      bannerText.style.left = (pacX + 60) + "px";

      dots.forEach(dot => {
        const rect = dot.getBoundingClientRect();
        if (rect.left < pacX + 20 && rect.right > pacX) {
          dot.style.opacity = "0";
        }
      });

      if (pacX < -bannerText.offsetWidth - 100) {
        pacX = window.innerWidth + 40;
        dots.forEach(dot => dot.style.opacity = "1");
      }
      requestAnimationFrame(animate);
    }
    animate();
  </script>

  <div class="container">
    <!-- REGISTRAZIONE -->
    <div class="box register-box">
      <h2>Sign-up</h2>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="register">Register</button>
      </form>
    </div>

    <!-- LOGIN -->
    <div class="box login-box">
      <h2>Sign-in</h2>
      <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit" name="login">Login</button>
      </form>
    </div>
  </div>

  <!-- MESSAGGIO -->
  <?php if ($message): ?>
    <p class="message <?= $message_class ?>">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>
</body>
</html>