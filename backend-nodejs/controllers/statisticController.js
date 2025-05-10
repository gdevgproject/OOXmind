const db = require("../config/database");

// Get activity statistics
const getActivityStats = async (req, res) => {
  try {
    // Get activity logs for the last 30 days
    const results = await db.getAll(
      "SELECT * FROM activity_log ORDER BY activity_date DESC LIMIT 30"
    );

    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching activity statistics:", error);
    res.status(500).json({
      message: "Error fetching activity statistics",
      error: error.message,
    });
  }
};

// Get mastery statistics
const getMasteryStats = async (req, res) => {
  try {
    // Get various mastery statistics
    const totalItems = await db.getOne("SELECT COUNT(*) as total FROM content");
    const masteredItems = await db.getOne(
      "SELECT COUNT(*) as mastered FROM content WHERE level >= 5"
    );
    const inProgressItems = await db.getOne(
      "SELECT COUNT(*) as in_progress FROM content WHERE level > 0 AND level < 5"
    );
    const newItems = await db.getOne(
      "SELECT COUNT(*) as new FROM content WHERE level = 0"
    );

    // Get items by level
    const levelCounts = await db.getAll(
      "SELECT level, COUNT(*) as count FROM content GROUP BY level ORDER BY level"
    );

    // Get vocab vs question distribution
    const vocabDistribution = await db.getOne(`
      SELECT 
        COUNT(DISTINCT vocab) as vocab_count,
        COUNT(DISTINCT question) as question_count
      FROM content
    `);

    // Get top performing vocabulary (highest level)
    const topPerforming = await db.getAll(`
      SELECT content_id, vocab, level, correct_count, incorrect_count
      FROM content 
      ORDER BY level DESC, correct_count DESC
      LIMIT 10
    `);

    // Get lowest performing vocabulary (lowest level)
    const lowestPerforming = await db.getAll(`
      SELECT content_id, vocab, level, correct_count, incorrect_count
      FROM content 
      WHERE level >= 0
      ORDER BY level ASC, incorrect_count DESC
      LIMIT 10
    `);

    // Get review schedule distribution
    const reviewSchedule = await db.getAll(`
      SELECT 
        DATE(next_review) as review_date,
        COUNT(*) as count
      FROM content
      GROUP BY DATE(next_review)
      ORDER BY review_date ASC
      LIMIT 10
    `);

    // Get content type performance
    const contentTypePerformance = await db.getAll(`
      SELECT
        CASE 
          WHEN image_path != '' THEN 'With Image'
          ELSE 'No Image'
        END as has_image,
        CASE 
          WHEN audio_path != '' THEN 'With Audio'
          ELSE 'No Audio'
        END as has_audio,
        AVG(correct_count) as avg_correct,
        AVG(incorrect_count) as avg_incorrect
      FROM content
      GROUP BY has_image, has_audio
    `);

    // Get progress over time
    const progressOverTime = await db.getAll(`
      SELECT 
        DATE(last_review) as date,
        AVG(level) as avg_level
      FROM content
      GROUP BY DATE(last_review)
      ORDER BY date DESC
      LIMIT 30
    `);

    res.status(200).json({
      totalItems: totalItems.total,
      masteredItems: masteredItems.mastered,
      inProgressItems: inProgressItems.in_progress,
      newItems: newItems.new,
      levelCounts: levelCounts,
      vocabDistribution: vocabDistribution,
      topPerforming: topPerforming,
      lowestPerforming: lowestPerforming,
      reviewSchedule: reviewSchedule,
      contentTypePerformance: contentTypePerformance,
      progressOverTime: progressOverTime,
    });
  } catch (error) {
    console.error("Error fetching mastery statistics:", error);
    res.status(500).json({
      message: "Error fetching mastery statistics",
      error: error.message,
    });
  }
};

// Get count statistics
const getCountStats = async (req, res) => {
  try {
    // Get all count records
    const results = await db.getAll("SELECT * FROM count");
    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching count statistics:", error);
    res.status(500).json({
      message: "Error fetching count statistics",
      error: error.message,
    });
  }
};

// Update count
const updateCount = async (req, res) => {
  try {
    const { name } = req.params;
    const { value } = req.body;

    // Check if the count exists
    const existingCount = await db.getOne(
      "SELECT * FROM count WHERE count_name = ?",
      [name]
    );

    if (existingCount) {
      // Update existing count
      await db.execute(
        "UPDATE count SET count = count + ? WHERE count_name = ?",
        [value, name]
      );
    } else {
      // Create new count
      await db.execute("INSERT INTO count (count_name, count) VALUES (?, ?)", [
        name,
        value,
      ]);
    }

    res.status(200).json({
      message: "Count updated successfully",
      countName: name,
    });
  } catch (error) {
    console.error("Error updating count:", error);
    res
      .status(500)
      .json({ message: "Error updating count", error: error.message });
  }
};

module.exports = {
  getActivityStats,
  getMasteryStats,
  getCountStats,
  updateCount,
};
