<?php /* =====================================================================
   File: elearning.php (Student)
   Single-file: View Materials / Lab Manuals / Timetable
   Expects: ?username=STUDENT_USERNAME in query (from your student login flow).
======================================================================
-- Study Materials
CREATE TABLE materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Lab Manuals
CREATE TABLE labmanuals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Timetables
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    branch VARCHAR(100) NOT NULL,
    section VARCHAR(50) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by VARCHAR(100) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
 */ ?>
<?php
require_once __DIR__ . '/db.php'; // $conn

if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo 'Invalid access!';
  exit;
}
$student_username = $_GET['username'];

// verify student exists
if ($stmt = $conn->prepare('SELECT std_name FROM students WHERE username=? LIMIT 1')) {
  $stmt->bind_param('s', $student_username);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { echo 'Student not found!'; exit; }
  $stu = $res->fetch_assoc();
  $stmt->close();
}

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['materials','manuals','timetable'], true) ? $_GET['tab'] : 'materials';
$qUser = '?username=' . urlencode($student_username);

$materials = $manuals = [];
$timetable = []; // latest only

if ($tab === 'materials') {
  $rs = $conn->query('SELECT id,title,subject,file_path,uploaded_at FROM materials ORDER BY uploaded_at DESC');
  if ($rs) { while ($r = $rs->fetch_assoc()) $materials[] = $r; }
}
if ($tab === 'manuals') {
  $rs = $conn->query('SELECT id,title,subject,file_path,uploaded_at FROM labmanuals ORDER BY uploaded_at DESC');
  if ($rs) { while ($r = $rs->fetch_assoc()) $manuals[] = $r; }
}
if ($tab === 'timetable') {
  $rs = $conn->query('SELECT id,branch,section,file_path,uploaded_at FROM timetable ORDER BY uploaded_at DESC LIMIT 1');
  if ($rs) { $timetable = $rs->fetch_assoc(); }
}

function h2($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SCPS • E-Learning</title>
  <link rel="stylesheet" href="assets/css/dashboard.css" />
  <style>
    .section-title{margin:8px 0 12px;font-size:22px;font-weight:800;color:#27364a}
    .list{display:grid;gap:12px;max-width:960px}
    .bar{display:flex;align-items:center;gap:16px;background:#fff;border-radius:14px;padding:14px;box-shadow:0 10px 24px rgba(18,38,63,.08)}
    .file-badge{width:58px;height:58px;min-width:58px;border-radius:14px;display:grid;place-items:center;background:#eef4ff;color:#2c6fbc;font-size:26px;box-shadow:inset 0 0 0 1.5px #d9e3f1}
    .meta{flex:1}
    .meta h4{margin:0;font-size:18px}
    .meta p{margin:2px 0;color:#607089;font-size:13px}
    .btn-dl{text-decoration:none;display:inline-block;background:linear-gradient(90deg,#43e97b,#38f9d7);color:#fff;font-weight:800;padding:10px 14px;border-radius:12px;transition:transform .18s ease,box-shadow .18s ease}
    .btn-dl:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(18,38,63,.16)}

    .tt-wrap{background:#fff;border-radius:16px;box-shadow:0 10px 24px rgba(18,38,63,.08);padding:16px;max-width:980px}
    .tt-preview{max-width:600px;margin:0 auto}
    .tt-media{width:100%;height:auto;max-height:400px;object-fit:contain;border-radius:10px;box-shadow:0 6px 16px rgba(18,38,63,.08)}
    .tt-actions{margin-top:12px;display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="layout">
    <aside class="sidebar">
      <nav>
        <a class="nav-item" href="elearning.php<?= $qUser ?>&tab=materials">
          <span class="dot"><img src="assets/images/materials.png" alt=""></span>
          <span class="nav-text">view materials</span>
        </a>
        <a class="nav-item" href="elearning.php<?= $qUser ?>&tab=manuals">
          <span class="dot"><img src="assets/images/manual.png" alt=""></span>
          <span class="nav-text">view lab manuals</span>
        </a>
        <a class="nav-item" href="elearning.php<?= $qUser ?>&tab=timetable">
          <span class="dot"><img src="assets/images/upload_timetable.png" alt=""></span>
          <span class="nav-text">view timetable</span>
        </a>
      </nav>
    </aside>

    <main class="main">
      <?php if ($tab === 'materials'): ?>
        <h2 class="section-title">Study Materials</h2>
        <?php if (!$materials): ?><p>No materials available.</p><?php else: ?>
          <div class="list">
            <?php foreach ($materials as $m): ?>
              <div class="bar">
                <div class="file-badge">📘</div>
                <div class="meta">
                  <h4><?= h2($m['title']) ?></h4>
                  <p>Subject: <?= h2($m['subject']) ?></p>
                  <p>Date: <?= h2($m['uploaded_at']) ?></p>
                </div>
                <a class="btn-dl" href="<?= h2($m['file_path']) ?>" download>Download</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'manuals'): ?>
        <h2 class="section-title">Lab Manuals</h2>
        <?php if (!$manuals): ?><p>No lab manuals available.</p><?php else: ?>
          <div class="list">
            <?php foreach ($manuals as $m): ?>
              <div class="bar">
                <div class="file-badge">🧪</div>
                <div class="meta">
                  <h4><?= h2($m['title']) ?></h4>
                  <p>Lab: <?= h2($m['subject']) ?></p>
                  <p>Date: <?= h2($m['uploaded_at']) ?></p>
                </div>
                <a class="btn-dl" href="<?= h2($m['file_path']) ?>" download>Download</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php else: ?>
        <h2 class="section-title">Timetable</h2>
        <?php if (!$timetable): ?><p>No timetable uploaded yet.</p><?php else: ?>
          <div class="tt-wrap">
            <?php
              $path = (string)$timetable['file_path'];
              $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
              $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp'], true);
              $isPdf = ($ext === 'pdf');
            ?>
            <div class="tt-preview">
              <?php if ($isImg): ?>
                <img class="tt-media" src="<?= h2($path) ?>" alt="Timetable" />
              <?php elseif ($isPdf): ?>
                <embed class="tt-media" src="<?= h2($path) ?>" type="application/pdf" />
              <?php else: ?>
                <p>Timetable: <a href="<?= h2($path) ?>">Open file</a></p>
              <?php endif; ?>
            </div>
            <div class="tt-actions">
              <a class="btn-dl" href="<?= h2($path) ?>" download>Download</a>
              <span style="color:#607089;font-weight:700">
                Branch: <?= h2($timetable['branch']) ?> • Section: <?= h2($timetable['section']) ?> • <?= h2($timetable['uploaded_at']) ?>
              </span>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>