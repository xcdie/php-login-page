<?php

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
                    $error = 'Failed to deliver security OTP configuration. Please verify your email connection properties.';
                }
            }
        } catch (PDOException $e) {
            $error = 'System connectivity issue encountered during check. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html>
  <head>
    <tittle> verify your email</tittle>
    <body>
      <h2>
        enter your verification code
      </h2>
      <? php if ($error) > <p style = 'color: red;'><? htmlspecialchars($error) ?> <p/> <?php endif; ?>
     <form method = "POST">
     <label> otp: <input type="text" name= "otp" pattern="\d{6}" maxlegnth= "6" required></label> <br>
      <button type= "submit"> verify & login</button>
     </form>
    </body>   
  </head>
</html>
