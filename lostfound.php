<?php
/* 
----------------------------------------
SQL Query for LostFound table
----------------------------------------
CREATE TABLE LostFound (
  id INT AUTO_INCREMENT PRIMARY KEY,
  report_item_name VARCHAR(255) NOT NULL,
  report_description TEXT,
  report_image VARCHAR(500),         -- store file path/URL (recommended)
  found_item_description TEXT,
  found_item_image VARCHAR(500),     -- store file path/URL
  responses TEXT,
  username VARCHAR(100) NOT NULL,
  lost_location VARCHAR(255),
  found_location VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
*/

session_start();
include("header.php");
include("db.php");

// ✅ Fallback: use ?username=111 if no session set
if (isset($_GET['username']) && !isset($_SESSION['username'])) {
    $_SESSION['username'] = $_GET['username'];
}
?>
<link rel="stylesheet" href="assets/css/dashboard.css">
<style>
/* Internal CSS for main body */
.main-body {
  padding: 20px;
}
.section {
  background: #fff;
  border-radius: 16px;
  padding: 24px;
  margin-bottom: 24px;
  box-shadow: 0 8px 20px rgba(0,0,0,0.08);
}
.section h2 {
  font-size: 28px;
  text-align: center;
  color: #1a73e8;
  font-weight: bold;
  margin-bottom: 20px;
}
.form-group {
  margin-bottom: 16px;
}
.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 6px;
}
.form-group input, 
.form-group textarea {
  width: 100%;
  padding: 10px;
  border-radius: 8px;
  border: 1px solid #ccc;
}
.upload-area {
  border: 2px dashed #6ca0dc;
  border-radius: 12px;
  padding: 30px;
  text-align: center;
  cursor: pointer;
  color: #2c6fbc;
  transition: background 0.3s, border 0.3s;
}
.upload-area.dragover {
  background: #eef6ff;
  border-color: #1b4f8a;
}
.upload-area img {
  max-width: 150px;
  margin-top: 15px;
  border-radius: 8px;
}
.btn {
  background: #2c6fbc;
  color: #fff;
  padding: 10px 16px;
  border: none;
  border-radius: 10px;
  font-weight: bold;
  cursor: pointer;
}
.btn:hover {
  background: #1b4f8a;
}
.btn-container {
  text-align: center;
  margin-top: 20px;
}
.card {
  background: #f7f9fc;
  border-radius: 14px;
  padding: 16px;
  margin-bottom: 14px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.card img {
  max-width: 120px;
  height: auto;
  border-radius: 10px;
  margin-bottom: 10px;
}
.alert {
  padding: 12px;
  border-radius: 8px;
  margin-bottom: 10px;
}
.alert.success { background: #d4edda; color: #155724; }
.alert.error { background: #f8d7da; color: #721c24; }
</style>

<div class="layout">
  <!-- Sidebar -->
  <aside class="sidebar">
    <nav>
      <a href="?page=lost" class="nav-item">
        <div class="dot"><img src="assets/images/lost_report.png" alt=""></div>
        <div class="nav-text">Report Lost Item</div>
      </a>
      <a href="?page=found" class="nav-item">
        <div class="dot"><img src="assets/images/found_item.png" alt=""></div>
        <div class="nav-text">Upload Found Item</div>
      </a>
      <a href="?page=responses" class="nav-item">
        <div class="dot"><img src="assets/images/response.png" alt=""></div>
        <div class="nav-text">Responses</div>
      </a>
    </nav>
  </aside>

  <!-- Main Body -->
  <main class="main-body">
    <?php
    $page = $_GET['page'] ?? 'lost';

    // ✅ Handle Lost Item Submission
    if (isset($_POST['report_lost']) && isset($_SESSION['username'])) {
        $name = $_POST['item_name'];
        $desc = $_POST['item_desc'];
        $loc  = $_POST['lost_location'];

        $target_db = "";
        if (!empty($_FILES['item_photo']['name'])) {
            $file   = $_FILES['item_photo']['name'];
            $target = __DIR__ . "/uploads/" . basename($file);
            $target_db = "uploads/" . basename($file);

            if (!move_uploaded_file($_FILES['item_photo']['tmp_name'], $target)) {
                echo "<div class='alert error'>❌ File upload failed.</div>";
            }
        }

        $sql = "INSERT INTO LostFound (username, report_item_name, report_description, report_image, lost_location) 
                VALUES ('{$_SESSION['username']}', '$name', '$desc', '$target_db', '$loc')";

        if (mysqli_query($conn, $sql)) {
            echo "<div class='alert success'>✅ Lost item reported successfully!</div>";
        } else {
            echo "<div class='alert error'>❌ Database Error: " . mysqli_error($conn) . "</div>";
        }
    }

    // ✅ Handle Found Item Submission
    if (isset($_POST['upload_found']) && isset($_SESSION['username'])) {
        $desc = $_POST['item_desc'];
        $loc  = $_POST['found_location'];

        $target_db = "";
        if (!empty($_FILES['item_photo']['name'])) {
            $file   = $_FILES['item_photo']['name'];
            $target = __DIR__ . "/uploads/" . basename($file);
            $target_db = "uploads/" . basename($file);

            if (!move_uploaded_file($_FILES['item_photo']['tmp_name'], $target)) {
                echo "<div class='alert error'>❌ File upload failed.</div>";
            }
        }

        $sql = "INSERT INTO LostFound (username, found_item_description, found_item_image, found_location) 
                VALUES ('{$_SESSION['username']}', '$desc', '$target_db', '$loc')";

        if (mysqli_query($conn, $sql)) {
            echo "<div class='alert success'>✅ uploaded successfully! submit item on office.</div>";
        } else {
            echo "<div class='alert error'>❌ Database Error: " . mysqli_error($conn) . "</div>";
        }
    }

    // ✅ Lost Item Form
    if ($page == 'lost') {
    ?>
      <div class="section">
        <h2>Report Lost Item</h2>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label>Item Name</label>
            <input type="text" name="item_name" placeholder="Eg: Phone, ID card, watch etc.." required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="item_desc" placeholder="Eg: Color of the product, condition, owner identity etc.." required></textarea>
          </div>
          <div class="form-group">
            <label>Lost Location</label>
            <input type="text" name="lost_location" placeholder="Eg: Near cantene, library, playground etc.." required>
          </div>
          <div class="form-group">
            <label>Upload Image (Optional)</label>
            <div class="upload-area" id="uploadLost">
              <p>Click or drag and drop image here</p>
              <input type="file" name="item_photo" id="lostFile" hidden>
              <div id="lostPreview"></div>
            </div>
          </div>
          <div class="btn-container">
            <button type="submit" name="report_lost" class="btn">Report Lost Item</button>
          </div>
        </form>
      </div>
    <?php
    }

    // ✅ Found Item Form
    if ($page == 'found') {
    ?>
      <div class="section">
        <h2>Upload Found Item</h2>
        <form method="POST" enctype="multipart/form-data">
          <div class="form-group">
            <label>Description</label>
            <textarea name="item_desc" placeholder="Eg: Color of the product, condition, owner identity etc.." required></textarea>
          </div>
          <div class="form-group">
            <label>Found Location</label>
            <input type="text" name="found_location" placeholder="Eg: Near canteen, library, 1st floor etc.." required>
          </div>
          <div class="form-group">
            <label>Upload Image (required)</label>
            <div class="upload-area" id="uploadFound">
              <p>Click or drag and drop image here</p>
              <input type="file" name="item_photo" id="foundFile" hidden required>
              <div id="foundPreview"></div>
            </div>
          </div>
          <div class="btn-container">
            <button type="submit" name="upload_found" class="btn">Upload Found Item</button>
          </div>
        </form>
      </div>
    <?php
    }

    // ✅ Responses
    if ($page == 'responses') {
        if (!isset($_SESSION['username'])) {
            echo "<div class='alert error'>You must be logged in to view responses.</div>";
        } else {
            $sql = "SELECT * FROM LostFound WHERE username='{$_SESSION['username']}' AND responses IS NOT NULL";
            $result = mysqli_query($conn, $sql);
            echo "<div class='section'><h2>Responses</h2>";
            if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='card'>
                            <img src='{$row['report_image']}' alt='item'>
                            <h3>{$row['report_item_name']}</h3>
                            <p>{$row['report_description']}</p>
                            <p><b>Lost Location:</b> {$row['lost_location']}</p>
                            <p><b>Found Location:</b> {$row['found_location']}</p>
                            <b>Response: {$row['responses']}</b>
                          </div>";
                }
            } else {
                echo "<p>No responses yet.</p>";
            }
            echo "</div>";
        }
    }
    ?>
  </main>
</div>

<script>
// Drag & Drop + Preview for Lost
const uploadLost = document.getElementById('uploadLost');
const lostFile = document.getElementById('lostFile');
const lostPreview = document.getElementById('lostPreview');
if(uploadLost){
  uploadLost.addEventListener('click', () => lostFile.click());
  uploadLost.addEventListener('dragover', e => {
    e.preventDefault(); uploadLost.classList.add('dragover');
  });
  uploadLost.addEventListener('dragleave', () => uploadLost.classList.remove('dragover'));
  uploadLost.addEventListener('drop', e => {
    e.preventDefault(); lostFile.files = e.dataTransfer.files;
    previewFile(lostFile, lostPreview);
    uploadLost.classList.remove('dragover');
  });
  lostFile.addEventListener('change', () => previewFile(lostFile, lostPreview));
}

// Drag & Drop + Preview for Found
const uploadFound = document.getElementById('uploadFound');
const foundFile = document.getElementById('foundFile');
const foundPreview = document.getElementById('foundPreview');
if(uploadFound){
  uploadFound.addEventListener('click', () => foundFile.click());
  uploadFound.addEventListener('dragover', e => {
    e.preventDefault(); uploadFound.classList.add('dragover');
  });
  uploadFound.addEventListener('dragleave', () => uploadFound.classList.remove('dragover'));
  uploadFound.addEventListener('drop', e => {
    e.preventDefault(); foundFile.files = e.dataTransfer.files;
    previewFile(foundFile, foundPreview);
    uploadFound.classList.remove('dragover');
  });
  foundFile.addEventListener('change', () => previewFile(foundFile, foundPreview));
}

// Preview helper
function previewFile(input, previewDiv){
  previewDiv.innerHTML = "";
  const file = input.files[0];
  if(file){
    const reader = new FileReader();
    reader.onload = e => {
      previewDiv.innerHTML = `<img src="${e.target.result}" alt="preview">`;
    }
    reader.readAsDataURL(file);
  }
}

// ✅ Auto-hide alerts after 4 seconds
setTimeout(() => {
  document.querySelectorAll('.alert').forEach(alert => {
    alert.style.transition = "opacity 0.5s";
    alert.style.opacity = "0";
    setTimeout(() => alert.remove(), 500);
  });
}, 4000);
</script>

<?php include("footer.php"); ?>
