<!DOCTYPE html>
<html lang='en'>

<head>
    <meta charset='UTF-8'>
    <meta name='view-port' content='width=device-width, initial-scale=1.0'>
    <link rel="stylesheet" href="">
    <tittle> simple login page</tittle>
</head>

<body>
    <nav>
        <ul>
        <li> <a href= "#" >HOME</a></li>
        <li> <a href = "#" >login</a></li>
</ul>
    </nav>
       <div class="container">
        <div class="login-card">
            <h2>Login</h2>
            <!-- action attribute points to the current file to process with PHP later -->
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
<?php
session_start();
//database configuration

$database_host= '127.0.0.1';
$database_name= 'loginsystem';
$database_host='jamlick';
$database_password='20590770';

$success_message='';
$error_message='';

if($_SERVER['REQUEST_METHOD']=='POST'&&isset($_POST['login_submit'])){
     $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS));
    $password = $_POST['password'] ?? '';
    if (empty($username) || (empty$password)){
         $error_message = 'Please enter both username and password.';
    }else{
        
    }

?>
</body>
</html>