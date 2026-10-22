<?php
/*
CREATE TABLE LostFound (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_item_name VARCHAR(255) NOT NULL,
  report_description TEXT,
  report_image VARCHAR(500),
  found_item_description TEXT,
  found_item_image VARCHAR(500),
  depot_item_image VARCHAR(500),
  responses TEXT, -- admin responses stored here
  username VARCHAR(100) NOT NULL,
  lost_location VARCHAR(255),
  found_location VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/
include 'header.php';
include 'db.php';

/* ========================
   AJAX: Save Response
   ======================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_response') {
    $item_id = intval($_POST['item_id']);
    $response = $conn->real_escape_string($_POST['response']);

    $sql = "UPDATE LostFound SET responses='$response' WHERE id=$item_id";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit;
}

/* ========================
   AJAX: Delete Item
   ======================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $conn->query("DELETE FROM LostFound WHERE id=$id");
    echo json_encode(["status" => "success"]);
    exit;
}

/* ========================
   Fetch Data
   ======================== */
// Fetch only lost items (not found)
$lostItems = $conn->query("
    SELECT * FROM LostFound 
    WHERE report_item_name IS NOT NULL 
    AND found_item_description IS NULL
")->fetch_all(MYSQLI_ASSOC);

// Fetch only found items
$foundItems = $conn->query("
    SELECT * FROM LostFound 
    WHERE found_item_description IS NOT NULL
")->fetch_all(MYSQLI_ASSOC);
?>

<link rel="stylesheet" href="assets/css/dashboard.css">

<style>
  .main { min-width: 0; padding: 20px; }

  .tracking-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
  }
  .track-card {
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 6px 14px rgba(18, 38, 63, 0.08);
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition: transform .2s ease, box-shadow .2s ease;
  }
  .track-card:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 16px 32px rgba(18, 38, 63, 0.15);
  }
  .track-card img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 10px;
  }
  .track-card h3 {
    margin: 0;
    font-size: 16px;
    color: #2b3a4a;
  }

  .detail-box {
    margin-top: 25px;
    display: flex;
    gap: 20px;
    padding: 20px;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(18, 38, 63, 0.08);
  }
  .detail-box img {
    width: 400px;
    height: 400px;
    object-fit: cover;
    border-radius: 12px;
  }
  .detail-info { flex: 1; }
  .detail-info h4 { margin: 0 0 10px; font-size: 20px; }
  .detail-info p { margin: 6px 0; color: #2a3443; }

  .response-form label { display: block; margin: 6px 0; }
  .response-form button,
  .delete-btn {
    padding: 8px 14px;
    border: none; border-radius: 12px;
    background: linear-gradient(180deg, #4facfe, #00f2fe);
    color: white; font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
    display: inline-block;
    transition: transform .18s ease, box-shadow .18s ease;
    text-decoration: none;
  }
  .response-form button:hover,
  .delete-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  }
  .delete-btn { background: #ff4b4b; }

  /* Popup Notification */
  .popup {
    position: fixed;
    top: 20px;
    right: 20px;
    min-width: 220px;
    text-align: center;
    font-weight: bold;
    padding: 12px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    opacity: 0;
    transform: translateY(-20px);
    transition: all 0.5s ease-in-out;
    z-index: 1000;
    pointer-events: none;
  }
  .popup.show {
    opacity: 1;
    transform: translateY(0);
  }
  .popup.success {
    background: #4caf50;
    color: white;
  }
  .popup.error {
    background: #ff4b4b;
    color: white;
  }
</style>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <nav>
      <a class="nav-item" onclick="showView('lost')">
        <span class="dot"><img src="assets/images/lost_report.png" alt="Lost"></span>
        <span class="nav-text">Lost Items</span>
      </a>
      <a class="nav-item" onclick="showView('found')">
        <span class="dot"><img src="assets/images/found_item.png" alt="Found"></span>
        <span class="nav-text">Found Items</span>
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="main" id="main-content"></main>
</div>

<!-- Popup container -->
<div id="popup" class="popup"></div>

<script>
  const lostItems = <?php echo json_encode($lostItems); ?>;
  const foundItems = <?php echo json_encode($foundItems); ?>;

  function showPopup(message, isError = false) {
    const popup = document.getElementById("popup");
    popup.textContent = message;
    popup.className = "popup"; // reset
    popup.classList.add(isError ? "error" : "success");

    void popup.offsetWidth; // reflow

    popup.classList.add("show");

    setTimeout(() => {
      popup.classList.remove("show");
    }, 3000);
  }

  function showView(view) {
    let container = document.getElementById("main-content");

    container.innerHTML = `<h2>${view === 'lost' ? 'Lost Items' : 'Found Items'}</h2><div class="tracking-grid" id="grid"></div>`;
    let grid = document.getElementById("grid");

    let items = (view === 'lost') ? lostItems : foundItems;
    items.forEach(item => {
      let card = document.createElement("div");
      card.className = "track-card";
      card.innerHTML = `
        <img src="${view === 'lost' ? item.report_image : item.found_item_image}" alt="Item">
        <h3>${view === 'lost' ? item.report_item_name : item.found_item_description}</h3>
      `;
      card.onclick = () => showDetail(view, item.id);
      grid.appendChild(card);
    });
  }

  function showDetail(view, id) {
    let items = (view === 'lost') ? lostItems : foundItems;
    let item = items.find(i => i.id == id);

    let container = document.getElementById("main-content");
    let html = `
      <h2>${view === 'lost' ? 'Lost Item Details' : 'Found Item Details'}</h2>
      <div class="detail-box">
        <img src="${view === 'lost' ? item.report_image : item.found_item_image}" alt="Item">
        <div class="detail-info">
          <p><b>Item Name:</b> ${view === 'lost' ? item.report_item_name : item.found_item_description}</p>
          ${view === 'lost' 
            ? `<p><b>Description:</b> ${item.report_description}</p>
               <p><b>Lost Location:</b> ${item.lost_location}</p>
               <form class="response-form" onsubmit="sendResponse(event, ${item.id})">
                 <label><input type="radio" name="response" value="Found" required> Found</label>
                 <label><input type="radio" name="response" value="Not Found"> Not Found</label>
                 <label><input type="radio" name="response" value="Need More Details"> Need More Details</label>
                 <button type="submit">Send Response</button>
               </form>`
            : `<p><b>Found Location:</b> ${item.found_location}</p>`}
          <a href="#" class="delete-btn" onclick="deleteItem(${item.id}, '${view}')">Delete</a>
        </div>
      </div>
    `;
    container.innerHTML = html;
  }

  function sendResponse(e, id) {
    e.preventDefault();
    let form = e.target;
    let response = form.querySelector('input[name="response"]:checked').value;

    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=send_response&item_id=${id}&response=${encodeURIComponent(response)}`
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        showPopup("Response sent successfully!");
        let item = lostItems.find(i => i.id == id);
        if (item) item.responses = response;
        showView('lost');
      } else {
        showPopup("Error: " + data.message, true);
      }
    });
  }

  function deleteItem(id, view) {
    if (!confirm("Are you sure you want to delete this item?")) return;

    fetch(`?action=delete&id=${id}`, { method: 'GET' })
    .then(res => res.json())
    .then(data => {
      if (data.status === "success") {
        showPopup("Item deleted successfully!");
        if (view === 'lost') {
          const index = lostItems.findIndex(i => i.id == id);
          if (index > -1) lostItems.splice(index, 1);
        } else {
          const index = foundItems.findIndex(i => i.id == id);
          if (index > -1) foundItems.splice(index, 1);
        }
        showView(view);
      } else {
        showPopup("Error deleting item", true);
      }
    });
  }

  // Default view
  window.onload = () => showView('lost');
</script>

<?php include 'footer.php'; ?>
