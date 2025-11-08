<?php
session_start();
$api_base = "http://127.0.0.1:8000";

if (!isset($_SESSION["token"])) {
    header("Location: index.php");
    exit();
}

$token = $_SESSION["token"];
$quiz_data = $_SESSION['current_quiz'] ?? null;
$current_index = $_SESSION['current_question'] ?? 0;
$answers = $_SESSION['answers'] ?? [];
$message_before = $_SESSION['quiz_message_before'] ?? "";
$show_message_before = $_SESSION['show_message_before'] ?? false;
$message_after = $_SESSION['quiz_message_after'] ?? "";
$show_message_after = $_SESSION['show_message_after'] ?? false;
$message_duration = $_SESSION['message_duration'] ?? 5000;
$last_result = $_SESSION['last_result'] ?? null;
$error = $_SESSION['quiz_error'] ?? null;

// PULISCI SESSIONE
unset(
    $_SESSION['quiz_message_before'],
    $_SESSION['show_message_before'],
    $_SESSION['quiz_message_after'],
    $_SESSION['show_message_after'],
    $_SESSION['message_duration'],
    $_SESSION['quiz_error']
);

// --- AVVIO QUIZ ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["start_quiz"])) {
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
    } else {
        $_SESSION['quiz_error'] = "Errore: $http_code";
        header("Location: quiz.php");
        exit();
    }
}

// --- INVIO RISPOSTA (anche timeout) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && (isset($_POST["submit_answer"]) || isset($_POST["timeout"]))) {
    $choice = $_POST["choice"] ?? "NESSUNA_RISPOSTA";
    $qid = (int)$_POST["qid"];
    $quiz_id = (int)$_POST["quiz_id"];

    error_log("INVIO RISPOSTA: qid=$qid, quiz_id=$quiz_id, choice=$choice");

    // Invia al backend
    $data = ["quiz_id" => $quiz_id, "question_id" => $qid, "choice" => $choice];
    $ch = curl_init("$api_base/quiz/answer");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("RISPOSTA BACKEND: $http_code - $response");

    if ($http_code != 200) {
        $_SESSION['quiz_error'] = "Errore backend: $http_code - $response";
        header("Location: quiz.php");
        exit();
    }

    // Salva risposta
    $answers[$qid] = $choice;
    $_SESSION['answers'] = $answers;

    // Prossima domanda
    $current_index++;
    $_SESSION['current_question'] = $current_index;

    // MESSAGGIO DOPO LA RISPOSTA (ultime 3 domande)
    $just_answered = $current_index; // domanda appena completata
    if ($just_answered == 7) {
        $_SESSION['quiz_message_after'] = "introduzione alla domanda 8";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 5000;
    } elseif ($just_answered == 8) {
        $_SESSION['quiz_message_after'] = "descrizione della risposta alla domanda 8 e introduzione alla domanda 9!";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 5000;
    } elseif ($just_answered == 9) {
        $_SESSION['quiz_message_after'] = "descrizione della risposta alla domanda 9 e introduzione alla domanda 10!";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 5000;
    } elseif ($just_answered == 10) {
        $_SESSION['quiz_message_after'] = "descrizione della risposta alla domanda 10!";
        $_SESSION['show_message_after'] = true;
        $_SESSION['message_duration'] = 5000;
    }

    // FINE QUIZ
    if ($current_index >= count($quiz_data['quiz'])) {
        $payload = ["quiz_id" => $quiz_id];
        $ch = curl_init("$api_base/quiz/submit");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Content-Type: application/json"
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            $answered_count = count(array_filter($answers, fn($v) => $v !== "NESSUNA_RISPOSTA"));
            $_SESSION['last_result'] = [
                'score' => $result["score"] ?? 0,
                'answered' => $answered_count,
                'total' => count($quiz_data['quiz']),
                'all_correct' => $result["all_correct"] ?? false,
                'tx' => $result["tx_hash"] ?? "nessuna",
                'timeout' => false
            ];
        }
        unset($_SESSION['current_quiz'], $_SESSION['current_question'], $_SESSION['answers']);
        header("Location: quiz.php");
        exit();
    }

    header("Location: quiz_single.php");
    exit();
}
?>

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
    <h1>Answer [<?= $current_index + 1 ?> / <?= count($quiz_data['quiz'] ?? []) ?>]</h1>
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