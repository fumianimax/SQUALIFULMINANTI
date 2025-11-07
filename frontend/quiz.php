<?php
session_start();
$api_base = "http://127.0.0.1:8000";

if (!isset($_SESSION["token"])) {
    header("Location: index.php");
    exit();
}

$token = $_SESSION["token"];
$last_result = $_SESSION['last_result'] ?? null;
$error = $_SESSION['quiz_error'] ?? null;

// Pulisci sessione
unset($_SESSION['last_result'], $_SESSION['quiz_error']);
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

    <!-- ERRORE -->
    <?php if ($error): ?>
      <div class="error-box">
        <p><strong>Errore:</strong> <?= htmlspecialchars($error) ?></p>
      </div>
    <?php endif; ?>

    <!-- PULSANTE INIZIA QUIZ -->
    <div class="box container">
    <form method="POST" action="quiz_single.php">
      <button type="submit" name="start_quiz" class="start-btn">PLAY NOW!</button>
    </form>
    </div>
    <div class="container">
    <form action="logout.php" method="POST">
      <button type="submit" class="logout-btn">Logout</button>
    </form>
    </div>
  </div>
</body>
</html>