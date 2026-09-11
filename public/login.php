<?php
// login.php
session_start();
require 'db.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_submit'])) {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) { // Fixed syntax typo here
        $error_message = 'Please enter both username and password.';
    } else {
        try {
            // Find user by username or email
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :username OR email = :username LIMIT 1');
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $success_message = 'Login successful! Welcome back.';
            } else {
                $error_message = 'Invalid credentials provided.';
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred during verification processing.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'> <!-- Fixed view-port typo -->
    <title>Simple Login Page</title> <!-- Fixed tittle typo -->
    <style>
        body { font-family: sans-serif; background: #f4f6f9; margin: 0; padding: 0; }
        nav { background: #333; padding: 10px; }
        nav ul { list-style: none; display: flex; gap: 15px; margin: 0; padding: 0; }
        nav a { color: white; text-decoration: none; font-weight: bold; }
        .container { display: flex; justify-content: center; align-items: center; height: 80vh; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .alert { padding: 10px; margin-bottom: 10px; border-radius: 4px; font-size: 14px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; }
        .alert-success { background: #d4edda; color: #155724; }
    </style>
</head>
<body>
    <nav>
        <ul>
            <li><a href="#">HOME</a></li>
            <li><a href="#">Login</a></li>
        </ul>
    </nav>
    <div class="container">
        <div class="login-card">
            <h2>Login</h2>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            <form action="" method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" name="login_submit">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
