<?php
include("db.php");

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $query = "SELECT * FROM students WHERE username='$username' AND password='$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        header("Location: student_dashboard.php?username=" . urlencode($username));
        exit();
    } else {
        $error = "Invalid Username or Password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Student Login - SCPS</title>
    <link rel="stylesheet" type="text/css" href="assets/css/login.css">
</head>
<body>
    <div class="background"></div>
    <div class="login-box">
        <img src="assets/images/logo.jpg" class="logo">
        <h2>KH University</h2>
        <h3>Student Login</h3>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <p class="error"><?php echo $error; ?></p>
    </div>
</body>
</html>