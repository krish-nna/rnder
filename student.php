<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CompLog - Student Data</title>
  <link rel="stylesheet" href="student.css">
</head>
<body>
    <?php
    session_start();
    $isAdminLoggedIn = (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);
    ?>
    <script>
      const isAdminLoggedIn = <?php echo $isAdminLoggedIn ? 'true' : 'false'; ?>;
    </script>
    

  <header>
    <div class="top-row">
      <div class="logo">
        <img src="logo1.png" alt="Logo" style="border-radius: 10px;">
      </div>
      <div>
        <img class="dpu" src="dpu.png" alt="Logo" style="border-radius: 10px;">
      </div>
    </div>

    <div class="nav-bar">
      <nav>
        <a href="index2.html">Home</a>
        <a href="student.html">Students</a>
        <a href="login.html">Login</a>
      </nav>
    </div>
  </header>

  <main class="mcont">
    <div class="filters">
      <h3>Filters</h3>
      <label for="filterClass">Class</label>
      <select id="filterClass">
        <option value="all">All Classes</option>
      </select>
      
      <label for="filterRank">Rank</label>
      <select id="filterRank">
        <option value="all">All</option>
        <option value="top3">Top 3</option>
      </select>

      <div class="dbtc">
        <a href="download_template.php" id="download-btn" class="dbt">Download Format</a>
      </div>
      <div class="upload-section">
        <label for="upload-file" class="upload-label">Choose File</label>
        <input type="file" id="upload-file" accept=".xlsx" style="display: none;">
        <span class="filen" id="file-name"></span>
        <button id="upload-btn">Upload</button>
      </div>
    </div>

    <div class="rcont">
      <div class="category-tiles" id="categoryTiles">
        <!-- Student Tiles will be generated here based on the competition id passed via URL -->
      </div>
    </div>
  </main>

  <script src="student.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
  <div id="error-message" class="error-message"></div>
</body>
</html>
