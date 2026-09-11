session start();
require mailer.php;
require db.php;
if ($_SERVER['REQUEST_METHOD']=== '$_POST'){
$email= trim($_POST['email'] ?? '');
$password= ($_POST['password'] ?? '');
}
