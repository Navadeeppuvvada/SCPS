<?php
/* =====================================================================
   File: upload_timetable.php (Faculty)
   Upload + View Timetables
====================================================================== */
require_once __DIR__ . '/db.php'; // $conn (mysqli)

if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo 'Invalid access!';
  exit;
}
$faculty_username = $_GET['username'];

// verify faculty exists
if ($stmt = $conn->prepare('SELECT emp_name FROM faculty WHERE username=? LIMIT 1')) {
  $stmt->bind_param('s', $faculty_username);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res->num_rows === 0) { echo 'Faculty not found!'; exit; }
  $fac = $res->fetch_assoc();
  $stmt->close();
}

$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['upload','view'], true) ? $_GET['tab'] : 'upload';
$status = null;
$qUser = '?username=' . urlencode($faculty_username);

// ---- handle upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_timetable') {
    $branch   = trim($_POST['branch'] ?? '');
    $section  = trim($_POST['section'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    if ($branch === '' || $section === '' || $semester === '') {
        $status = ['type' => 'err', 'msg' => 'Branch, Section, and Semester are required.'];
    } elseif (!isset($_FILES['timetable_file']) || $_FILES['timetable_file']['error'] !== UPLOAD_ERR_OK) {
        $status = ['type' => 'err', 'msg' => 'Please choose a file.'];
    } else {
        $allowed = ['pdf','doc','docx','ppt','pptx','png','jpg','jpeg'];
        $orig = (string)$_FILES['timetable_file']['name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $status = ['type' => 'err', 'msg' => 'Allowed: PDF/DOC/DOCX/PPT/PPTX/PNG/JPG'];
        } else {
            $uploadDirFs  = __DIR__ . '/assets/uploads/timetables/';
            $uploadDirWeb = 'assets/uploads/timetables/';
            if (!is_dir($uploadDirFs)) { @mkdir($uploadDirFs, 0777, true); }

            $base  = preg_replace('/[^A-Za-z0-9._-]+/', '_', pathinfo($orig, PATHINFO_FILENAME));
            $name  = date('Ymd_His') . '_' . substr(md5(random_int(1, PHP_INT_MAX)), 0, 6) . '_' . $base . '.' . $ext;
            $destF = $uploadDirFs . $name;
            $destW = $uploadDirWeb . $name;

            if (!move_uploaded_file($_FILES['timetable_file']['tmp_name'], $destF)) {
                $status = ['type' => 'err', 'msg' => 'Failed to move uploaded file.'];
            } else {
                $stmt = $conn->prepare('INSERT INTO timetable (branch, section, semester, file_path, uploaded_by) VALUES (?,?,?,?,?)');
                if ($stmt) {
                    $stmt->bind_param('sssss', $branch, $section, $semester, $destW, $faculty_username);
                    if ($stmt->execute()) {
                        header('Location: upload_timetable.php' . $qUser . '&tab=view&msg=uploaded');
                        exit;
                    }
                    $stmt->close();
                }
                $status = ['type' => 'err', 'msg' => 'Database error while saving record.'];
            }
        }
    }
}

// ---- fetch list for view ----
$timetables = [];
if ($tab === 'view') {
    $rs = $conn->query('SELECT id,branch,section,semester,file_path,uploaded_by,uploaded_at FROM timetable ORDER BY uploaded_at DESC');
    if ($rs) { while ($row = $rs->fetch_assoc()) $timetables[] = $row; }
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SCPS • Upload Timetable</title>
  <link rel="stylesheet" href="assets/css/dashboard.css" />
  <style>
    /* same CSS style as materials page */
    .section-title{margin:8px 0 12px;font-size:22px;font-weight:800;color:#27364a}
    .upload-card{background:linear-gradient(135deg,#eaf4ff,#ffffff);border-radius:18px;padding:28px;box-shadow:0 8px 22px rgba(18,38,63,.08);max-width:760px;margin:0 auto}
    .upload-icon{width:120px;height:120px;border-radius:20px;border:2px dashed #2c6fbc;display:flex;align-items:center;justify-content:center;font-size:48px;color:#2c6fbc;margin:0 auto 18px;background:#fff;cursor:pointer}
    .form-col{margin-bottom:14px}
    .form-control{width:100%;padding:12px 14px;border:1px solid #e1e8f0;border-radius:12px;font:14px/1.4 inherit}
    .actions{margin-top:14px;display:flex;gap:10px;justify-content:center}
    .btn{border:0;color:#fff;font-weight:800;padding:12px 18px;border-radius:14px;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,filter .18s ease}
    .btn:hover{transform:translateY(-2px);box-shadow:0 12px 24px rgba(18,38,63,.16);filter:saturate(1.05)}
    .btn-upload{background:linear-gradient(90deg,#4facfe,#00f2fe)}
    .status{margin-top:12px;padding:10px 12px;border-radius:12px;font-weight:700}
    .status.ok{background:#e7fff4;color:#0b7a47;border:1px solid #bdf2db}
    .status.err{background:#fff1f1;color:#b32d2d;border:1px solid #f3c1c1}
    .list{display:grid;gap:12px;max-width:960px;margin:0 auto}
    .bar{display:flex;align-items:center;gap:16px;background:#fff;border-radius:14px;padding:14px;box-shadow:0 10px 24px rgba(18,38,63,.08)}
    .file-badge{width:58px;height:58px;min-width:58px;border-radius:14px;display:grid;place-items:center;background:#eef4ff;color:#2c6fbc;font-size:26px;box-shadow:inset 0 0 0 1.5px #d9e3f1}
    .meta{flex:1}
    .meta h4{margin:0;font-size:18px}
    .meta p{margin:2px 0;color:#607089;font-size:13px}
    .btn-dl{ text-decoration:none;display:inline-block;background:linear-gradient(90deg,#43e97b,#38f9d7);color:#fff;font-weight:800;padding:10px 14px;border-radius:12px;transition:transform .18s ease,box-shadow .18s ease}
    .btn-dl:hover{transform:translateY(-2px);box-shadow:0 10px 20px rgba(18,38,63,.16)}
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="layout">
    <aside class="sidebar">
      <nav>
        <a class="nav-item" href="upload_timetable.php<?= $qUser ?>&tab=upload">
          <span class="dot"><img src="assets/images/upload_timetable.png" alt=""></span>
          <span class="nav-text">upload timetable</span>
        </a>
        <a class="nav-item" href="upload_timetable.php<?= $qUser ?>&tab=view">
          <span class="dot"><img src="assets/images/upload_timetable.png" alt=""></span>
          <span class="nav-text">view timetables</span>
        </a>
      </nav>
    </aside>

    <main class="main">
      <?php if (isset($_GET['msg']) && $_GET['msg']==='uploaded'): ?>
        <div class="status ok">Timetable uploaded successfully.</div>
      <?php endif; ?>

      <?php if ($tab === 'upload'): ?>
        <h2 class="section-title">Upload Timetable</h2>
        <div class="upload-card">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_timetable" />

            <label for="fileInput" class="upload-icon">+</label>
            <input id="fileInput" type="file" name="timetable_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.png,.jpg,.jpeg" required style="display:none;">

            <div class="form-col">
              <input class="form-control" type="text" name="branch" placeholder="Enter Branch" required>
            </div>
            <div class="form-col">
              <input class="form-control" type="text" name="section" placeholder="Enter Section" required>
            </div>
            <div class="form-col">
              <input class="form-control" type="text" name="semester" placeholder="Enter Semester" required>
            </div>

            <div class="actions">
              <button class="btn btn-upload" type="submit">Upload</button>
            </div>
          </form>
          <?php if ($status): ?>
            <div class="status <?= h($status['type']) ?>"><?= h($status['msg']) ?></div>
          <?php endif; ?>
        </div>

      <?php else: ?>
        <h2 class="section-title">View Timetables</h2>
        <?php if (!$timetables): ?>
          <p>No timetables uploaded yet.</p>
        <?php else: ?>
          <div class="list">
            <?php foreach ($timetables as $t): ?>
              <div class="bar">
                <div class="file-badge">📄</div>
                <div class="meta">
                  <h4><?= h($t['branch']) ?> - <?= h($t['section']) ?> (Sem <?= h($t['semester']) ?>)</h4>
                  <p>By <?= h($t['uploaded_by']) ?> • <?= h($t['uploaded_at']) ?></p>
                </div>
                <a class="btn-dl" href="<?= h($t['file_path']) ?>" download>Download</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </main>
  </div>

  <?php include 'footer.php'; ?>
</body>
</html>