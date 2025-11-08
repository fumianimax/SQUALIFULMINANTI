<?php
session_start();

$show_message_before = $_SESSION['show_message_before'] ?? false;
$show_message_after = $_SESSION['show_message_after'] ?? false;
$message_before = $_SESSION['quiz_message_before'] ?? '';
$message_after = $_SESSION['quiz_message_after'] ?? '';
$api_base = "http://127.0.0.1:8000";

if (!isset($_SESSION["token"])) {
    header("Location: index.php");
    exit();
}

// PULISCI SOLO ALL'INIZIO
if (isset($_POST["start_quiz"]) || !isset($_SESSION['current_quiz'])) {
    unset($_SESSION['quiz_message_after'], $_SESSION['show_message_after'], 
          $_SESSION['message_duration'], $_SESSION['final_message_ready']);
}

$token = $_SESSION["token"];
$quiz_data = $_SESSION['current_quiz'] ?? null;
$current_index = $_SESSION['current_question'] ?? 0;
$message_after = $_SESSION['quiz_message_after'] ?? "";
$show_message_after = $_SESSION['show_message_after'] ?? false;
$message_duration = $_SESSION['message_duration'] ?? 5000;
$final_message_ready = $_SESSION['final_message_ready'] ?? false;

// --- AVVIO QUIZ ---
if (isset($_POST["start_quiz"])) {
    $ch = curl_init("$api_base/quiz/start");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $data = json_decode($response, true);
        $_SESSION['current_quiz'] = $data;
        $_SESSION['current_question'] = 0;
        $_SESSION['answers'] = [];
        header("Location: quiz_single.php");
        exit();
    }
}

// --- INVIO RISPOSTA ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["submit_answer"]) || isset($_POST["timeout"]))) {
    $choice = $_POST["choice"] ?? "NESSUNA_RISPOSTA";
    $qid = (int)$_POST["qid"];
    $quiz_id = (int)$_POST["quiz_id"];

    // Invia risposta
    $ch = curl_init("$api_base/quiz/answer");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["quiz_id" => $quiz_id, "question_id" => $qid, "choice" => $choice]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_exec($ch);
    curl_close($ch);

    $_SESSION['answers'][$qid] = $choice;

    // Incrementa normalmente
    $next = ($current_index + 1);
    $_SESSION['current_question'] = $next;

    // MESSAGGI DOPO RISPOSTA
    if ($next == 7) {
        $_SESSION['quiz_message_after'] = "BLOCKCHAIN IN PILLS Blockchain is a distributed, decentralised, and immutable digital ledger that records transactions in blocks chronologically linked together in a chain. This technology allows data to be stored and shared in a transparent and secure way. Transactions are cryptographically protected and immutable.";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 8000;
    }
    elseif ($next == 8) {
        $_SESSION['quiz_message_after'] = "CORRECT ANSWER: Banking, Financial Services and Insurance. <br> Indeed, BFSI generated approximately 38.7% of the global demand for blockchain solutions in 2024. \nBLOCKCHAIN IN PILLS \nThere are four main types of blockchains that can read/write data: public (fully open and decentralised), private (controlled by a single organisation), consortium (controlled by a group of organisations rather than a single entity) and 
hybrid (combination of public and private blockchains).";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 8000;
    }
    elseif ($next == 9) {
        $_SESSION['quiz_message_after'] = "CORRECT ANSWER: Consortium. Consortium blockchains allow shared control among different banks and let transaction details remain visible only to the involved parties. \nBLOCKCHAIN IN PILLS\nBlockchain nodes are connection points in a blockchain network that receive, store, verify, and transmit transaction data.";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 8000;
    }
    elseif ($next == 10) {
        $_SESSION['quiz_message_after'] = "CORRECT ANSWER: Selector node. The main types of nodes are: full nodes (storing the entire blockchain and validating every transaction), light nodes (storing only block headers) and mining/validator nodes (creating and proposing new blocks).";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 8000;
        $_SESSION['final_message_ready'] = true;  // <-- Segnala: mostra messaggio e poi submit
    }

    header("Location: quiz_single.php");
    exit();
}

// DOPO L'ULTIMO MESSAGGIO → SUBMIT FINALE
if ($final_message_ready && !$show_message_after) {
    $quiz_id = $quiz_data['quiz_id'];
    $ch = curl_init("$api_base/quiz/submit");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["quiz_id" => $quiz_id]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    $_SESSION['last_result'] = $result;

    // PULISCI TUTTO
    unset($_SESSION['current_quiz'], $_SESSION['current_question'], $_SESSION['answers'], $_SESSION['final_message_ready']);
    header("Location: quiz.php");
    exit();
}
?>

<!-- <!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz</title>
  <style>
    body { font-family: 'Segoe UI', sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
    .container { max-width: 700px; margin: 0 auto; background: white; border-radius: 16px; padding: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1);gk; text-align: center; }
    h1 { color: #0066cc; font-size: 2em; }
    .timer { font-size: 36px; font-weight: bold; color: #d32f2f; margin: 20px 0; padding: 10px; background: #ffebee; border-radius: 8px; }
    .question { background: #f9f9ff; padding: 25px; margin: 20px 0; border-radius: 12px; border-left: 6px solid #0066cc; font-size: 1.3em; }
    .options label { display: block; margin: 12px 0; padding: 14px; background: #e3f2fd; border-radius: 8px; cursor: pointer; transition: 0.2s; font-size: 1.1em; }
    .options label:hover { background: #bbdefb; }
    .options input[type="radio"] { margin-right: 12px; }
    .message { background: #fff3e0; color: #e65100; padding: 20px; border-radius: 12px; margin: 20px 0; font-weight: bold; font-size: 1.3em; animation: fadeIn 0.5s; white-space: pre-line; }
    .progress { height: 10px; background: #e0e0e0; border-radius: 5px; margin: 20px 0; }
    .progress-bar { height: 100%; background: #0066cc; border-radius: 5px; width: 0%; transition: width 0.3s; }
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
  </style>
</head>
<body>
  <div class="container">
    <h1>Question <?= min($current_index + 1, 10) ?> / 10</h1>
    <div class="progress"><div class="progress-bar" id="progress"></div></div>

    <?php if ($show_message_after && $message_after): ?>
      <div class="message"><?= nl2br(htmlspecialchars($message_after)) ?></div>
      <script>
        setTimeout(() => {
          window.location.href = "quiz_single.php";
        }, <?= $message_duration ?>);
      </script>

    <?php else: ?>
      <?php if ($quiz_data && $current_index < 10): ?>
        <?php $q = $quiz_data['quiz'][$current_index]; ?>
        <div class="timer" id="timer">1:15</div>
        <div class="question">
          <p><strong><?= htmlspecialchars($q['question']) ?></strong></p>
        </div>

        <form method="POST" id="answer_form">
          <input type="hidden" name="qid" value="<?= $q['id'] ?>">
          <input type="hidden" name="quiz_id" value="<?= $quiz_data['quiz_id'] ?>">
          <input type="hidden" name="choice" value="NESSUNA_RISPOSTA" id="hidden_choice">
          <input type="hidden" name="submit_answer" value="1">
          <div class="options">
            <?php foreach ($q['options'] as $opt): ?>
              <label>
                <input type="radio" name="choice" value="<?= htmlspecialchars($opt) ?>" onchange="document.getElementById('hidden_choice').value = this.value;">
                <?= htmlspecialchars($opt) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </form>

        <script>
          const timePerQuestion = <?= $quiz_data['time_per_question'] ?>;
          let timeLeft = timePerQuestion;
          let answered = false;

          function updateTimer() {
            if (answered) return;
            const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const secs = String(timeLeft % 60).padStart(2, '0');
            document.getElementById('timer').textContent = `${mins}:${secs}`;
            document.getElementById('progress').style.width = `${((timePerQuestion - timeLeft) / timePerQuestion) * 100}%`;
            if (timeLeft <= 0) {
              answered = true;
              document.getElementById('hidden_choice').value = "NESSUNA_RISPOSTA";
              document.getElementById('answer_form').submit();
            }
            timeLeft--;
          }

          setInterval(updateTimer, 1000);
          updateTimer();

          document.getElementById('answer_form').addEventListener('submit', function(e) {
            if (answered) e.preventDefault();
            answered = true;
          });
        </script>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <?php
  if ($show_message_after) {
      unset($_SESSION['quiz_message_after'], $_SESSION['show_message_after'], $_SESSION['message_duration']);
  }
  ?>
</body>
</html> -->

<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>XRPL Quiz</title>
  <link rel="stylesheet" href="quiz.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Audiowide&family=Monoton&display=swap" rel="stylesheet">
</head>
<body>
  <div class="container">
    <h1>Question [<?= $current_index + 1 ?> / <?= count($quiz_data['quiz'] ?? []) ?>]</h1>
    <div class="progress"><div class="progress-bar" id="progress"></div></div>

    <!-- MESSAGGIO DOPO RISPOSTA (5 sec) -->
    <?php if ($show_message_after && $message_after): ?>
      <div class="message"><?= htmlspecialchars($message_after) ?></div>
      <script>
        setTimeout(() => {
          window.location.href = "quiz_single.php";
        }, <?= $message_duration ?>);
      </script>

    <!-- MESSAGGIO PRIMA DELLA DOMANDA (5 sec) -->
    <?php elseif ($show_message_before && $message_before): ?>
      <div class="message"><?= htmlspecialchars($message_before) ?></div>
      <script>
        setTimeout(() => {
          window.location.href = "quiz_single.php";
        }, 5000);
      </script>

    <!-- DOMANDA NORMALE -->
    <?php else: ?>
      <?php if ($quiz_data && $current_index < count($quiz_data['quiz'])): ?>
        <?php $q = $quiz_data['quiz'][$current_index]; ?>
        <div class="timer" id="timer">1:15</div>
        <div class="question">
          <p><strong><?= htmlspecialchars($q['question']) ?></strong></p>
        </div>

        <form method="POST" id="answer_form">
          <input type="hidden" name="qid" value="<?= $q['id'] ?>">
          <input type="hidden" name="quiz_id" value="<?= $quiz_data['quiz_id'] ?>">
          <input type="hidden" name="choice" value="NESSUNA_RISPOSTA" id="hidden_choice">
          <input type="hidden" name="submit_answer" value="1">
          <div class="options">
            <?php foreach ($q['options'] as $index => $opt): ?>
              <input type="radio" id="r<?= $index ?>" name="choice" value="<?= htmlspecialchars($opt) ?>" onchange="document.getElementById('hidden_choice').value = this.value;">
              <label for="r<?= $index ?>"><?= htmlspecialchars($opt) ?></label>
            <?php endforeach; ?>
          </div>

        <input type="hidden" id="hidden_choice" name="hidden_choice">

        </form>

        <script>
          const timePerQuestion = <?= $quiz_data['time_per_question'] ?>;
          let timeLeft = timePerQuestion;

          function updateTimer() {
            const mins = String(Math.floor(timeLeft / 60)).padStart(2, '0');
            const secs = String(timeLeft % 60).padStart(2, '0');
            document.getElementById('timer').textContent = `${mins}:${secs}`;
            document.getElementById('progress').style.width = `${((timePerQuestion - timeLeft) / timePerQuestion) * 100}%`;

            if (timeLeft <= 0) {
              document.getElementById('answer_form').submit();
              return;
            }
            timeLeft--;
          }

          setInterval(updateTimer, 1000);
          updateTimer();

          document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
              e.preventDefault();
              document.getElementById('answer_form').submit();
            }
          });
        </script>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>