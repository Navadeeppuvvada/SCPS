<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>KH University - Login</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      background: linear-gradient(135deg, #fefefeb5, #b4e3e3ff);
    }

    .title-bar {
      display: flex;
      align-items: center;
      justify-content: flex-start;
      background: #9dcbd7ff;
      box-shadow: 0px 3px 10px rgba(0,0,0,0.2);
      padding: 5px 5px;
      border-radius: 0 0 15px 25px;
      width: 100%;
    }

    .title-bar img {
      width: 55px;
      height: 55px;
      border-radius: 50%;
      margin-right: 15px;
      margin-left: 25px;
    }

    .university-title {
      font-size: 32px;
      font-weight: bold;
      background: linear-gradient(to right, #000000, #6b4226, #000000);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .login-heading {
      font-size: 20px;
      font-weight: bold;
      margin: 70px 0 29px;
      color: #333;
    }

    .login-options {
      display: flex;
      justify-content: center;
      gap: 50px;
    }

    .login-box {
      width: 160px;
      height: 160px;
      border-radius: 15px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
    }

    .login-box img {
      width: 105px;
      height: 95px;
      margin-bottom: 8px;
    }

    .login-box p {
      font-size: 15px;
      font-weight: bold;
    }

    .admin {
      background: linear-gradient(135deg, #1e90ff, #ffffff);
    }
    .student {
      background: linear-gradient(135deg, #32cd32, #ffffff);
    }
    .faculty {
      background: linear-gradient(135deg, #87ceeb, #ffffff);
    }

    .login-box:hover {
      transform: scale(1.05);
      box-shadow: 0px 6px 15px rgba(0, 0, 0, 0.4);
    }
  </style>
</head>
<body>
  <!-- Top Slab -->
  <header class="title-bar">
    <img src="assets/images/logo.jpg" alt="College Logo">
    <h1 class="university-title">KH UNIVERSITY</h1>
  </header>

  <!-- Heading -->
  <h2 class="login-heading">Choose a Login Option</h2>

  <!-- Login Boxes -->
  <div class="login-options">
    <div class="login-box admin" onclick="window.location.href='AdminLogin.php'">
      <img src="assets/images/administrator.png" alt="Admin Login">
      <p>Admin Login</p>
    </div>
    <div class="login-box student" onclick="window.location.href='StudentLogin.php'">
      <img src="assets/images/student.png" alt="Student Login">
      <p>Student Login</p>
    </div>
    <div class="login-box faculty" onclick="window.location.href='FacultyLogin.php'">
      <img src="assets/images/faculty.png" alt="Faculty Login">
      <p>Faculty Login</p>
    </div>
  </div>
</body>
</html>