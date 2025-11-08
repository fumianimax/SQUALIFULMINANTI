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

  <!-- ERRORE QUIZ -->
  <?php if (isset($_SESSION['quiz_error'])): ?>
    <p class="message error">
      <?= htmlspecialchars($_SESSION['quiz_error']) ?>
    </p>
    <?php unset($_SESSION['quiz_error']); ?>
  <?php endif; ?>

  <!-- RISULTATO QUIZ -->
  <?php if (isset($_SESSION['last_result'])): ?>
    <?php
      $res = $_SESSION['last_result'];
      $score = $res['score'] ?? 0;
      $prize = $res['prize'] ?? 'Nessuna vincita';
      $proof_tx = $res['proof_tx_hash'] ?? '';
      $prize_tx = $res['prize_tx_hash'] ?? '';

      $total_players = 7;
      $same_score = rand(1, 7);
    ?>
    <div style="margin: 50px auto; max-width: 650px; background: rgba(255,255,255,0.12); backdrop-filter: blur(15px); border-radius: 25px; padding: 30px; border: 2px solid rgba(0,255,136,0.4); box-shadow: 0 0 30px rgba(0,255,136,0.3); text-align: center; font-family: 'Poppins', sans-serif;">

      <h2 style="color: #00ff88; font-size: 2.2em; margin-bottom: 20px; text-shadow: 0 0 15px #00ff88;">
        RISULTATO FINALE
      </h2>

      <div style="font-size: 4.5em; font-weight: bold; color: #00ff88; margin: 20px 0; text-shadow: 0 0 20px #00ff88;">
        <?= $score ?>%
      </div>

      <div style="display: flex; justify-content: center; gap: 25px; flex-wrap: wrap; margin: 25px 0;">
        <div style="background: rgba(0,255,136,0.15); padding: 15px 25px; border-radius: 15px; border: 2px solid #00ff88; min-width: 160px;">
          <div style="font-size: 1.8em; font-weight: bold; color: #0f0;"><?= $total_players ?></div>
          <div style="font-size: 0.9em; color: #aaa;">Partecipanti totali</div>
        </div>
        <div style="background: rgba(0,255,136,0.15); padding: 15px 25px; border-radius: 15px; border: 2px solid #00ff88; min-width: 160px;">
          <div style="font-size: 1.8em; font-weight: bold; color: #0f0;"><?= $same_score ?></div>
          <div style="font-size: 0.9em; color: #aaa;">Stesso punteggio</div>
        </div>
      </div>

      <div style="font-size: 1.8em; padding: 20px; border-radius: 18px; margin: 25px 0; font-weight: bold; 
        <?= $score == 100 ? 'background: linear-gradient(45deg, #ff0, #f90); color: #000; animation: pulse 2s infinite;' : 
           ($score >= 90 ? 'background: rgba(0,255,136,0.25); color: #0f0; border: 3px solid #0f0;' : 
           'background: rgba(255,0,0,0.25); color: #f66; border: 3px solid #f66;') ?>">
        <?= htmlspecialchars($prize) ?>
      </div>

      <?php if ($proof_tx && $proof_tx !== "nessuna" && $proof_tx !== "proof_error"): ?>
        <div style="background: #111; padding: 15px; border-radius: 12px; margin: 15px 0; font-family: monospace; word-break: break-all; font-size: 0.9em;">
          Proof inviato su XRPL!<br>
          <a href="https://test.bithomp.com/explorer/<?= $proof_tx ?>" target="_blank" style="color:#00ff88; text-decoration:none;">
            <?= substr($proof_tx, 0, 14) ?>...<?= substr($proof_tx, -10) ?>
          </a>
        </div>
      <?php endif; ?>

      <?php if ($prize_tx && $prize_tx !== "nessuna" && $prize_tx !== "tx_error"): ?>
        <div style="background: #111; padding: 15px; border-radius: 12px; margin: 15px 0; font-family: monospace; word-break: break-all; font-size: 0.9em;">
          Premio inviato!<br>
          <a href="https://test.bithomp.com/explorer/<?= $prize_tx ?>" target="_blank" style="color:#00ff88; text-decoration:none;">
            <?= substr($prize_tx, 0, 14) ?>...<?= substr($prize_tx, -10) ?>
          </a>
        </div>
      <?php endif; ?>

      <div style="margin-top: 30px;">
        <a href="quiz.php" style="display: inline-block; padding: 15px 35px; background: #00ff88; color: #000; text-decoration: none; border-radius: 15px; font-weight: bold; font-size: 1.2em; box-shadow: 0 0 20px rgba(0,255,136,0.5);">
          GIOCA DI NUOVO
        </a>
      </div>
    </div>

    <?php 
      unset($_SESSION['last_result']);
      echo "<style>@keyframes pulse { 0%,100% { box-shadow: 0 0 20px #ff0; } 50% { box-shadow: 0 0 40px #ff0; } }</style>";
    ?>
  <?php endif; ?>

</body>
</html>