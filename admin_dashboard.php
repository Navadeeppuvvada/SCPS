<?php
// admin_dashboard.php
require_once "db.php";

// ——— Guard ———
if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo "Invalid access!";
  exit;
}
$username = $_GET['username'];

// ——— Fetch Admin row (fixes: "Undefined array key 'name'") ———
$adm = null;
if ($stmt = $conn->prepare("SELECT admin_name, username FROM admin WHERE username = ? LIMIT 1")) {
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) {
    $adm = $res->fetch_assoc();
  }
  $stmt->close();
}
if (!$adm) {
  echo "Admin not found!";
  exit;
}

// ——— Helpers to safely count records even if table names differ in your DB ———
function safe_count($conn, $sql) {
  $rs = @mysqli_query($conn, $sql);
  if (!$rs) return 0;
  $row = mysqli_fetch_row($rs);
  return (int)($row[0] ?? 0);
}

// NOTE: If your actual table names differ, just change the names below.
// I used the most likely names based on your description.
$students_count = safe_count($conn, "SELECT COUNT(*) FROM students");
$faculty_count  = safe_count($conn, "SELECT COUNT(*) FROM faculty");

$q = "?username=" . urlencode($username);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

  <?php include "header.php"; ?>

  <div class="layout">

    <!-- Sidebar -->
    <aside class="sidebar">
      <nav>
        <a class="nav-item" href="manage_students.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/student.png" alt=""></span>
          <span class="nav-text">manage students</span>
        </a>
        <a class="nav-item" href="manage_faculty.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/faculty.png" alt=""></span>
          <span class="nav-text">manage faculty</span>
        </a>
        <a class="nav-item" href="manage_events.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/event.png" alt=""></span>
          <span class="nav-text">manage events</span>
        </a>
        <a class="nav-item" href="admin_lostfound.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/lostfound.png" alt=""></span>
          <span class="nav-text">lost & Found</span>
        </a>
        <a class="nav-item" href="old_books.php<?php echo $q; ?>">
          <span class="dot"><img src="assets/images/book_exchange.png" alt=""></span>
          <span class="nav-text">old books</span>
        </a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="main">

      <!-- Detail Bar -->
      <section class="detail-bar">
        <div class="detail-overlay">
          <h2>Welcome, <span><?php echo htmlspecialchars($adm['admin_name']); ?></span></h2>
        </div>
      </section>

      <!-- Tracking cards -->
      <section class="tracking-grid">
        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/student.png" alt=""></div>
            <div>
              <h3>Students</h3>
              <p class="value"><?php echo $students_count; ?></p>
            </div>
          </div>
        </div>

        <div class="track-card">
          <div class="track-meta">
            <div class="icon"><img src="assets/images/faculty.png" alt=""></div>
            <div>
              <h3>Faculty</h3>
              <p class="value"><?php echo $faculty_count; ?></p>
            </div>
          </div>
        </div>

      
      </section>

    </main>
  </div>

  <?php include "footer.php"; ?>
</body>
</html>