<?php
session start();
require mailer.php;
require db.php;
if ($_SERVER['REQUEST_METHOD']=== '$_POST'){
$email= trim($_POST['email'] ?? '');
$password= ($_POST['password'] ?? '');
$username = ($_POST['username'] ?? '');
$phonenumber = ($_POST['phonenumber'] ?? '');

$stmt->$pdo- >prepare('SELECT * FROM users WHERE email = :email' OR phonenumber = :phonenumber LINIT1);
$stmt->execute

'email' = $email,
'phonenumber'= $phonenumber,
);

$stmt user fetch();
} if {
$error = '';
>
