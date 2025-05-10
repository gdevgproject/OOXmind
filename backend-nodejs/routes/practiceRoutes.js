const express = require("express");
const router = express.Router();
const practiceController = require("../controllers/practiceController");

// Get vocabulary for practice
router.get("/vocab", practiceController.getVocabForPractice);

// Get vocab based on time criteria
router.get("/vocab-time", practiceController.getVocabByTime);

// Update time spent
router.put("/update-time", practiceController.updateTime);

// Update vocab reviewed count
router.put(
  "/update-reviewed-count",
  practiceController.updateVocabReviewedCount
);

// Draft practice routes
router.get("/draft", practiceController.getDraftForPractice);

module.exports = router;
