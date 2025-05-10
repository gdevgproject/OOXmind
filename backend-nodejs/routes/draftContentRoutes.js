const express = require("express");
const router = express.Router();
const draftController = require("../controllers/draftController");
const upload = require("../middleware/upload");

// Get all draft content items
router.get("/", draftController.getAllDrafts);

// Search drafts
router.get("/search/:query", draftController.searchDrafts);

// Get a single draft content item by ID
router.get("/:id", draftController.getDraftById);

// Add new draft with file uploads
router.post(
  "/",
  upload.fields([
    { name: "image", maxCount: 1 },
    { name: "audio", maxCount: 1 },
    { name: "video", maxCount: 1 },
  ]),
  draftController.addDraft
);

// Update draft
router.put(
  "/:id",
  upload.fields([
    { name: "image", maxCount: 1 },
    { name: "audio", maxCount: 1 },
    { name: "video", maxCount: 1 },
  ]),
  draftController.updateDraft
);

// Delete draft
router.delete("/:id", draftController.deleteDraft);

// Accept draft (move to content table)
router.post("/accept/:id", draftController.acceptDraft);

module.exports = router;
