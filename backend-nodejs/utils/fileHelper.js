/**
 * Utilities for file path management
 */
const path = require("path");
const fs = require("fs");

/**
 * Normalizes file paths to use forward slashes
 * for consistent storage and comparison across platforms
 */
function normalizePath(filePath) {
  return filePath ? filePath.replace(/\\/g, "/") : "";
}

/**
 * Safely deletes a file if it exists
 * @param {string} filePath - Relative path from project root
 * @returns {boolean} True if file was deleted, false otherwise
 */
function safeDeleteFile(filePath) {
  if (!filePath) return false;

  try {
    // Get absolute path
    const absolutePath = path.resolve(__dirname, "..", filePath);

    // Check if file exists
    if (fs.existsSync(absolutePath)) {
      // Delete the file
      fs.unlinkSync(absolutePath);
      console.log(`File deleted: ${absolutePath}`);
      return true;
    }
    return false;
  } catch (err) {
    console.error(`Error deleting file ${filePath}:`, err);
    return false;
  }
}

module.exports = {
  normalizePath,
  safeDeleteFile,
};
