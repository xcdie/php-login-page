<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mailer.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function jsonResponse($success, $message, $data = [])
{
    header('Content-Type: application/json');

    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data));

    exit;
}

function verifyCSRF()
{
    $token = $_POST['csrf_token'] ?? '';

    if (
        empty($token) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        jsonResponse(false, 'Invalid security token.');
    }
}

//logout

if (isset($_GET['logout'])) {

    $_SESSION = [];

    if (ini_get("session.use_cookies")) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"] ?? '',
            $params["secure"],
            $params["httponly"]
        );
    }

    session_destroy();

    header("Location: index.php");
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCSRF();

    $action = $_POST['action'] ?? '';

   //register
    if ($action === 'register') {

        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phonenumber'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $email === '' || $phone === '' || $password === '') {
            jsonResponse(false, 'All fields are required.');
        }

        if (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
            jsonResponse(
                false,
                'Username must be 3-50 characters and contain only letters, numbers and underscores.'
            );
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, 'Enter a valid email address.');
        }

        if (strlen($password) < 8) {
            jsonResponse(false, 'Password must contain at least 8 characters.');
        }

        $stmt = $pdo->prepare(
            "SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1"
        );

        $stmt->execute([$username, $email]);

        if ($stmt->fetch()) {
            jsonResponse(false, 'Username or email already exists.');
        }

        $otp = (string) random_int(100000, 999999);

        $_SESSION['pending_registration'] = [
            'username' => $username,
            'email' => $email,
            'phonenumber' => $phone,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'otp_hash' => password_hash($otp, PASSWORD_DEFAULT),
            'expires' => time() + 600,
            'attempts' => 0
        ];

        if (!sendOTP($email, $otp)) {
            unset($_SESSION['pending_registration']);

            jsonResponse(
                false,
                'Unable to send verification email.'
            );
         }
        if (!sendSMS($phone, $otp)){
            unset($_SESSION['pending_registration']);
            jsonResponse(
                false,
                'unable to send verification code to your phone number.'
            );

        }

        jsonResponse(
            true,
            'Verification code sent to your email.',
            ['step' => 'otp']
        );
    }

  //verify otp

    if ($action === 'verify_otp') {

        if (empty($_SESSION['pending_registration'])) {
            jsonResponse(false, 'No registration is waiting for verification.');
        }

        $registration = $_SESSION['pending_registration'];

        if (time() > $registration['expires']) {
            unset($_SESSION['pending_registration']);

            jsonResponse(false, 'The verification code has expired.');
        }

        if ($registration['attempts'] >= 5) {
            unset($_SESSION['pending_registration']);

            jsonResponse(false, 'Too many incorrect attempts.');
        }

        $otp = trim($_POST['otp'] ?? '');

        if (!preg_match('/^\d{6}$/', $otp)) {
            jsonResponse(false, 'Enter the 6-digit verification code.');
        }

        if (!password_verify($otp, $registration['otp_hash'])) {

            $_SESSION['pending_registration']['attempts']++;

            jsonResponse(false, 'Incorrect verification code.');
        }

        $stmt = $pdo->prepare(
            "INSERT INTO users (username, email, password)
             VALUES (?, ?, ?)"
        );

        try {

            $stmt->execute([
                $registration['username'],
                $registration['email'],
                $registration['password']
            ]);

        } catch (PDOException $e) {

            unset($_SESSION['pending_registration']);

            jsonResponse(false, 'Registration failed. Username or email may already exist.');
        }

        unset($_SESSION['pending_registration']);

        jsonResponse(
            true,
            'Registration successful. You can now log in.',
            ['step' => 'login']
        );
    }

  // LOGIN
    

    if ($action === 'login') {

        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($login === '' || $password === '') {
            jsonResponse(false, 'Enter your username/email and password.');
        }

        $stmt = $pdo->prepare(
            "SELECT id, username, email, password
             FROM users
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        $stmt->execute([$login, $login]);

        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            jsonResponse(false, 'Invalid username/email or password.');
        }

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];

        jsonResponse(
            true,
            'Login successful.',
            ['redirect' => 'dashboard']
        );
    }

    jsonResponse(false, 'Invalid request.');
}

// DASHBOARD

if (isset($_SESSION['user_id'])) {

    $stmt = $pdo->prepare(
        "SELECT username, email, created_at
         FROM users
         WHERE id = ?
         LIMIT 1"
    );

    $stmt->execute([$_SESSION['user_id']]);

    $user = $stmt->fetch();

    if (!$user) {
        session_destroy();
        header("Location: index.php");
        exit;
    }

    ?>

    <!DOCTYPE html>
    <html lang="en">

    <head>

        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Dashboard</title>

        <style>

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                font-family: Arial, sans-serif;
                background: #f4f6f8;
            }

            .navbar {
                background: #111827;
                color: white;
                padding: 18px 30px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .navbar a {
                color: white;
                text-decoration: none;
                background: #dc2626;
                padding: 10px 16px;
                border-radius: 6px;
            }

            .container {
                max-width: 800px;
                margin: 50px auto;
                padding: 20px;
            }

            .card {
                background: white;
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 5px 20px rgba(0,0,0,.08);
            }

            h1 {
                margin-top: 0;
            }

            .info {
                padding: 15px;
                background: #f3f4f6;
                margin: 10px 0;
                border-radius: 6px;
            }

        </style>

    </head>

    <body>

        <div class="navbar">

            <strong>Secure Login System</strong>

            <a href="?logout=1">Logout</a>

        </div>

        <div class="container">

            <div class="card">

                <h1>Welcome, <?= htmlspecialchars($user['username']) ?>!</h1>

                <p>You are successfully logged in.</p>

                <div class="info">
                    <strong>Username:</strong>
                    <?= htmlspecialchars($user['username']) ?>
                </div>

                <div class="info">
                    <strong>Email:</strong>
                    <?= htmlspecialchars($user['email']) ?>
                </div>
                <div
                    class = "info">
                    <strong>phonenumber:</strong>
                    <?= htmlspecialchars($user['phonenumber']) ?>
                </div>

                <div class="info">
                    <strong>Account created:</strong>
                    <?= htmlspecialchars($user['created_at']) ?>
                </div>

            </div>

        </div>

    </body>

    </html>

    <?php

    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Secure Login System</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #000000, #000000);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 430px;
            padding: 20px;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0,0,0,.25);
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
        }

        .tabs {
            display: flex;
            margin-bottom: 25px;
        }

        .tabs button {
            width: 50%;
            padding: 12px;
            border: none;
            background: #e5e7eb;
            cursor: pointer;
            font-weight: bold;
        }

        .tabs button.active {
            background: #2563eb;
            color: white;
        }

        .form {
            display: none;
        }

        .form.active {
            display: block;
        }

        label {
            display: block;
            margin-top: 15px;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: none;
            border-color: #2563eb;
        }

        button.submit {
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #2563eb;
            color: white;
            font-size: 16px;
            cursor: pointer;
        }

        button.submit:hover {
            background: #1d4ed8;
        }

        .message {
            display: none;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
        }

        .message.success {
            display: block;
            background: #dcfce7;
            color: #166534;
        }

        .message.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }

        .otp {
            text-align: center;
            letter-spacing: 8px;
            font-size: 24px;
        }

    </style>

</head>

<body>

<div class="container">

    <div class="card">

        <h1>Secure Login</h1>

        <div id="message" class="message"></div>

        <div class="tabs">

            <button
                id="loginTab"
                class="active"
                onclick="showForm('login')"
            >
                Login
            </button>

            <button
                id="registerTab"
                onclick="showForm('register')"
            >
                Register
            </button>

        </div>

        <!-- LOGIN -->

        <form id="loginForm" class="form active">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="login"
            >

            <label>Username or Email</label>

            <input
                type="text"
                name="login"
                required
            >

            <label>Password</label>

            <input
                type="password"
                name="password"
                required
            >

            <button class="submit" type="submit">
                Login
            </button>

        </form>

        <!-- REGISTER -->

        <form id="registerForm" class="form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="register"
            >

            <label>Username</label>

            <input
                type="text"
                name="username"
                minlength="3"
                maxlength="50"
                required
            >

            <label>Email</label>

            <input
                type="email"
                name="email"
                required
            >
            <label>Phone Number</label>
            <input
                type="text"
                name="phonenumber"
                pattern="\+?\d{10,15}"
                required>

            <label>Password</label>

            <input
                type="password"
                name="password"
                minlength="8"
                required
            >

            <button class="submit" type="submit">
                Create Account
            </button>

        </form>

        <!-- OTP -->

        <form id="otpForm" class="form">

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>"
            >

            <input
                type="hidden"
                name="action"
                value="verify_otp"
            >

            <label>Verification Code</label>

            <input
                class="otp"
                type="text"
                name="otp"
                maxlength="6"
                pattern="[0-9]{6}"
                required
            >

            <button class="submit" type="submit">
                Verify Account
            </button>

        </form>

    </div>

</div>

<script>

const message = document.getElementById("message");

function showMessage(text, type) {

    message.textContent = text;

    message.className = "message " + type;
}

function showForm(form) {

    document.getElementById("loginForm").classList.remove("active");
    document.getElementById("registerForm").classList.remove("active");
    document.getElementById("otpForm").classList.remove("active");

    document.getElementById("loginTab").classList.remove("active");
    document.getElementById("registerTab").classList.remove("active");

    if (form === "login") {

        document.getElementById("loginForm").classList.add("active");
        document.getElementById("loginTab").classList.add("active");

    }

    if (form === "register") {

        document.getElementById("registerForm").classList.add("active");
        document.getElementById("registerTab").classList.add("active");

    }

    if (form === "otp") {

        document.getElementById("otpForm").classList.add("active");

    }
}

async function submitForm(form) {

    const formData = new FormData(form);

    try {

        const response = await fetch("index.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        showMessage(
            data.message,
            data.success ? "success" : "error"
        );

        if (!data.success) {
            return;
        }

        if (data.step === "otp") {

            showForm("otp");

        }

        if (data.step === "login") {

            showForm("login");

        }

        if (data.redirect) {

            window.location.href = "index.php";

        }

    } catch (error) {

        showMessage(
            "Something went wrong. Check the server configuration.",
            "error"
        );

        console.error(error);
    }
}

document
    .getElementById("loginForm")
    .addEventListener("submit", function(e) {

        e.preventDefault();

        submitForm(this);

    });

document
    .getElementById("registerForm")
    .addEventListener("submit", function(e) {

        e.preventDefault();

        submitForm(this);

    });

document
    .getElementById("otpForm")
    .addEventListener("submit", function(e) {

        e.preventDefault();

        submitForm(this);

    });

</script>

</body>

</html>