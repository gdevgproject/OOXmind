const express = require("express");
const router = express.Router();
const statisticController = require("../controllers/statisticController");

// Get activity statistics
router.get("/activity", statisticController.getActivityStats);

// Get mastery statistics
router.get("/mastery", statisticController.getMasteryStats);

// Get count statistics
router.get("/count", statisticController.getCountStats);

// Update count statistics
router.put("/count/:name", statisticController.updateCount);

module.exports = router;
