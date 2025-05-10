const express = require("express");
const router = express.Router();
const contentController = require("../controllers/contentController");
const upload = require("../middleware/upload");

// Get all content items
router.get("/", contentController.getAllContent);

// Search content
router.get("/search/:query", contentController.searchContent);

// Get a single content item by ID
router.get("/:id", contentController.getContentById);

// Add new content with file uploads
router.post(
  "/",
  upload.fields([
    { name: "image", maxCount: 1 },
    { name: "audio", maxCount: 1 },
    { name: "video", maxCount: 1 },
  ]),
  contentController.addContent
);

// Update content
router.put(
  "/:id",
  upload.fields([
    { name: "image", maxCount: 1 },
    { name: "audio", maxCount: 1 },
    { name: "video", maxCount: 1 },
  ]),
  contentController.updateContent
);

// Delete content
router.delete("/:id", contentController.deleteContent);

// Update correct/incorrect counts
router.put("/update-count/:id", contentController.updateCount);

// Update spaced repetition data
router.put("/update-repetition/:id", contentController.updateSpacedRepetition);

module.exports = router;
