<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $data = [
        "username" => $username,
        "password" => $password
    ];

    $ch = curl_init("http://127.0.0.1:8000/auth/register");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true); // importantissimo!
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    $response = curl_exec($ch);

    if(curl_errno($ch)) {
        echo "Curl error: " . curl_error($ch);
    }

    curl_close($ch);

    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
}
?>

<form method="POST">
    <input type="text" name="username" placeholder="Username"><br>
    <input type="password" name="password" placeholder="Password"><br>
    <button type="submit">Register</button>
</form>
