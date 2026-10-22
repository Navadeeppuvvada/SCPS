<?php
/* ============================================================
   File: event_management.php  (Student / Faculty)
   - Tabs: registration | details | prizes
   - Requires: ?username=USERNAME
   ============================================================ */

require_once __DIR__ . '/db.php';

if (!isset($_GET['username']) || $_GET['username'] === '') {
  echo 'Invalid access!';
  exit;
}
$username = $_GET['username'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], ['registration','details','prizes'], true)
  ? $_GET['tab'] : 'details';

$qUser = '?username=' . urlencode($username);

// ---------- Fetch events + registration count ----------
$events = [];
$sql = "SELECT e.*,
               (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS reg_count
        FROM events e
        ORDER BY e.id DESC";
if ($rs = $conn->query($sql)) {
  while ($r = $rs->fetch_assoc()) { $events[] = $r; }
}

// ---------- Handle Registration POST ----------
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'register') {
  $first = trim($_POST['first_name'] ?? '');
  $last  = trim($_POST['last_name'] ?? '');
  $roll  = trim($_POST['roll_number'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $age   = isset($_POST['age']) && $_POST['age'] !== '' ? (int)$_POST['age'] : 0;
  $year  = trim($_POST['academic_year'] ?? '');
  $desc  = trim($_POST['description'] ?? '');
  $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;

  if ($first === '' || $roll === '' || $email === '' || $phone === '' || $event_id === 0) {
    $flash = 'Please fill all required fields (First name, Roll, Email, Phone, Select Event).';
    $tab = 'registration';
  } else {
    $stmt = $conn->prepare("SELECT max_participants FROM events WHERE id = ? LIMIT 1");
    if ($stmt) {
      $stmt->bind_param("i", $event_id);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res ? $res->fetch_assoc() : null;
      $stmt->close();
      if (!$row) {
        $flash = 'Selected event not found.';
        $tab = 'registration';
      } else {
        $maxp = (int)($row['max_participants'] ?? 0);

        $stmt2 = $conn->prepare("SELECT COUNT(*) AS cnt FROM registrations WHERE event_id = ?");
        $stmt2->bind_param("i", $event_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $cntRow = $res2 ? $res2->fetch_assoc() : ['cnt' => 0];
        $stmt2->close();
        $curCount = (int)$cntRow['cnt'];

        if ($maxp > 0 && $curCount >= $maxp) {
          $flash = "Registration closed: event reached maximum participants ({$maxp}).";
          $tab = 'registration';
        } else {
          $insert = $conn->prepare(
            "INSERT INTO registrations (first_name,last_name,roll_number,email,phone,age,academic_year,description,event_id)
             VALUES (?,?,?,?,?,?,?,?,?)"
          );
          if ($insert) {
            $ageParam = $age;
            $insert->bind_param("sssssissi",
              $first, $last, $roll, $email, $phone, $ageParam, $year, $desc, $event_id
            );
            if ($insert->execute()) {
              $flash = 'Registration submitted successfully! 🎉';
              $tab = 'registration';
              // refresh events after insert
              $events = [];
              if ($rs = $conn->query($sql)) {
                while ($r = $rs->fetch_assoc()) { $events[] = $r; }
              }
              $_POST = [];
            } else {
              $flash = 'Failed to submit. Please try again later.';
              $tab = 'registration';
            }
            $insert->close();
          } else {
            $flash = 'DB error (prepare failed).';
            $tab = 'registration';
          }
        }
      }
    } else {
      $flash = 'DB error (prepare failed).';
      $tab = 'registration';
    }
  }
}

// helper
function h2($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>SCPS • Event Management</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- External for header/sidebar/footer -->
  <link rel="stylesheet" href="assets/css/dashboard.css" />
  <!-- Internal CSS for main body -->
  <style>
    .section-title{margin:8px 0 16px;font-size:26px;font-weight:800;color:#27364a}

    .events-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:18px;max-width:1080px}
    .event-card{background:#fff;border-radius:18px;padding:20px;box-shadow:var(--shadow);display:flex;flex-direction:column;gap:10px}
    .event-card.prizes{align-items:center;text-align:center}

    .event-header{display:flex;align-items:center;gap:12px;width:100%}
    .event-card.prizes .event-header{justify-content:center}

    .event-img-circle{width:64px;height:64px;border-radius:50%;background:#eef4ff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex:0 0 64px}
    .event-img-circle img{width:100%;height:100%;object-fit:contain} /* <= fix zoom */

    .event-name{margin:0;font-size:24px;color:#2c6fbc;font-weight:900}
    .event-desc{color:#607089;font-size:14px}

    .event-line{color:#425065;font-weight:700;text-align:left}
    .prize-line{color:#2b3a4a;font-weight:800;margin:6px 0}
    .divider{height:1px;width:100%;background:#e1e8f0;margin:6px 0}

    .card-actions{width:100%;display:flex;justify-content:center;margin-top:8px}

    .register-btn,
    .btn-dl{ /* also style the form button */
      appearance:none;border:0;cursor:pointer;
      text-decoration:none;display:inline-block;
      background:linear-gradient(90deg,#7b61ff,#6aa6ff);
      color:#fff;font-weight:800;padding:10px 16px;border-radius:14px;
      box-shadow:0 6px 14px rgba(122,142,255,.25);transition:transform .14s ease, box-shadow .14s ease
    }
    .register-btn:hover,.btn-dl:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(122,142,255,.35)}

    .ev-form{background:#fff;border-radius:18px;padding:18px;box-shadow:var(--shadow);max-width:1080px}
    .ev-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    @media(max-width:860px){.ev-row{grid-template-columns:1fr}.events-grid{grid-template-columns:1fr}}
    .ev-row>div{display:flex;flex-direction:column;gap:6px}
    .ev-form label{font-weight:700;color:#27364a;font-size:14px}
    .ev-form input,.ev-form select,.ev-form textarea{width:100%;padding:10px 12px;border-radius:10px;border:1px solid #e1e8f0;font:inherit}
    .ev-form textarea{min-height:110px;resize:vertical}
    .ev-actions{margin-top:12px;display:flex;gap:10px;align-items:center;justify-content:center}
    .hint{color:#607089;font-weight:600}
  </style>
</head>
<body>
  <?php include 'header.php'; ?>

  <div class="layout">
    <!-- Sidebar -->
    <aside class="sidebar">
      <nav>
        <a class="nav-item <?php echo $tab==='registration'?'active':''; ?>" href="event_management.php<?= $qUser ?>&tab=registration">
          <span class="dot"><img src="assets/images/event.png" alt=""></span>
          <span class="nav-text">event registration</span>
        </a>
        <a class="nav-item <?php echo $tab==='details'?'active':''; ?>" href="event_management.php<?= $qUser ?>&tab=details">
          <span class="dot"><img src="assets/images/event.png" alt=""></span>
          <span class="nav-text">event details</span>
        </a>
        <a class="nav-item <?php echo $tab==='prizes'?'active':''; ?>" href="event_management.php<?= $qUser ?>&tab=prizes">
          <span class="dot"><img src="assets/images/prizes.png" alt=""></span>
          <span class="nav-text">event prizes</span>
        </a>
      </nav>
    </aside>

    <!-- Main -->
    <main class="main">
      <?php if ($tab === 'registration'): ?>
        <h2 class="section-title">Event Registration</h2>
        <?php if ($flash): ?><p class="hint"><?= h2($flash) ?></p><?php endif; ?>

        <div class="ev-form">
          <form method="post" action="event_management.php<?= $qUser ?>&tab=registration" autocomplete="off" novalidate>
            <input type="hidden" name="form" value="register" />
            <div class="ev-row">
              <div>
                <label>First Name *</label>
                <input name="first_name" required value="<?= h2($_POST['first_name'] ?? '') ?>">
              </div>
              <div>
                <label>Last Name (optional)</label>
                <input name="last_name" value="<?= h2($_POST['last_name'] ?? '') ?>">
              </div>
            </div>
            <div class="ev-row">
              <div>
                <label>Email *</label>
                <input type="email" name="email" required value="<?= h2($_POST['email'] ?? '') ?>">
              </div>
              <div>
                <label>Phone Number *</label>
                <input name="phone" required value="<?= h2($_POST['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="ev-row">
              <div>
                <label>Roll Number *</label>
                <input name="roll_number" required value="<?= h2($_POST['roll_number'] ?? '') ?>">
              </div>
              <div>
                <label>Academic Year *</label>
                <select name="academic_year" required>
                  <?php
                  $years = ['First Year','Second Year','Third Year','Fourth Year','PG'];
                  $selYear = $_POST['academic_year'] ?? '';
                  echo '<option value="">Select your year</option>';
                  foreach ($years as $y) {
                    $sel = ($selYear===$y)?'selected':''; echo "<option $sel>".h2($y)."</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="ev-row">
              <div>
                <label>Age</label>
                <select name="age">
                  <option value="">Select age</option>
                  <?php $selAge = (int)($_POST['age'] ?? 0);
                  for ($a=16; $a<=60; $a++){ $sel = ($selAge===$a)?'selected':''; echo "<option value=\"$a\" $sel>$a</option>"; } ?>
                </select>
              </div>
              <div>
                <label>Select Event *</label>
                <select name="event_id" required>
                  <option value="">-- Select Event --</option>
                  <?php foreach ($events as $ev): ?>
                    <?php
                      $label = h2($ev['event_name']);
                      $date = isset($ev['event_date']) && $ev['event_date'] ? h2($ev['event_date']) : '';
                      $time = isset($ev['event_time']) && $ev['event_time'] ? h2($ev['event_time']) : '';
                      $display = $label . ($date ? " — $date" : '') . ($time ? " $time" : '');
                    ?>
                    <option value="<?= (int)$ev['id'] ?>" <?= (isset($_POST['event_id']) && (int)$_POST['event_id']===(int)$ev['id'])?'selected':'' ?>>
                      <?= $display ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div style="margin-top:12px">
              <label>What do you hope to gain from this event?</label>
              <textarea name="description" placeholder="Share goals..."><?= h2($_POST['description'] ?? '') ?></textarea>
            </div>

            <!-- Centered, styled button -->
            <div class="ev-actions">
              <button class="btn-dl" type="submit">Register for Event</button>
            </div>
          </form>
        </div>

      <?php elseif ($tab === 'details'): ?>
        <h2 class="section-title">Event Details</h2>
        <?php if (empty($events)): ?>
          <p class="hint">No events available.</p>
        <?php else: ?>
          <div class="events-grid">
            <?php foreach ($events as $ev): ?>
              <div class="event-card">
                <!-- header: logo + name on one line -->
                <div class="event-header">
                  <div class="event-img-circle">
                    <img src="https://img.icons8.com/color/96/event-accepted.png" alt="event">
                  </div>
                  <h3 class="event-name"><?= h2($ev['event_name']) ?></h3>
                </div>

                <p class="event-desc"><?= h2($ev['description'] ?? '') ?></p>

                <!-- left-aligned info lines -->
                <div class="event-line">📍 <strong>Venue:</strong> <?= h2($ev['venue'] ?? '—') ?></div>
                <div class="event-line">📅 <strong>Date:</strong> <?= h2($ev['event_date'] ?? '—') ?></div>
                <div class="event-line">⏰ <strong>Time:</strong> <?= h2($ev['event_time'] ?? '—') ?></div>
                <div class="event-line">
                  👥 <strong>Participants:</strong>
                  <?= (int)($ev['reg_count'] ?? 0) ?><?php if (!empty($ev['max_participants'])) echo " / " . (int)$ev['max_participants']; ?>
                </div>

                <!-- centered register button -->
                <div class="card-actions">
                  <a href="event_management.php<?= $qUser ?>&tab=registration" class="register-btn">Register</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php elseif ($tab === 'prizes'): ?>
        <h2 class="section-title">Event Prizes</h2>
        <?php if (empty($events)): ?>
          <p class="hint">No events available.</p>
        <?php else: ?>
          <div class="events-grid">
            <?php foreach ($events as $ev): ?>
              <div class="event-card prizes">
                <div class="event-header">
                  <div class="event-img-circle">
                    <img src="https://img.icons8.com/color/96/prize.png" alt="prize">
                  </div>
                  <h3 class="event-name"><?= h2($ev['event_name']) ?></h3>
                </div>

                <p class="event-desc"><?= h2($ev['description'] ?? '') ?></p>
                <div class="divider"></div>

                <!-- centered prize lines -->
                <div class="prize-line">🥇 <strong>1st Prize:</strong> <?= h2($ev['prize1'] ?? '—') ?></div>
                <div class="prize-line">🥈 <strong>2nd Prize:</strong> <?= h2($ev['prize2'] ?? '—') ?></div>
                <div class="prize-line">🥉 <strong>3rd Prize:</strong> <?= h2($ev['prize3'] ?? '—') ?></div>

                <!-- centered register button -->
                <div class="card-actions">
                  <a href="event_management.php<?= $qUser ?>&tab=registration" class="register-btn">Register Now</a>
                </div>
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
