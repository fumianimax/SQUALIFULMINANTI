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
  <title>XRPL Quiz</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
    .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    h1 { text-align: center; color: #0066cc; margin-bottom: 30px; font-size: 2.2em; }
    .result-box { background: #e8f5e9; border: 2px solid #4caf50; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center; }
    .error-box { background: #ffebee; border: 2px solid #d32f2f; padding: 20px; border-radius: 12px; margin: 20px 0; color: #d32f2f; text-align: center; }
    .answered { font-size: 24px; font-weight: bold; color: #0066cc; margin: 10px 0; }
    .score { font-size: 32px; font-weight: bold; color: #2e7d32; }
    button { display: block; width: 100%; padding: 16px; font-size: 20px; background: #0066cc; color: white; border: none; border-radius: 10px; cursor: pointer; margin-top: 20px; font-weight: bold; }
    button:hover { background: #004999; }
    .start-btn { background: #4caf50; }
    .start-btn:hover { background: #388e3c; }
    .logout-btn { background: #666; margin-top: 10px; }
    .logout-btn:hover { background: #444; }
  </style>
</head>
<body>
  <div class="container">
    <h1>XRPL Quiz</h1>

    <!-- RISULTATO PRECEDENTE -->
    <?php if ($last_result): ?>
      <div class="result-box">
        <h3>Risultato Quiz</h3>
        <?php if ($last_result['timeout']): ?>
          <p style="color:red; font-weight:bold; font-size:20px;">Tempo scaduto!</p>
        <?php endif; ?>
        <p class="answered">Risposte date: <?= $last_result['answered'] ?>/<?= $last_result['total'] ?></p>
        <p class="score">Punteggio: <?= $last_result['score'] ?>%</p>
        <?php if ($last_result['all_correct'] && $last_result['answered'] == $last_result['total']): ?>
          <p style="color:green; font-weight:bold;">Perfetto! Premio XRP inviato!</p>
          <p>Transazione: <code><?= $last_result['tx'] ?></code></p>
          <p><a href="https://test.bithomp.com/explorer/<?= $last_result['tx'] ?>" target="_blank">Vedi su Bithomp</a></p>
        <?php else: ?>
          <p>Le domande non risposte sono state contate come sbagliate.</p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- ERRORE -->
    <?php if ($error): ?>
      <div class="error-box">
        <p><strong>Errore:</strong> <?= htmlspecialchars($error) ?></p>
      </div>
    <?php endif; ?>

    <!-- PULSANTE INIZIA QUIZ -->
    <form method="POST" action="quiz_single.php">
      <button type="submit" name="start_quiz" class="start-btn">Inizia Quiz (5 min)</button>
    </form>

    <hr>
    <form action="logout.php" method="POST">
      <button type="submit" class="logout-btn">Logout</button>
    </form>
  </div>
</body>
</html>