<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(isset($_POST["submit"])) 
{
    
    //grabbing data
    $uid = $_POST["uid"];
    $pwd = $_POST["pwd"];

    //instantiate SignupContr class
    include "../classes/dbh.classes.php";
    include "../classes/login.classes.php";
    include "../classes/login-contr.classes.php";
    $login = new LoginContr($uid, $pwd);

    //running error handlers and user signup
    $login->loginUser();

    //going back to front page
    header("location: ../index.php?error=none");

}