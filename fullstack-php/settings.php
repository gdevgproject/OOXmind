<?php
require_once 'view/header.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'delete_image':
        $imagePath = $_POST['image_path'];
        if (file_exists($imagePath)) {
          unlink($imagePath);
        }
        break;

      case 'upload_image':
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === 0) {
          $uploadDir = 'assets/girl_background/';
          $fileName = time() . '_' . $_FILES['background_image']['name'];
          $uploadPath = $uploadDir . $fileName;

          if (move_uploaded_file($_FILES['background_image']['tmp_name'], $uploadPath)) {
            $success = "Image uploaded successfully!";
          } else {
            $error = "Failed to upload image.";
          }
        }
        break;

      case 'save_music_settings':
        $homeMusic = $_POST['home_music'] ?? 'assets/audio/mixisoundbg.mp3';
        $practiceMusic = $_POST['practice_music'] ?? 'assets/audio/mixisoundbg.mp3';

        // Save music settings to a JSON file
        $musicSettings = [
          'home_music' => $homeMusic,
          'practice_music' => $practiceMusic
        ];
        file_put_contents('assets/music_settings.json', json_encode($musicSettings));
        $success = "Music settings saved successfully!";
        break;
    }
  }
}

// Get current background images
$imageFolder = "assets/girl_background/";
$backgroundImages = [];
if (is_dir($imageFolder)) {
  $files = scandir($imageFolder);
  foreach ($files as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array(strtolower($extension), ["jpg", "jpeg", "png", "gif"])) {
      $backgroundImages[] = $imageFolder . $file;
    }
  }
}

// Get available music files
$musicFolder = "assets/audio/";
$musicFiles = [];
if (is_dir($musicFolder)) {
  $files = scandir($musicFolder);
  foreach ($files as $file) {
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (in_array(strtolower($extension), ["mp3", "wav", "ogg"])) {
      $musicFiles[] = $musicFolder . $file;
    }
  }
}

// Load current music settings
$currentMusicSettings = [
  'home_music' => 'assets/audio/mixisoundbg.mp3',
  'practice_music' => 'assets/audio/mixisoundbg.mp3'
];
if (file_exists('assets/music_settings.json')) {
  $savedSettings = json_decode(file_get_contents('assets/music_settings.json'), true);
  if ($savedSettings) {
    $currentMusicSettings = array_merge($currentMusicSettings, $savedSettings);
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings - OOXmind</title>
  <style>
    .settings-container {
      margin-top: 100px;
      padding: 20px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      backdrop-filter: blur(10px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .settings-section {
      margin-bottom: 40px;
      padding: 25px;
      background: rgba(255, 255, 255, 0.8);
      border-radius: 12px;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .section-title {
      font-size: 24px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #2c3e50;
      border-bottom: 3px solid #3498db;
      padding-bottom: 10px;
    }

    .image-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .image-item {
      position: relative;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      transition: transform 0.3s ease;
    }

    .image-item:hover {
      transform: scale(1.05);
    }

    .image-item img {
      width: 100%;
      height: 150px;
      object-fit: cover;
    }

    .image-overlay {
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: flex;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }

    .image-item:hover .image-overlay {
      opacity: 1;
    }

    .delete-btn {
      background: #e74c3c;
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 5px;
      cursor: pointer;
      font-weight: bold;
    }

    .delete-btn:hover {
      background: #c0392b;
    }

    .upload-section {
      margin-top: 20px;
      padding: 20px;
      border: 2px dashed #3498db;
      border-radius: 10px;
      text-align: center;
      background: rgba(52, 152, 219, 0.1);
    }

    .upload-btn {
      background: #3498db;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      margin-top: 10px;
    }

    .upload-btn:hover {
      background: #2980b9;
    }

    .music-settings {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-top: 20px;
    }

    .music-section {
      padding: 20px;
      background: rgba(155, 89, 182, 0.1);
      border-radius: 10px;
      border: 2px solid #9b59b6;
    }

    .music-section h4 {
      color: #8e44ad;
      margin-bottom: 15px;
      font-size: 18px;
    }

    .music-select {
      width: 100%;
      padding: 10px;
      border-radius: 5px;
      border: 2px solid #9b59b6;
      margin-bottom: 10px;
    }

    .save-music-btn {
      background: #9b59b6;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
    }

    .save-music-btn:hover {
      background: #8e44ad;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: bold;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .back-btn {
      background: #34495e;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      margin-bottom: 20px;
      text-decoration: none;
      display: inline-block;
    }

    .back-btn:hover {
      background: #2c3e50;
      text-decoration: none;
      color: white;
    }

    @media (max-width: 768px) {
      .music-settings {
        grid-template-columns: 1fr;
      }

      .image-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
      }
    }
  </style>
</head>

<body>
  <div class="settings-container">
    <a href="home.php" class="back-btn">← Back to Home</a>

    <h1 style="text-align: center; color: #2c3e50; margin-bottom: 30px;">⚙️ Settings</h1>

    <?php if (isset($success)): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Background Images Section -->
    <div class="settings-section">
      <h2 class="section-title">🖼️ Background Images Management</h2>

      <div class="upload-section">
        <h4>Upload New Background Image</h4>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="upload_image">
          <input type="file" name="background_image" accept="image/*" required>
          <br>
          <button type="submit" class="upload-btn">Upload Image</button>
        </form>
      </div>

      <div class="image-grid">
        <?php foreach ($backgroundImages as $image): ?>
          <div class="image-item">
            <img src="<?php echo $image; ?>" alt="Background Image">
            <div class="image-overlay">
              <form method="POST" style="margin: 0;">
                <input type="hidden" name="action" value="delete_image">
                <input type="hidden" name="image_path" value="<?php echo $image; ?>">
                <button type="submit" class="delete-btn" onclick="return confirm('Are you sure you want to delete this image?')">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if (empty($backgroundImages)): ?>
        <p style="text-align: center; color: #7f8c8d; margin-top: 20px;">No background images found.</p>
      <?php endif; ?>
    </div>

    <!-- Music Settings Section -->
    <div class="settings-section">
      <h2 class="section-title">🎵 Music Settings</h2>

      <form method="POST">
        <input type="hidden" name="action" value="save_music_settings">
        <div class="music-settings">
          <div class="music-section">
            <h4>Home Page Music</h4>
            <select name="home_music" class="music-select">
              <?php foreach ($musicFiles as $music): ?>
                <option value="<?php echo $music; ?>" <?php echo $currentMusicSettings['home_music'] === $music ? 'selected' : ''; ?>>
                  <?php echo basename($music); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <audio controls style="width: 100%;">
              <source src="<?php echo $currentMusicSettings['home_music']; ?>" type="audio/mpeg">
            </audio>
          </div>

          <div class="music-section">
            <h4>Practice Page Music</h4>
            <select name="practice_music" class="music-select">
              <?php foreach ($musicFiles as $music): ?>
                <option value="<?php echo $music; ?>" <?php echo $currentMusicSettings['practice_music'] === $music ? 'selected' : ''; ?>>
                  <?php echo basename($music); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <audio controls style="width: 100%;">
              <source src="<?php echo $currentMusicSettings['practice_music']; ?>" type="audio/mpeg">
            </audio>
          </div>
        </div>

        <button type="submit" class="save-music-btn" style="margin-top: 20px;">Save Music Settings</button>
      </form>
    </div>

    <!-- Future Settings Placeholder -->
    <div class="settings-section">
      <h2 class="section-title">🔧 General Settings</h2>
      <p style="color: #7f8c8d; text-align: center; padding: 40px;">More settings will be available here in future updates...</p>
    </div>
  </div>

  <script>
    // Preview music when selection changes
    document.querySelectorAll('.music-select').forEach(select => {
      select.addEventListener('change', function() {
        const audio = this.parentElement.querySelector('audio source');
        const audioElement = this.parentElement.querySelector('audio');
        audio.src = this.value;
        audioElement.load();
      });
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity 0.5s';
        setTimeout(() => alert.remove(), 500);
      });
    }, 5000);
  </script>
</body>

</html>