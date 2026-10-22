<?php
include("db.php");

// Default
$homeLink = "index.php";

// Ensure username is in URL
if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Check in admin
    $res = mysqli_query($conn, "SELECT 1 FROM admin WHERE username='" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $homeLink = "admin_dashboard.php?username=" . urlencode($username);
    }

    // Check in faculty
    $res = mysqli_query($conn, "SELECT 1 FROM faculty WHERE username='" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $homeLink = "faculty_dashboard.php?username=" . urlencode($username);
    }

    // Check in students
    $res = mysqli_query($conn, "SELECT 1 FROM students WHERE username='" . mysqli_real_escape_string($conn, $username) . "' LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        $homeLink = "student_dashboard.php?username=" . urlencode($username);
    }
}
?>

<header class="site-header">
  <div class="header-wrap">
    <div class="header-left">
      <div class="logo-circle"><img src="assets/images/logo.jpg" alt=""></div>
      <h1 class="brand">KH University</h1>
    </div>

    <nav class="header-center">
      <!-- Home will now go to correct dashboard -->
      <a href="<?php echo $homeLink; ?>">Home</a>
      <a href="event_management.php">Events</a>
      <a href="help.php">Help</a>
    </nav>

    <div class="header-right">
      <a class="logout-btn" href="index.php">Logout</a>
    </div>
  </div>
</header>