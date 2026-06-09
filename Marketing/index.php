<?php
include("include/dbconnect.php");
require_once 'include/session_config.php';

$error = $_GET['error'] ?? '';

if (isset($_SESSION["user_mobile"])) {
	header("Location: https://velicham.tuckermotors.com/velicham/Marketing/Dashboard.php");
	exit;
}

if (isset($_POST["submit"])) {
	$uname1 = str_replace(' ', '', trim($_POST['username']));

	$pass1 = base64_encode($_POST['password']);
	$objResult = mysqli_query($connect, "SELECT * FROM `user_management` WHERE `user_mobile`='$uname1' AND `user_password`='$pass1'");
	writeLog("Login Station Mobile is " . $uname1 . "Password Is : " . $pass1);
	if (mysqli_num_rows($objResult) > 0) {
		$userData = mysqli_fetch_assoc($objResult);
		// Check if user is in allowed users list
		$allowedUsers = ['Amsathvani', 'Srihari', 'Kannan'];
		if (!in_array($userData['user_name'], $allowedUsers)) {
			header("Location:index.php?error=2");
			exit;
		}
		 $_SESSION['user_name'] = $userData['user_name'];

		
		setUserSession($uname1, $userData['user_name']);
		header("Location: https://velicham.tuckermotors.com/velicham/Marketing/Dashboard.php");
		exit;
	} else {
		header("Location:index.php?error=1");
		exit;
	}
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ADMIN PORTAL</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="assets/styles/login.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
</head>


<body>

    <div class="login-page-container">
        <div class="login-card">
            <div class="login-header">
                <h1>
                    <span>ADMIN PORTAL</span>
                </h1>
            </div>

            <form method="post" action="">
                <?php if ($error == '1'): ?>
                <div
                    style="color: red; text-align: center; margin-bottom: 15px; padding: 10px; background-color: #ffe6e6; border: 1px solid #ffcccc; border-radius: 5px;">
                    Invalid username or password. Please try again.
                </div>
                <?php elseif ($error == '2'): ?>
                <div
                    style="color: red; text-align: center; margin-bottom: 15px; padding: 10px; background-color: #ffe6e6; border: 1px solid #ffcccc; border-radius: 5px;">
                    Access denied. You are not authorized to access this system.
                </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">User Name</label>
                    <div class="input-with-icon">
                        <i class='bx bxs-user'></i>
                        <input type="text" id="username" name="username" placeholder="Enter your User Name" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class='bx bxs-lock-alt'></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class='bx bx-show toggle-password' id="togglePassword"
                            style="cursor: pointer; margin-left: 280px;" title="Show/Hide Password"></i>
                    </div>
                </div>
                <button type="submit" name="submit" class="login-btn">Login</button>
            </form>
        </div>
    </div>

</body>

</html>

<script>
$('#togglePassword').on('click', function() {
    const passwordInput = $('#password');
    const icon = $(this); // use "this" instead of separate #toggleIcon
    const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
    passwordInput.attr('type', type);
    icon.toggleClass('bx-show bx-hide');
});
</script>