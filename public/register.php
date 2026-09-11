<?php
// register.php
session_start();
require 'mailer.php'; 
require 'db.php';     

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email       = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $password    = $_POST['password'] ?? '';
    $username    = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $phonenumber = trim(filter_input(INPUT_POST, 'phonenumber', FILTER_SANITIZE_SPECIAL_CHARS));

    if (empty($email) || empty($password) || empty($username) || empty($phonenumber)) {
        $error = 'All fields are strictly required.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email OR phonenumber = :phonenumber LIMIT 1');
            $stmt->execute([
                'email'       => $email,
                'phonenumber' => $phonenumber
            ]);
            
            $existing_user = $stmt->fetch();

            if ($existing_user) {
                $error = 'An account with this email or phone number already exists.';
            } else {
                $otp_code = (string)random_int(100000, 999999);

                $_SESSION['temp_registration'] = [
                    'username'      => $username,
                    'email'         => $email,
                    'password_hash' => password_hash($password, PASSWORD_BCRYPT), 
                    'phonenumber'   => $phonenumber,
                    'otp'           => $otp_code,
                    'otp_expires'   => time() + 600 
                ];

                if (sendOtpEmail($email, $otp_code)) {
                    header('Location: verify_otp.php');
                    exit();
                } else {
                    $error = 'Failed to deliver security OTP configuration. Please verify your email setup.';
                }
            }
        } catch (PDOException $e) {
            $error = 'System connectivity issue encountered during check.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Account</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 320px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { display: block; margin-bottom: 4px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        .error { color: red; margin-bottom: 10px; font-size: 14px; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="form-card">
        <h2>Create Account</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
            <div class="form-group"><label>Email Address</label><input type="email" name="email" required></div>
            <div class="form-group"><label>Phone Number</label><input type="text" name="phonenumber" required></div>
            <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
            <button type="submit">Sign Up</button>
        </form>
    </div>
</body>
</html>
