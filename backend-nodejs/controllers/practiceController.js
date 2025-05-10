const db = require("../config/database");

// Get vocabulary for practice
const getVocabForPractice = async (req, res) => {
  try {
    // Get vocabulary that has response time > 40000ms (similar to the PHP version)
    const results = await db.getAll(
      "SELECT * FROM content WHERE response_time > 40000"
    );
    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching vocabulary for practice:", error);
    res
      .status(500)
      .json({ message: "Error fetching vocabulary", error: error.message });
  }
};

// Get vocabulary based on time criteria
const getVocabByTime = async (req, res) => {
  try {
    // Get vocabulary that needs to be reviewed based on next_review time
    const now = new Date().toISOString().slice(0, 19).replace("T", " ");

    const results = await db.getAll(
      "SELECT * FROM content WHERE next_review <= ? ORDER BY next_review",
      [now]
    );

    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching vocabulary by time:", error);
    res
      .status(500)
      .json({ message: "Error fetching vocabulary", error: error.message });
  }
};

// Update time spent
const updateTime = async (req, res) => {
  try {
    // The PHP version uses CURDATE() directly
    const currentDate = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    const { total_time_spent, add_time, close_time } = req.body;
    const date = req.body.date || currentDate;

    // Check if entry for today exists
    const existingLog = await db.getOne(
      "SELECT * FROM activity_log WHERE activity_date = ?",
      [date]
    );

    if (!existingLog) {
      // Create new entry if it doesn't exist
      await db.execute("INSERT INTO activity_log (activity_date) VALUES (?)", [
        date,
      ]);
    }

    // Handle the different update modes like in PHP
    if (total_time_spent !== undefined) {
      // Update total time
      await db.execute(
        "UPDATE activity_log SET total_time_spent = ? WHERE activity_date = ?",
        [total_time_spent, date]
      );
    }

    if (add_time !== undefined) {
      // Add to total time
      await db.execute(
        "UPDATE activity_log SET total_time_spent = total_time_spent + ? WHERE activity_date = ?",
        [add_time, date]
      );
    }

    if (close_time !== undefined) {
      // Update close time
      await db.execute(
        "UPDATE activity_log SET close_time = ? WHERE activity_date = ?",
        [close_time, date]
      );
    }

    res.status(200).json({
      message: "Time updated successfully",
      date: date,
      success: true,
    });
  } catch (error) {
    console.error("Error updating time:", error);
    res
      .status(500)
      .json({ message: "Error updating time", error: error.message });
  }
};

// Update vocabulary reviewed count
const updateVocabReviewedCount = async (req, res) => {
  try {
    // PHP version uses the current date automatically
    const currentDate = new Date().toISOString().slice(0, 10); // YYYY-MM-DD
    const date = req.body.date || currentDate;
    const count = req.body.count || 1; // Default to 1 if not specified

    // Check if entry for today exists
    const existingLog = await db.getOne(
      "SELECT * FROM activity_log WHERE activity_date = ?",
      [date]
    );

    if (existingLog) {
      // Update existing entry
      await db.execute(
        "UPDATE activity_log SET vocab_reviewed_count = vocab_reviewed_count + ? WHERE activity_date = ?",
        [count, date]
      );
    } else {
      // Create new entry
      await db.execute(
        "INSERT INTO activity_log (activity_date, vocab_reviewed_count) VALUES (?, ?)",
        [date, count]
      );
    }

    res.status(200).json({
      message: "Vocabulary reviewed count updated successfully",
      date: date,
      success: true,
    });
  } catch (error) {
    console.error("Error updating vocabulary reviewed count:", error);
    res.status(500).json({
      message: "Error updating vocabulary reviewed count",
      error: error.message,
    });
  }
};

// Get draft for practice
const getDraftForPractice = async (req, res) => {
  try {
    // Get all drafts for practice
    const results = await db.getAll(
      "SELECT * FROM draft_content WHERE accepted = 0"
    );
    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching drafts for practice:", error);
    res
      .status(500)
      .json({ message: "Error fetching drafts", error: error.message });
  }
};

module.exports = {
  getVocabForPractice,
  getVocabByTime,
  updateTime,
  updateVocabReviewedCount,
  getDraftForPractice,
};
