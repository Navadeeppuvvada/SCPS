<?php
include("db.php"); // $conn

// ---- Input (no sessions per your spec) ----
if (!isset($_GET['username']) || $_GET['username'] === '') {
    echo "Invalid access!";
    exit;
}
$username = mysqli_real_escape_string($conn, $_GET['username']);

// ---- Fetch Student ----
$student_sql = "SELECT username, std_name, std_address, roll_number, phone 
                FROM students 
                WHERE username='$username' LIMIT 1";
$student_rs  = mysqli_query($conn, $student_sql);

if (!$student_rs || mysqli_num_rows($student_rs) === 0) {
    echo "Student not found!";
    exit;
}
$student = mysqli_fetch_assoc($student_rs);
$roll    = mysqli_real_escape_string($conn, $student['roll_number']);

// ---- Fetch Academics (may be empty, handle gracefully) ----
$acad_sql = "SELECT attendance_percentage, marks, fee_due, backlogs 
             FROM academics 
             WHERE roll_number='$roll' LIMIT 1";
$acad_rs  = mysqli_query($conn, $acad_sql);
$acad     = ($acad_rs && mysqli_num_rows($acad_rs) > 0) ? mysqli_fetch_assoc($acad_rs) : [
    'attendance_percentage' => 0,
    'marks'                 => 0,
    'fee_due'               => 0,
    'backlogs'              => 0
];

// ---- Helpers ----
function mask_phone($ph) {
    // mask mid digits if longer than 6
    if (strlen($ph) >= 10) {
        return substr($ph, 0, 2) . str_repeat("X", max(0, strlen($ph)-4)) . substr($ph, -2);
    }
    return $ph;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Student Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

  <?php include("header.php"); ?>

  <div class="layout">

    <!-- ============ Sidebar (fixed) ============ -->
    <aside class="sidebar">
      <nav>
        <a href="elearning.php?username=<?php echo urlencode($student['username']); ?>" class="nav-item">
          <span class="dot"><img src="assets/images/e-learning.png" alt=""></span>
          <span class="nav-text">e-learning</span>
        </a>
        <a href="academic_management.php?username=<?php echo urlencode($student['username']); ?>" class="nav-item">
          <span class="dot"><img src="assets/images/erp.png" alt=""></span>
          <span class="nav-text">academic management</span>
        </a>
        <a href="event_management.php?username=<?php echo urlencode($student['username']); ?>" class="nav-item">
          <span class="dot"><img src="assets/images/event.png" alt=""></span>
          <span class="nav-text">events</span>
        </a>
        <a href="lostfound.php?username=<?php echo urlencode($student['username']); ?>" class="nav-item">
          <span class="dot"><img src="assets/images/lostfound.png" alt=""></span>
          <span class="nav-text">lost & found</span>
        </a>
        <a href="book_exchange.php?username=<?php echo urlencode($student['username']); ?>" class="nav-item">
          <span class="dot"><img src="assets/images/book_exchange.png" alt=""></span>
          <span class="nav-text">book exchange</span>
        </a>
      </nav>
    </aside>

    <!-- ============ Main content ============ -->
    <main class="main">

      <!-- Detail Bar -->
      <section class="detail-bar">
      
        <div class="detail-overlay">
          <h2>Welcome, <span><?php echo htmlspecialchars($student['std_name']); ?></span></h2>
          <p><strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number']); ?></p>
          <p><strong>Address:</strong> <?php echo htmlspecialchars($student['std_address']); ?></p>
          <p><strong>Phone:</strong> <?php echo htmlspecialchars(mask_phone($student['phone'])); ?></p>
        </div>
        <!-- blurred bg via CSS ::before -->
      </section>

      <!-- Tracking Bars -->
      <section class="tracking-grid">

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/attendence.png" alt=""></div>
            <div>
              <h3>attendance</h3>
              <p class="value"><?php echo (float)$acad['attendance_percentage']; ?>%</p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/fee.png" alt=""></div>
            <div>
              <h3>fee due</h3>
              <p class="value">₹<?php echo number_format((float)$acad['fee_due'], 2); ?></p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/marks.png" alt=""></div>
            <div>
              <h3>marks (prev sem)</h3>
              <p class="value"><?php echo (int)$acad['marks']; ?><span class="muted"> / 900</span></p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/backlog.png" alt=""></div>
            <div>
              <h3>backlogs</h3>
              <p class="value"><?php echo (int)$acad['backlogs']; ?></p>
            </div>
          </div>
        </div>

      </section>

     

    </main>
  </div>

  <?php include("footer.php"); ?>

</body>
</html>