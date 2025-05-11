<?php
// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 引入 PHPMailer 类
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

// First we check if the form was submitted.
if (isset($_POST['reset-request-submit'])) {

  /* The first thing you should know about reset password scripts, is that we need to make it as secure as possible. To help do this we will be creating "tokens" to ensure that it is the correct user who tries to reset their password.

  Tokens are used to make sure it is the correct user that is trying to reset their password. I will explain more on this later.

  When we create the two tokens, we use random_bytes() and bin2hex(), which are build-in functions in PHP. random_bytes() generates cryptographically secure pseudo-random bytes, which we then convert to hexadecimal values so we can actually use it. Right now we are only going to use the bin2hex() on the "selector" because later we need to insert the "token" into the database in binary.

  // Later we will also include these tokens into a link which we then send the user by mail so they can reset their password. */

  $selector = bin2hex(random_bytes(8));
  $token = random_bytes(32);

  // The reason we need to have a "selector" and a "token" is to prevent timing attacks, which is when we limit the speed at which a hacker can attempt to hack our script. I will get more into this later in the next script.

  // Then we create the URL link which we will send the user by mail so they can reset their password.
  // Notice that we convert the "token" to hexadecimals here as well, to make the URL usable.

  $url = "http://localhost/practice5/create-new-password.php?selector=" . $selector . "&validator=" . bin2hex($token);

  // Then we need to define when the tokens should expire. We do this for security reasons to make sure the same token can't be used for more than an hour.

  // Then we set the timestamp and add another hour to the current time, and then pass it into the format we defined.
  $expires = date("U") + 1800;

  // Next we delete any existing tokens that might be in the database. We don't want to fill up our database with unnecessary data we don't need anymore.

  // First we need to get our database connection.
  require 'dbh.inc.php';

  // Then we grab the e-mail the user submitted from the form.
  $userEmail = $_POST["email"];

  // Finally we delete any existing entries.
  $sql = "DELETE FROM pwdReset WHERE pwdResetEmail=?";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    echo "There was an error!";
    exit();
  } else {
    mysqli_stmt_bind_param($stmt, "s", $userEmail);
    mysqli_stmt_execute($stmt);
  }

  // Here we then insert the info we have regarding the token into the database. This means that we have something we can use to check if it is the correct user that tries to change their password.
  $sql = "INSERT INTO pwdReset (pwdResetEmail, pwdResetSelector, pwdResetToken, pwdResetExpires) VALUES (?, ?, ?, ?)";
  $stmt = mysqli_stmt_init($conn);
  if (!mysqli_stmt_prepare($stmt, $sql)) {
    echo "There was an error!";
    exit();
  } else {
    // Here we also hash the token to make it unreadable, in case a hacker accessess our database.
    $hashedToken = password_hash($token, PASSWORD_DEFAULT);
    mysqli_stmt_bind_param($stmt, "ssss", $userEmail, $selector, $hashedToken, $expires);
    mysqli_stmt_execute($stmt);
  }

  // Here we close the statement and connection.
  mysqli_stmt_close($stmt);
  mysqli_close($conn);

  // The last thing we need to do is to format an e-mail and send it to the user, so they can click a link that allow them to reset their password.

  // 创建 PHPMailer 实例
  $mail = new PHPMailer(true);

  try {
    // 服务器设置
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // 启用详细调试输出
    $mail->Debugoutput = function($str, $level) {
      echo "Debug: $str<br>";
    };
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'sharpstone4848@gmail.com';
    $mail->Password = 'hwyunjqukpiurmdk'; // 替换为你的Gmail应用密码（不要包含空格）
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';

    // 收件人设置
    $mail->setFrom('sharpstone4848@gmail.com', '烟囱雨燕');
    $mail->addAddress($userEmail);

    // 邮件内容
    $mail->isHTML(true);
    $mail->Subject = 'Reset your password';
    $mail->Body = '<p>We received a password reset request. The link to reset your password is below. ';
    $mail->Body .= 'If you did not make this request, you can ignore this email</p>';
    $mail->Body .= '<p>Here is your password reset link: </br>';
    $mail->Body .= '<a href="' . $url . '">' . $url . '</a></p>';

    echo "正在尝试发送邮件...<br>";
    $mail->send();
    echo "邮件发送成功！<br>";
    echo "正在重定向...<br>";
    header("Location: ../reset-password.php?reset=success");
    exit();
  } catch (Exception $e) {
    echo "<h2>发送邮件时出错：</h2>";
    echo "<pre>";
    echo "错误信息: " . $e->getMessage() . "\n";
    echo "错误详情: " . $mail->ErrorInfo . "\n";
    echo "</pre>";
    die();
  }
} else {
  header("Location: ../signup.php");
  exit();
}
