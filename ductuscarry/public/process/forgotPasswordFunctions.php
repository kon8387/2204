<?php 
include_once __DIR__.'/../helpers/mysql.php';
include_once __DIR__.'/../helpers/helper.php';

session_start();
$helper = new Helper();
$db = new Mysql_Driver();

$success = true;
$message_title = "";
$message_body = "";
$message_link = "";

if (isset($_POST["request-security-qsn"])) {

    // Get input data
    $email = $_POST["email"] ;

    // validation if email exist

    // Connect to the MySQL database
    $db->connect();

    // Check for duplicate email
    $qry = "SELECT * FROM customer WHERE customerEmail = '$email'";
    $result = $db->query($qry);

    if ($db->num_rows($result) <= 0 ) {
        $success = false;
        $message_body .= "<p>- Email provided is not valid! Please provide an existing one.</p>";
    }

    if ($success) {

        $row = $db->fetch_array($result);

        $_SESSION['customerPwdQn'] = $row['customerPwdQn'];
        $_SESSION['customerEmail'] = $row['customerEmail'];
        
        $db->close();
        $pageUrl = $helper->pageUrl('securityQuestion.php');
        header("Location: $pageUrl");

    } else {
        $message_title = "<h1 class='page-title'>Password Reset Failed</h1>";
        $message_link = "<a href='javascript:history.back()'>Go Back</a>";
    }

} else if (isset($_POST["submit-security-qsn"])) {
    // retrieve email and answer
    $email = $_POST["email"];
    $pwdanswer = $_POST["pwdanswer"];

    // check if the security answer is correct
    $db->connect();

    // Check if the password answer is the same
    $qry = "SELECT * FROM customer WHERE customerEmail='$email'";
    $result = $db->query($qry);
    $row = $db->fetch_array($result);

    if ($row['customerPwdAns'] != $pwdanswer) {
        $success = false;
        $message_body .= "<p>- Security Answer is not correct! Please try again</p>";
    }

    if ($success) {
        // reset the password and send the email
        $customerId = $row["customerID"];

        // Random password generator
        $seed = str_split('abcdefghijklmnopqrstuvwxyz'
                 .'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
                 .'0123456789!@#$%^&*()'); // and any other characters
        shuffle($seed); // probably optional since array_is randomized; this may be redundant
        $newPassword = '';
        foreach (array_rand($seed, 8) as $k) {
            $newPassword .= $seed[$k];
        }
        
        $newPasswordHashed = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user account with new password
		$qry = "UPDATE customer SET customerPwd='$newPasswordHashed' 
				WHERE customerID=$customerId";
        $db->query($qry);
        
        $message_title = "<h1 class='page-title'>Password Reset Success</h1>";
        $message_body .= "<p>Acccount Password has been reset! New Password: $newPassword</p>";
        $pageUrl = $helper->pageUrl('login.php');
        $message_link = "<a href='$pageUrl'>Log In</a>";

    } else {
        $message_title = "<h1 class='page-title'>Password Reset Failed</h1>";
        $pageUrl = $helper->pageUrl('forgotPassword.php');
        $message_link = "<a href='$pageUrl'>Try Again</a>";
    }


    
}
$db->close();
?>
<html lang="en">
<?php include $helper->subviewPath('header.php') ?>
<main class="container text-center">
    <?php 
        echo $message_title;
        echo $message_body;
        echo $message_link; 
    ?>
</main>
<?php include $helper->subviewPath('footer.php') ?>
</html>