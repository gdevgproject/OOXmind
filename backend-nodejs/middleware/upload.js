const multer = require("multer");
const path = require("path");
const fs = require("fs");

// Configure storage
const storage = multer.diskStorage({
  destination: function (req, file, cb) {
    let folder = "";

    if (file.fieldname === "image") {
      folder = "uploads/image";
    } else if (file.fieldname === "audio") {
      folder = "uploads/audio";
    } else if (file.fieldname === "video") {
      folder = "uploads/video";
    }

    // Create directory if it doesn't exist
    const dir = path.join(__dirname, "..", folder);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }

    cb(null, folder);
  },
  filename: function (req, file, cb) {
    // Generate timestamp for unique filenames
    const timestamp = new Date()
      .toISOString()
      .replace(/[-:T.]/g, "_")
      .replace(/Z/g, "");

    // Get file extension
    const extension = path.extname(file.originalname);

    // Create filename with timestamp and original extension
    const filename = `${timestamp}_${file.fieldname}${extension}`;
    cb(null, filename);
  },
});

// File filter
const fileFilter = (req, file, cb) => {
  if (file.fieldname === "image") {
    // Accept images only
    if (!file.originalname.match(/\.(jpg|jpeg|png|gif|bmp|webp|svg)$/i)) {
      return cb(new Error("Only image files are allowed!"), false);
    }
  } else if (file.fieldname === "audio") {
    // Accept audio only
    if (!file.originalname.match(/\.(mp3|wav|ogg|aac|flac|alac|wma)$/i)) {
      return cb(new Error("Only audio files are allowed!"), false);
    }
  } else if (file.fieldname === "video") {
    // Accept video only
    if (!file.originalname.match(/\.(mp4|avi|flv|wmv|mov|mkv)$/i)) {
      return cb(new Error("Only video files are allowed!"), false);
    }
  }
  cb(null, true);
};

// Initialize multer
const upload = multer({
  storage: storage,
  fileFilter: fileFilter,
  limits: {
    fileSize: 10 * 1024 * 1024, // 10 MB
  },
});

module.exports = upload;
