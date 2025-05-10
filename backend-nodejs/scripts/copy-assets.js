const fs = require("fs");
const path = require("path");

// Define source and destination directories
const sourceRoot = path.join(__dirname, "..", "backend-php", "assets");
const destRoot = path.join(__dirname, "assets");

// Create the destination directory if it doesn't exist
if (!fs.existsSync(destRoot)) {
  fs.mkdirSync(destRoot, { recursive: true });
}

/**
 * Copy a directory recursively
 * @param {string} src  Source path
 * @param {string} dest Destination path
 */
function copyDir(src, dest) {
  // Create destination directory if it doesn't exist
  if (!fs.existsSync(dest)) {
    fs.mkdirSync(dest, { recursive: true });
  }

  // Get all files and directories in the source directory
  const entries = fs.readdirSync(src, { withFileTypes: true });

  for (const entry of entries) {
    const srcPath = path.join(src, entry.name);
    const destPath = path.join(dest, entry.name);

    // If entry is a directory, recursively copy it
    if (entry.isDirectory()) {
      copyDir(srcPath, destPath);
    } else {
      // Otherwise copy the file
      fs.copyFileSync(srcPath, destPath);
      console.log(`Copied: ${srcPath} -> ${destPath}`);
    }
  }
}

// Start the copy process
console.log("Starting asset copy process...");
copyDir(sourceRoot, destRoot);
console.log("Asset copy process completed.");
