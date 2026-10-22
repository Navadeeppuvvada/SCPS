<?php
// faculty_dashboard.php
require_once "db.php";

if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo "Invalid access!";
  exit;
}
$username = $_GET['username'];

$stmt = $conn->prepare("SELECT * FROM faculty WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
  echo "Faculty not found!";
  exit;
}
$fac = $res->fetch_assoc();

$q = "?username=" . urlencode($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Faculty Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

  <?php include "header.php"; ?>

  <div class="layout">

    <!-- ============ Sidebar ============ -->
    <aside class="sidebar">
      <nav>
        <a class="nav-item" href="upload_materials.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/upload_materials.png" alt=""></span>
          <span class="nav-text">upload materials</span>
        <a class="nav-item" href="upload_labmanuals.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/manual.png" alt=""></span>
          <span class="nav-text">upload Labmanuals</span>
        </a>
        </a>
        <a class="nav-item" href="upload_marks.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/upload_marks.png" alt=""></span>
          <span class="nav-text">upload marks</span>
        </a>
        <a class="nav-item" href="upload_timetable.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/upload_timetable.png" alt=""></span>
          <span class="nav-text">upload timetable</span>
        </a>
        <a class="nav-item" href="lostfound.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/lostfound.png" alt=""></span>
          <span class="nav-text">lost & found</span>
        </a>
        <a class="nav-item" href="event_management.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/event.png" alt=""></span>
          <span class="nav-text">event management</span>
        </a>
      </nav>
    </aside>

    <!-- ============ Main content ============ -->
    <main class="main">

      <!-- Detail Bar -->
      <section class="detail-bar">
        <div class="detail-overlay">
          <h2>Welcome, <span><?php echo htmlspecialchars($fac['emp_name']); ?></span></h2>
          <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($fac['employee_id']); ?></p>
          <p><strong>Department:</strong> <?php echo htmlspecialchars($fac['department'] ?? '—'); ?></p>
          <p><strong>Address:</strong> <?php echo htmlspecialchars($fac['emp_address'] ?? '—'); ?></p>
        </div>
      </section>

      <!-- Tracking Bars -->
      <section class="tracking-grid">

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/attendence.png" alt=""></div>
            <div>
              <h3>Attendance (last month)</h3>
              <p class="value"><?php echo htmlspecialchars($fac['attendance'] ?? 0); ?> / 26 days</p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/salary.png" alt=""></div>
            <div>
              <h3>Salary</h3>
              <p class="value">₹<?php echo number_format((float)($fac['salary'] ?? 0), 2); ?></p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/feedback.png" alt=""></div>
            <div>
              <h3>Feedback</h3>
              <p class="value"><?php echo htmlspecialchars($fac['feedback_percentage'] ?? 0); ?>%</p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/classes.png" alt=""></div>
            <div>
              <h3>Classes Taken</h3>
              <p class="value"><?php echo htmlspecialchars($fac['classes_taken'] ?? 0); ?> / 50</p>
            </div>
          </div>
        </div>

      </section>

    </main>
  </div>

  <?php include "footer.php"; ?>

</body>
</html>