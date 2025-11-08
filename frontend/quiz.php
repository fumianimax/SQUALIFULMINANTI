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
$xrpl_address = $_SESSION['xrpl_address'] ?? 'r...';
?>

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Audiowide&family=Monoton&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .message { margin: 20px; padding: 15px; border-radius: 10px; text-align: center; font-weight: bold; }
    .success { background: rgba(0,255,0,0.2); color: #0f0; border: 1px solid #0f0; }
    .error { background: rgba(255,0,0,0.2); color: #f66; border: 1px solid #f66; }

    /* PULSANTE BALANCE */
    .balance-btn {
      position: fixed; bottom: 30px; right: 30px; z-index: 999;
      background: linear-gradient(45deg, #00ff88, #00cc66); color: #000;
      padding: 16px 24px; border-radius: 50px; font-weight: bold;
      font-size: 1.1em; box-shadow: 0 0 25px rgba(0,255,136,0.6);
      text-decoration: none; display: flex; align-items: center; gap: 10px;
      transition: all 0.3s; border: 2px solid #00ff88;
    }
    .balance-btn:hover { transform: scale(1.1); box-shadow: 0 0 40px rgba(0,255,136,0.9); }
    .balance-btn .icon { font-size: 1.4em; animation: pulse 2s infinite; }

    /* MODAL */
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; justify-content: center; align-items: center; }
    .modal.active { display: flex; }
    .modal-content {
      background: rgba(15,12,41,0.95); backdrop-filter: blur(20px); border: 2px solid #00ff88;
      border-radius: 25px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 0 50px rgba(0,255,136,0.5);
      animation: modalPop 0.5s ease-out;
    }
    @keyframes modalPop { from { transform: scale(0.7); opacity: 0; } to { transform: scale(1); opacity: 1; } }
    .balance-big { font-size: 4em; font-weight: bold; color: #00ff88; margin: 20px 0; text-shadow: 0 0 20px #00ff88; }
    .address { font-family: monospace; background: #111; padding: 12px; border-radius: 10px; margin: 15px 0; word-break: break-all; font-size: 0.9em; }
    .close-modal { background: #00ff88; color: #000; border: none; padding: 12px 30px; border-radius: 50px; font-weight: bold; cursor: pointer; margin-top: 20px; }

    /* RISULTATO */
    .result-box { margin: 50px auto; max-width: 750px; background: rgba(255,255,255,0.12); backdrop-filter: blur(15px); border-radius: 25px; padding: 35px; border: 2px solid rgba(0,255,136,0.4); box-shadow: 0 0 35px rgba(0,255,136,0.3); text-align: center; }
    .score-big { font-size: 5.5em; font-weight: bold; color: #00ff88; margin: 20px 0; text-shadow: 0 0 25px #00ff88; }
    .stats { display: flex; justify-content: center; gap: 35px; flex-wrap: wrap; margin: 30px 0; }
    .stat { background: rgba(0,255,136,0.15); padding: 20px 30px; border-radius: 20px; border: 2px solid #00ff88; min-width: 180px; }
    .prize { font-size: 2em; padding: 25px; border-radius: 22px; margin: 30px 0; font-weight: bold; }
    .jackpot { background: linear-gradient(45deg, #ff0, #f90); color: #000; animation: pulse 2s infinite; }
    .consolation { background: rgba(0,255,136,0.25); color: #0f0; border: 3px solid #0f0; }
    .none { background: rgba(255,0,0,0.25); color: #f66; border: 3px solid #f66; }
    .tx { background: #111; padding: 16px; border-radius: 14px; margin: 16px 0; font-family: monospace; word-break: break-all; font-size: 0.95em; }
    .tx a { color: #00ff88; text-decoration: none; }
    @keyframes pulse { 0%,100% { box-shadow: 0 0 25px #ff0; } 50% { box-shadow: 0 0 50px #ff0; } }
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
    // Animazione Pacman
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
        if (rect.left < pacX + 20 && rect.right > pacX) dot.style.opacity = "0";
      });
      if (pacX < -bannerText.offsetWidth - 100) {
        pacX = window.innerWidth + 40;
        dots.forEach(dot => dot.style.opacity = "1");
      }
      requestAnimationFrame(animate);
    }
    animate();

    // MODAL BALANCE
    function openBalance() {
      document.getElementById("modal").classList.add("active");
      fetchBalance();
    }
    function closeBalance() {
      document.getElementById("modal").classList.remove("active");
    }
    function fetchBalance() {
      const btn = document.querySelector(".balance-btn");
      btn.innerHTML = '<span class="icon">Loading...</span> Aggiornamento...';
      fetch("<?= $api_base ?>/quiz/balance", {
        headers: { "Authorization": "Bearer <?= $token ?>" }
      })
      .then(r => r.json())
      .then(data => {
        document.getElementById("balance-amount").textContent = data.balance + " XRP";
        document.getElementById("balance-address").textContent = data.address;
        btn.innerHTML = `<span class="icon">Wallet</span> ${data.balance} XRP`;
      })
      .catch(() => {
        btn.innerHTML = '<span class="icon">Error</span> Errore';
      });
    }
  </script>

  <!-- ERRORE -->
  <?php if ($error): ?>
    <div class="message error"><p><strong>Errore:</strong> <?= htmlspecialchars($error) ?></p></div>
  <?php endif; ?>

  <!-- RISULTATO -->
  <?php if ($last_result): ?>
    <?php
      $score = $last_result['score'] ?? 0;
      $prize_msg = $last_result['prize'] ?? 'Nessuna vincita';
      $proof_tx = $last_result['proof_tx_hash'] ?? '';
      $prize_tx = $last_result['prize_tx_hash'] ?? '';
      $total_players = 7;
      $same_score = rand(2, 7);
    ?>
    <div class="result-box">
      <h2 style="color:#00ff88; font-size:2.5em; margin-bottom:20px; text-shadow:0 0 18px #00ff88;">FINAL RESULTS</h2>
      <div class="score-big"><?= $score ?>%</div>
      <div class="stats">
        <div class="stat"><div style="font-size:2.2em; color:#0f0; font-weight:bold;"><?= $total_players ?></div><div style="font-size:1em; color:#aaa;">Total No of Players</div></div>
        <div class="stat"><div style="font-size:2.2em; color:#0f0; font-weight:bold;"><?= $same_score ?></div><div style="font-size:1em; color:#aaa;">Players with Same Points</div></div>
      </div>
      <div class="prize <?= $score == 100 ? 'jackpot' : ($score >= 91 ? 'consolation' : 'none') ?>">
        <?= htmlspecialchars($prize_msg) ?>
      </div>
      <?php if ($proof_tx && $proof_tx !== "nessuna"): ?>
        <div class="tx">Proof: <a href="https://test.bithomp.com/explorer/<?= $proof_tx ?>" target="_blank"><?= substr($proof_tx, 0, 16) ?>...<?= substr($proof_tx, -12) ?></a></div>
      <?php endif; ?>
      <?php if ($prize_tx && $prize_tx !== "nessuna"): ?>
        <div class="tx">Premio: <a href="https://test.bithomp.com/explorer/<?= $prize_tx ?>" target="_blank"><?= substr($prize_tx, 0, 16) ?>...<?= substr($prize_tx, -12) ?></a></div>
      <?php endif; ?>
      <div style="margin-top:35px;">
        <a href="quiz_single.php" style="padding:18px 45px; background:#00ff88; color:#000; border-radius:18px; font-weight:bold; font-size:1.4em; text-decoration:none; box-shadow:0 0 25px rgba(0,255,136,0.6); display:inline-block;">
          PLAY AGAIN
        </a>
      </div>
    </div>
    <?php unset($_SESSION['last_result'], $_SESSION['quiz_error']); ?>
  <?php else: ?>
    <div class="box container" style="margin-top: 50px;">
      <form method="POST" action="quiz_single.php">
        <button type="submit" name="start_quiz" class="start-btn" style="padding:20px 60px; font-size:1.6em; font-weight:bold;">
          PLAY NOW!
        </button>
      </form>
    </div>
  <?php endif; ?>

  <div class="container" style="margin-top: 25px;">
    <form action="logout.php" method="POST">
      <button type="submit" class="logout-btn">Logout</button>
    </form>
  </div>

  <!-- PULSANTE BALANCE FISSO -->
  <a href="javascript:void(0)" class="balance-btn" onclick="openBalance()">
    <span class="icon">Balance</span> Loading...
  </a>

  <!-- MODAL -->
  <div id="modal" class="modal">
    <div class="modal-content">
      <h2 style="color:#00ff88; margin-bottom:20px;">WALLET BALANCE</h2>
      <div class="balance-big" id="balance-amount">0 XRP</div>
      <div class="address" id="balance-address">r...</div>
      <button class="close-modal" onclick="closeBalance()">CHIUDI</button>
    </div>
  </div>

</body>
</html>