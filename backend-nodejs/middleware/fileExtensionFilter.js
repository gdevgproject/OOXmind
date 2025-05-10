/**
 * Security middleware to prevent accessing files with unauthorized extensions
 */
const path = require("path");

// List of allowed file extensions
const allowedExtensions = [
  // Images
  ".jpg",
  ".jpeg",
  ".png",
  ".gif",
  ".bmp",
  ".webp",
  ".svg",

  // Audio
  ".mp3",
  ".wav",
  ".ogg",
  ".aac",
  ".flac",
  ".alac",
  ".wma",

  // Video
  ".mp4",
  ".avi",
  ".flv",
  ".wmv",
  ".mov",
  ".mkv",

  // Other assets
  ".css",
  ".js",
  ".json",
  ".ico",
];

/**
 * Middleware to filter access to static files based on file extension
 */
const fileExtensionFilter = (req, res, next) => {
  // Get the file extension
  const ext = path.extname(req.path).toLowerCase();

  // Check if extension is allowed
  if (!ext || allowedExtensions.includes(ext)) {
    next();
  } else {
    res.status(403).json({
      message: "Access to this file type is forbidden",
    });
  }
};

module.exports = fileExtensionFilter;
