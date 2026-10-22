<?php
// manageevents.php (Admin-only, fixed for varchar date & time)

require_once "db.php";

// --- Guard ---
if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo "Invalid access!";
  exit;
}
$username = $_GET['username'];

// --- Verify admin ---
$adm = null;
if ($stmt = $conn->prepare("SELECT admin_name, username FROM admin WHERE username = ? LIMIT 1")) {
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) $adm = $res->fetch_assoc();
  $stmt->close();
}
if (!$adm) { echo "Admin not found!"; exit; }

$q = "?username=" . urlencode($username);

// --- Helpers ---
function table_columns($conn, $table) {
  $cols = [];
  $rs = @mysqli_query($conn, "SHOW COLUMNS FROM `{$table}`");
  if ($rs) while ($r = mysqli_fetch_assoc($rs)) $cols[] = $r['Field'];
  return $cols;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// --- Detect columns ---
$event_cols = table_columns($conn, 'events');
$has_event_date       = in_array('event_date', $event_cols, true);
$has_event_time       = in_array('event_time', $event_cols, true);
$has_prize1           = in_array('prize1', $event_cols, true);
$has_prize2           = in_array('prize2', $event_cols, true);
$has_prize3           = in_array('prize3', $event_cols, true);
$has_max_participants = in_array('max_participants', $event_cols, true);
$has_created_at       = in_array('created_at', $event_cols, true);

// registrations & students columns (optional)
$reg_cols = table_columns($conn, 'registrations');
$stu_cols = table_columns($conn, 'students');

// --- Registration counts for list ---
$reg_counts = [];
$rs = @mysqli_query($conn, "SELECT event_id, COUNT(*) AS cnt FROM registrations GROUP BY event_id");
if ($rs) while ($r = mysqli_fetch_assoc($rs)) $reg_counts[(int)$r['event_id']] = (int)$r['cnt'];

// --- CREATE ---
$flash = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
  $event_name = trim($_POST['event_name'] ?? "");
  $venue      = trim($_POST['venue'] ?? "");
  $event_date = trim($_POST['event_date'] ?? "");
  $event_time = trim($_POST['event_time'] ?? "");
  $description = trim($_POST['description'] ?? "");
  $max_participants = (isset($_POST['max_participants']) && $_POST['max_participants']!=='') ? (int)$_POST['max_participants'] : null;

  // prizes
  $p1 = trim($_POST['prize1'] ?? '');
  $p2 = trim($_POST['prize2'] ?? '');
  $p3 = trim($_POST['prize3'] ?? '');

  if ($event_name === "" || $venue === "") {
    $flash = "Please fill Event Name and Venue.";
  } else {
    // Build insert
    $fields = [];
    $placeholders = [];
    $types = "";
    $values = [];

    if (in_array('event_name', $event_cols)) { $fields[]='event_name'; $placeholders[]='?'; $types.='s'; $values[]=$event_name; }
    if (in_array('venue', $event_cols))      { $fields[]='venue';      $placeholders[]='?'; $types.='s'; $values[]=$venue; }
    if ($has_event_date) { $fields[]='event_date'; $placeholders[]='?'; $types.='s'; $values[]=$event_date; }
    if ($has_event_time) { $fields[]='event_time'; $placeholders[]='?'; $types.='s'; $values[]=$event_time; }
    if (in_array('description', $event_cols)) { $fields[]='description'; $placeholders[]='?'; $types.='s'; $values[]=$description; }
    if ($has_prize1) { $fields[]='prize1'; $placeholders[]='?'; $types.='s'; $values[] = $p1 ?: null; }
    if ($has_prize2) { $fields[]='prize2'; $placeholders[]='?'; $types.='s'; $values[] = $p2 ?: null; }
    if ($has_prize3) { $fields[]='prize3'; $placeholders[]='?'; $types.='s'; $values[] = $p3 ?: null; }
    if ($has_max_participants) { $fields[]='max_participants'; $placeholders[]='?'; $types.='i'; $values[] = $max_participants !== null ? $max_participants : null; }

    $sql = "INSERT INTO events (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")";
    $st = $conn->prepare($sql);
    if ($st) {
      if ($types !== '') {
        $bind = [$types];
        for ($i=0; $i<count($values); $i++) $bind[] = &$values[$i];
        call_user_func_array([$st,'bind_param'], $bind);
      }
      if ($st->execute()) {
        $flash = "Event created successfully.";
      } else {
        $flash = "Failed to create event: " . h($st->error);
      }
      $st->close();
    } else {
      $flash = "DB prepare error: " . h($conn->error);
    }
  }
}

// --- DELETE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
  $id = (int)($_POST['id'] ?? 0);
  if ($id > 0) {
    $del = $conn->prepare("DELETE FROM events WHERE id = ?");
    $del->bind_param("i", $id);
    if ($del->execute()) $flash = "Event deleted.";
    else $flash = "Delete failed: " . h($del->error);
    $del->close();
  } else {
    $flash = "Invalid event id.";
  }
}

// --- Fetch events ---
$events = [];
$rs = @mysqli_query($conn, "SELECT * FROM events ORDER BY " . ($has_created_at ? "created_at DESC" : "id DESC"));
if ($rs) while ($r = mysqli_fetch_assoc($rs)) $events[] = $r;

// --- Fetch registrations ---
$registrations = [];
$can_join_students = in_array('student_id', $reg_cols, true) && in_array('id', $stu_cols, true);
if ($can_join_students) {
  $stu_name  = in_array('name', $stu_cols, true) ? 's.name' : "'' AS name";
  $stu_roll  = in_array('roll_no', $stu_cols, true) ? 's.roll_no' : "'' AS roll_no";
  $stu_mail  = in_array('email', $stu_cols, true) ? 's.email' : "'' AS email";
  $reg_extra = in_array('created_at', $reg_cols, true) ? 'r.created_at' : 'NULL AS created_at';
  $q = "SELECT r.*, $stu_name AS stu_name, $stu_roll AS stu_roll, $stu_mail AS stu_email, $reg_extra
        FROM registrations r
        JOIN students s ON s.id = r.student_id
        ORDER BY r.id DESC";
  $rsr = @mysqli_query($conn, $q);
  if ($rsr) while ($x = mysqli_fetch_assoc($rsr)) $registrations[] = $x;
} else {
  $rsr = @mysqli_query($conn, "SELECT * FROM registrations ORDER BY id DESC");
  if ($rsr) while ($x = mysqli_fetch_assoc($rsr)) $registrations[] = $x;
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Manage Events</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <style>
    .section-card{ background: var(--card-grad); border-radius:18px; box-shadow: var(--shadow); padding:18px; margin-bottom:18px; }
    .btn{ border:0; border-radius:12px; padding:10px 14px; font-weight:800; cursor:pointer; }
    .btn-primary{ color:#fff; background:linear-gradient(180deg,#7b61ff,#6aa6ff); }
    .btn-danger{ color:#fff; background:linear-gradient(180deg,#ff6b6b,#ff3d3d); }
    .field{ width:100%; border:1px solid #e6eaf1; border-radius:12px; padding:10px 12px; }
    .flash{ margin-bottom:14px; font-weight:700; color:#0f1f33; }
    .sidebar .nav-item{ display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:12px; text-decoration:none; color:#223; font-weight:700; }
    .sidebar .nav-item.active, .sidebar .nav-item:hover{ background: #eef3ff; }
    .tab-panel{ display:none; }
    .tab-panel.active{ display:block; }
    .form-grid{ display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-grid .full{ grid-column: 1 / -1; }
    @media(max-width:860px){ .form-grid{ grid-template-columns:1fr; } }
    .event-card{ background:#fff; border-radius:18px; box-shadow: var(--shadow); padding:18px; margin-bottom:16px; }
    .event-top{ display:flex; gap:14px; align-items:center; }
    .event-logo{ flex:0 0 64px; height:64px; width:64px; background:#f2f5ff; border-radius:14px; display:flex; align-items:center; justify-content:center; }
    .event-title{ font-size:20px; font-weight:800; margin:0; }
    .event-cols{ display:grid; grid-template-columns: 1.2fr 1fr; gap:10px; margin-top:10px; }
    .event-left p{ margin:4px 0; }
    .event-prizes{ text-align:center; font-weight:700; }
    .event-actions{ display:flex; justify-content:center; margin-top:12px; }
    .table{ width:100%; border-collapse:separate; border-spacing:0 10px; }
    .table th{ text-align:left; font-weight:800; padding:8px 10px; }
    .table td{ background:#fff; padding:12px 10px; }
  </style>
</head>
<body>
  <?php include "header.php"; ?>

  <div class="layout">
    <aside class="sidebar">
      <nav>
        <a class="nav-item active" href="#create" data-tab="create"><span>📝</span>Create Event</a>
        <a class="nav-item" href="#delete" data-tab="delete"><span>🗑️</span>Delete Event</a>
        <a class="nav-item" href="#registrations" data-tab="registrations"><span>👥</span>View Registrations</a>
      </nav>
    </aside>

    <main class="main">
      <h2>Manage Events</h2>
      <?php if ($flash): ?><div class="flash section-card"><?php echo h($flash); ?></div><?php endif; ?>

      <!-- CREATE TAB -->
      <section id="tab-create" class="tab-panel active">
        <div class="section-card">
          <h3>Create Event</h3>
          <form method="post">
            <input type="hidden" name="action" value="create">
            <div class="form-grid">
              <div><label>Event Name *</label><input class="field" type="text" name="event_name" required></div>
              <div><label>Venue *</label><input class="field" type="text" name="venue" required></div>
              <div><label>Date *</label><input class="field" type="text" name="event_date" placeholder="e.g., 16-09-2025"></div>
              <div><label>Time *</label><input class="field" type="text" name="event_time" placeholder="e.g., 10:00 AM"></div>
              <?php if ($has_max_participants): ?>
              <div><label>Maximum Participants</label><input class="field" type="number" name="max_participants"></div>
              <?php endif; ?>
              <div><label>1st Prize</label><input class="field" type="text" name="prize1"></div>
              <div><label>2nd Prize</label><input class="field" type="text" name="prize2"></div>
              <div class="full"><label>3rd Prize</label><input class="field" type="text" name="prize3"></div>
              <div class="full"><label>Description</label><textarea class="field" name="description" rows="4"></textarea></div>
              <div class="full" style="text-align:center;"><button class="btn btn-primary" type="submit">Create Event</button></div>
            </div>
          </form>
        </div>
      </section>

      <!-- DELETE TAB -->
      <section id="tab-delete" class="tab-panel">
        <div class="section-card">
          <h3>Delete Event</h3>
          <?php if (empty($events)): ?>
            <p>No events found.</p>
          <?php else: ?>
            <?php foreach ($events as $ev): ?>
              <div class="event-card">
                <div class="event-top">
                  <div class="event-logo"><img src="assets/images/event.png" style="width:42px;height:42px;"></div>
                  <h4 class="event-title"><?php echo h($ev['event_name']); ?></h4>
                </div>
                <div class="event-cols">
                  <div class="event-left">
                    <p><strong>Venue:</strong> <?php echo h($ev['venue']); ?></p>
                    <p><strong>Date:</strong> <?php echo h($ev['event_date']); ?></p>
                    <p><strong>Time:</strong> <?php echo h($ev['event_time']); ?></p>
                    <p><strong>Participants:</strong> <?php echo ($reg_counts[$ev['id']] ?? 0) . " / " . h($ev['max_participants']); ?></p>
                  </div>
                  <div class="event-prizes">
                    <?php
                      $prizes = [];
                      if (!empty($ev['prize1'])) $prizes[] = "1st: ".h($ev['prize1']);
                      if (!empty($ev['prize2'])) $prizes[] = "2nd: ".h($ev['prize2']);
                      if (!empty($ev['prize3'])) $prizes[] = "3rd: ".h($ev['prize3']);
                      echo $prizes ? implode(" | ", $prizes) : "—";
                    ?>
                  </div>
                </div>
                <div class="event-actions">
                  <form method="post" onsubmit="return confirm('Delete this event?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo (int)$ev['id']; ?>">
                    <button class="btn btn-danger" type="submit">Delete Event</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- REGISTRATIONS TAB -->
<section id="tab-registrations" class="tab-panel">
  <div class="section-card" aria-label="View registrations">
    <h3 style="margin:0 0 10px 0;">Registrations</h3>

    <?php if (empty($registrations)): ?>
      <p class="muted">No registrations found.</p>
    <?php else: ?>
      <div style="overflow:auto;">
        <table class="table">
          <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Roll No</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Event ID</th>
              <th>Registered At</th>
            </tr>
          </thead>
          <tbody>
            <?php $i=1; foreach ($registrations as $r): ?>
              <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo h(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')); ?></td>
                <td><?php echo h($r['roll_number'] ?? ''); ?></td>
                <td><?php echo h($r['email'] ?? ''); ?></td>
                <td><?php echo h($r['phone'] ?? ''); ?></td>
                <td><?php echo h($r['event_id'] ?? ''); ?></td>
                <td>
                  <?php
                    $ts = $r['created_at'] ?? '';
                    echo $ts ? h(date('d-m-Y H:i', strtotime($ts))) : '—';
                  ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>



  <?php include "footer.php"; ?>

  <script>
    const tabs = document.querySelectorAll('.sidebar .nav-item');
    const panels = {
      create: document.getElementById('tab-create'),
      delete: document.getElementById('tab-delete'),
      registrations: document.getElementById('tab-registrations')
    };
    function activateTab(key){
      tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === key));
      Object.keys(panels).forEach(k => panels[k].classList.toggle('active', k === key));
      history.replaceState(null, '', location.pathname + '<?php echo $q; ?>#'+key);
    }
    tabs.forEach(t => t.addEventListener('click', e => { e.preventDefault(); activateTab(t.dataset.tab); }));
    const initial = location.hash.replace('#','') || 'create';
    activateTab(initial);
  </script>
</body>
</html>
