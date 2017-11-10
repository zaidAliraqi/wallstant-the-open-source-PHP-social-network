<?php
session_start();
include("../config/connect.php");
$username = htmlentities($_POST['un'], ENT_QUOTES);
$password = htmlentities($_POST['pd'], ENT_QUOTES);
if($username == null && $password == null){
echo "<p class='alertRed'>".lang('enter_username_to_login')."</p>";
}elseif ($username == null){
    echo "<p class='alertRed'>".lang('enter_username_to_login')."</p>";
}elseif($password == null){
    echo "<p class='alertRed'>".lang('enter_password_to_login')."</p>";
}else{
    $chekPwd = $conn->prepare("SELECT * FROM signup WHERE Username = :username OR Email= :email");
    $chekPwd->bindParam(':email', $username, PDO::PARAM_STR);
    $chekPwd->bindParam(':username',$username,PDO::PARAM_STR);
    $chekPwd->execute();
    while ($row = $chekPwd->fetch(PDO::FETCH_ASSOC)) {
        $rUsername = $row['Username'];
        $rEmail = $row['Email'];
        $rPassword = $row['Password'];
    }

    if (isset($_COOKIE['linAtt']) AND $_COOKIE['linAtt'] == $username) {
        echo "<p class='alertRed'>".lang('cannot_login_attempts').".</p>";
    }else{
    // check if user try to login in his username or email
    $email_pattern = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/i";
    if (preg_match($email_pattern, $username)) {
        $un_or_em = $rEmail;
    }else{
        $un_or_em = $rUsername;
    }
    // ========================
    if ($un_or_em != $username) {
        echo "<p class='alertRed'>".lang('un_email_not_exist')."!</p>";
        
    }elseif (!password_verify($password,$rPassword)) {
        $checkAttempts = $conn->prepare("SELECT login_attempts FROM signup WHERE Username = :username");
        $checkAttempts->bindParam(':username',$username,PDO::PARAM_STR);
        $checkAttempts->execute();
        while ($attR = $checkAttempts->fetch(PDO::FETCH_ASSOC)) {
            $login_attempts = $attR['login_attempts'];
        }
        if ($login_attempts < 3) {
            $attempts = $login_attempts + 1;
            $addAttempts = $conn->prepare("UPDATE signup SET login_attempts =:attempts WHERE Username=:username");
            $addAttempts->bindParam(':username',$username,PDO::PARAM_STR);
            $addAttempts->bindParam(':attempts',$attempts,PDO::PARAM_INT);
            $addAttempts->execute();
        }elseif ($login_attempts >= 3) {
            $attempts = 0;
            $addAttempts = $conn->prepare("UPDATE signup SET login_attempts =:attempts WHERE Username=:username");
            $addAttempts->bindParam(':username',$username,PDO::PARAM_STR);
            $addAttempts->bindParam(':attempts',$attempts,PDO::PARAM_INT);
            $addAttempts->execute();
            setcookie("linAtt", "$username", time() + (60 * 15), '/');
        }
        $LoginTry = 3 - $login_attempts;
        echo "<p class='alertRed'>".lang('password_incorrect_you_have')." $LoginTry ".lang('attempts_to_login')."</p>";
        
    }else{
    $loginsql = "SELECT * FROM signup WHERE (Username= :username OR Email= :email) AND Password= :rPassword";
    $query = $conn->prepare($loginsql);
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':email', $username, PDO::PARAM_STR);
    $query->bindParam(':rPassword', $rPassword, PDO::PARAM_STR);
    $query->execute();
    $num = $query->rowCount();
    if($num == 0){
        echo "<p class='alertRed'>".lang('un_and_pwd_incorrect')."!</p>";
    }else{
        $_SESSION['attempts'] = 0;
        include ("GeT_login_WhileFetch.php");
        echo "Welcome...";
    }       
    }
    }
}
$conn = null;
?>
