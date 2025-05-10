const db = require("../config/database");

// Get all content items
const getAllContent = async (req, res) => {
  try {
    const results = await db.getAll(
      "SELECT * FROM content ORDER BY last_review DESC"
    );
    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching content:", error);
    res
      .status(500)
      .json({ message: "Error fetching content", error: error.message });
  }
};

// Get content by ID
const getContentById = async (req, res) => {
  try {
    const { id } = req.params;
    const content = await db.getOne(
      "SELECT * FROM content WHERE content_id = ?",
      [id]
    );

    if (!content) {
      return res.status(404).json({ message: "Content not found" });
    }

    res.status(200).json(content);
  } catch (error) {
    console.error("Error fetching content by ID:", error);
    res
      .status(500)
      .json({ message: "Error fetching content", error: error.message });
  }
};

// Search content
const searchContent = async (req, res) => {
  try {
    const { query } = req.params;
    const searchTerm = `%${query}%`;
    const exactSearchTerm = query.toLowerCase();

    // Check if search query is too long
    if (query.length > 600) {
      return res
        .status(400)
        .json({ message: "Search query must not exceed 600 characters" });
    }

    // Exact match query first
    const exactMatches = await db.getAll(
      `SELECT * FROM content WHERE 
      LOWER(vocab) = ? OR 
      LOWER(def) = ? OR 
      LOWER(question) = ? OR 
      LOWER(answer) = ?`,
      [exactSearchTerm, exactSearchTerm, exactSearchTerm, exactSearchTerm]
    );

    // Partial match query
    const partialMatches = await db.getAll(
      `SELECT * FROM content WHERE 
      LOWER(vocab) LIKE ? OR 
      LOWER(def) LIKE ? OR 
      LOWER(question) LIKE ? OR 
      LOWER(answer) LIKE ?`,
      [searchTerm, searchTerm, searchTerm, searchTerm]
    );

    // Combine results, putting exact matches first
    let combinedResults = [...exactMatches];

    // Add partial matches that aren't already in exact matches
    partialMatches.forEach((match) => {
      if (
        !combinedResults.some(
          (exactMatch) => exactMatch.content_id === match.content_id
        )
      ) {
        combinedResults.push(match);
      }
    });

    res.status(200).json(combinedResults);
  } catch (error) {
    console.error("Error searching content:", error);
    res
      .status(500)
      .json({ message: "Error searching content", error: error.message });
  }
};

// Add new content
const addContent = async (req, res) => {
  try {
    const { vocab, part_of_speech, ipa, def, ex, question, answer } = req.body;

    // Process file paths
    let imagePath = "";
    let audioPath = "";
    let videoPath = "";

    if (req.files) {
      if (req.files.image && req.files.image[0]) {
        imagePath = req.files.image[0].path.replace(/\\/g, "/");
      }

      if (req.files.audio && req.files.audio[0]) {
        audioPath = req.files.audio[0].path.replace(/\\/g, "/");
      }

      if (req.files.video && req.files.video[0]) {
        videoPath = req.files.video[0].path.replace(/\\/g, "/");
      }
    }

    const result = await db.execute(
      `INSERT INTO content (vocab, part_of_speech, ipa, def, ex, question, answer, image_path, audio_path, video_path)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        vocab,
        part_of_speech,
        ipa,
        def,
        ex,
        question,
        answer,
        imagePath,
        audioPath,
        videoPath,
      ]
    );

    res.status(201).json({
      message: "Content added successfully",
      contentId: result.insertId,
    });
  } catch (error) {
    console.error("Error adding content:", error);
    res
      .status(500)
      .json({ message: "Error adding content", error: error.message });
  }
};

// Update content
const updateContent = async (req, res) => {
  try {
    const { id } = req.params;
    const { vocab, part_of_speech, ipa, def, ex, question, answer } = req.body;
    const fs = require("fs");
    const path = require("path");

    // Get existing content to check file paths
    const existingContent = await db.getOne(
      "SELECT image_path, audio_path, video_path FROM content WHERE content_id = ?",
      [id]
    );

    if (!existingContent) {
      return res.status(404).json({ message: "Content not found" });
    }

    // Initialize with existing paths
    let imagePath = existingContent.image_path;
    let audioPath = existingContent.audio_path;
    let videoPath = existingContent.video_path;

    // Track old files that might need to be deleted
    const oldFiles = [];

    // Update paths if new files are uploaded
    if (req.files) {
      if (req.files.image && req.files.image[0]) {
        if (imagePath) oldFiles.push(imagePath);
        imagePath = req.files.image[0].path.replace(/\\/g, "/");
      }

      if (req.files.audio && req.files.audio[0]) {
        if (audioPath) oldFiles.push(audioPath);
        audioPath = req.files.audio[0].path.replace(/\\/g, "/");
      }

      if (req.files.video && req.files.video[0]) {
        if (videoPath) oldFiles.push(videoPath);
        videoPath = req.files.video[0].path.replace(/\\/g, "/");
      }
    }

    // Use transaction to ensure database consistency
    await db.withTransaction(async (connection) => {
      await connection.execute(
        `UPDATE content
         SET vocab = ?, part_of_speech = ?, ipa = ?, def = ?, ex = ?, question = ?, answer = ?,
             image_path = ?, audio_path = ?, video_path = ?
         WHERE content_id = ?`,
        [
          vocab,
          part_of_speech,
          ipa,
          def,
          ex,
          question,
          answer,
          imagePath,
          audioPath,
          videoPath,
          id,
        ]
      );

      // Delete old files if they were replaced
      oldFiles.forEach((filePath) => {
        try {
          const absolutePath = path.resolve(__dirname, "..", filePath);
          if (fs.existsSync(absolutePath)) {
            fs.unlinkSync(absolutePath);
            console.log(`Old file deleted: ${absolutePath}`);
          }
        } catch (err) {
          console.error(`Error deleting old file ${filePath}:`, err);
        }
      });
    });

    res.status(200).json({
      message: "Content updated successfully",
      contentId: id,
    });
  } catch (error) {
    console.error("Error updating content:", error);
    res
      .status(500)
      .json({ message: "Error updating content", error: error.message });
  }
};

// Delete content
const deleteContent = async (req, res) => {
  try {
    const { id } = req.params;
    const fileHelper = require("../utils/fileHelper");

    // Get the content to find file paths before deletion
    const content = await db.getOne(
      "SELECT image_path, audio_path, video_path FROM content WHERE content_id = ?",
      [id]
    );

    if (!content) {
      return res.status(404).json({ message: "Content not found" });
    }

    // Use transaction to ensure database consistency
    await db.withTransaction(async (connection) => {
      // Delete the content from database first
      await connection.execute("DELETE FROM content WHERE content_id = ?", [
        id,
      ]);

      // Delete physical files if they exist
      const filesToDelete = [
        content.image_path,
        content.audio_path,
        content.video_path,
      ];
      filesToDelete.forEach((filePath) => {
        if (filePath) {
          fileHelper.safeDeleteFile(filePath);
        }
      });
    });

    res.status(200).json({
      message: "Content deleted successfully",
      contentId: id,
    });
  } catch (error) {
    console.error("Error deleting content:", error);
    res
      .status(500)
      .json({ message: "Error deleting content", error: error.message });
  }
};

// Update correct/incorrect count
const updateCount = async (req, res) => {
  try {
    const { id } = req.params;
    const { isCorrect } = req.body;

    let updateField = isCorrect ? "correct_count" : "incorrect_count";

    await db.execute(
      `UPDATE content SET ${updateField} = ${updateField} + 1 WHERE content_id = ?`,
      [id]
    );

    res.status(200).json({
      message: `${updateField} updated successfully`,
      contentId: id,
    });
  } catch (error) {
    console.error("Error updating count:", error);
    res
      .status(500)
      .json({ message: "Error updating count", error: error.message });
  }
};

// Update spaced repetition data
const updateSpacedRepetition = async (req, res) => {
  try {
    const { id } = req.params;
    const { responseTime, isCorrect } = req.body;
    const currentDateTime = new Date()
      .toISOString()
      .slice(0, 19)
      .replace("T", " ");

    // Get content info first
    const contentInfo = await db.getOne(
      "SELECT correct_count, incorrect_count, is_recovery, level FROM content WHERE content_id = ?",
      [id]
    );

    if (!contentInfo) {
      return res.status(404).json({ message: "Content not found" });
    }

    const isRecovery = contentInfo.is_recovery;
    let level = contentInfo.level;

    // Handle response based on correct/incorrect
    if (isCorrect) {
      // Handle correct response
      if (isRecovery === 0) {
        // Not in recovery mode: increase correct count
        await db.execute(
          "UPDATE content SET correct_count = correct_count + 1 WHERE content_id = ?",
          [id]
        );
      } else {
        // In recovery mode: reset recovery state
        await db.execute(
          "UPDATE content SET is_recovery = 0 WHERE content_id = ?",
          [id]
        );
      }

      // Get updated info after modifications
      const updatedInfo = await db.getOne(
        "SELECT correct_count, incorrect_count FROM content WHERE content_id = ?",
        [id]
      );

      level = updatedInfo.correct_count - updatedInfo.incorrect_count;

      // Calculate next review interval based on level
      const interval = calculateNextReviewInterval(level);

      // Update level and next review time
      await db.execute(
        `UPDATE content SET 
         level = ?, 
         last_review = ?, 
         response_time = ?,
         next_review = DATE_ADD(?, INTERVAL ${interval})
         WHERE content_id = ?`,
        [level, currentDateTime, responseTime, currentDateTime, id]
      );
    } else {
      // Handle incorrect response
      if (isRecovery === 0) {
        if (level < 6) {
          // Low level: increase incorrect count if needed
          if (contentInfo.incorrect_count < contentInfo.correct_count) {
            await db.execute(
              "UPDATE content SET incorrect_count = incorrect_count + 1 WHERE content_id = ?",
              [id]
            );
          }

          // Get updated info after modifications
          const updatedInfo = await db.getOne(
            "SELECT correct_count, incorrect_count FROM content WHERE content_id = ?",
            [id]
          );

          level = updatedInfo.correct_count - updatedInfo.incorrect_count;

          // Calculate next review interval based on level
          const interval = calculateNextReviewInterval(level);

          // Update level and next review time
          await db.execute(
            `UPDATE content SET 
             level = ?, 
             last_review = ?, 
             response_time = ?,
             next_review = DATE_ADD(?, INTERVAL ${interval})
             WHERE content_id = ?`,
            [level, currentDateTime, responseTime, currentDateTime, id]
          );
        } else {
          // High level: enter recovery mode
          await db.execute(
            "UPDATE content SET is_recovery = 1 WHERE content_id = ?",
            [id]
          );

          // Increase incorrect count if needed
          if (contentInfo.incorrect_count < contentInfo.correct_count) {
            await db.execute(
              "UPDATE content SET incorrect_count = incorrect_count + 1 WHERE content_id = ?",
              [id]
            );
          }

          // Get updated info after modifications
          const updatedInfo = await db.getOne(
            "SELECT correct_count, incorrect_count FROM content WHERE content_id = ?",
            [id]
          );

          level = updatedInfo.correct_count - updatedInfo.incorrect_count;

          // Update with recovery mode (always 60 minutes)
          await db.execute(
            `UPDATE content SET 
             level = ?, 
             last_review = ?, 
             response_time = ?,
             next_review = DATE_ADD(?, INTERVAL 60 MINUTE)
             WHERE content_id = ?`,
            [level, currentDateTime, responseTime, currentDateTime, id]
          );
        }
      } else {
        // Already in recovery mode
        if (contentInfo.incorrect_count < contentInfo.correct_count) {
          await db.execute(
            "UPDATE content SET incorrect_count = incorrect_count + 1 WHERE content_id = ?",
            [id]
          );
        }

        // Get updated info after modifications
        const updatedInfo = await db.getOne(
          "SELECT correct_count, incorrect_count FROM content WHERE content_id = ?",
          [id]
        );

        level = updatedInfo.correct_count - updatedInfo.incorrect_count;

        // Update with recovery mode (always 60 minutes)
        await db.execute(
          `UPDATE content SET 
           level = ?, 
           last_review = ?, 
           response_time = ?,
           next_review = DATE_ADD(?, INTERVAL 60 MINUTE)
           WHERE content_id = ?`,
          [level, currentDateTime, responseTime, currentDateTime, id]
        );
      }
    }

    res.status(200).json({
      message: "Spaced repetition data updated successfully",
      contentId: id,
      level,
      isRecovery: contentInfo.is_recovery,
    });
  } catch (error) {
    console.error("Error updating spaced repetition data:", error);
    res.status(500).json({
      message: "Error updating spaced repetition data",
      error: error.message,
    });
  }
};

// Helper function to calculate next review interval based on level
function calculateNextReviewInterval(level) {
  const intervals = {
    1: "70 MINUTE",
    2: "12 HOUR",
    3: "23 HOUR",
    4: "47 HOUR",
    5: "71 HOUR",
    6: "359 HOUR",
    7: "1103 HOUR",
    8: "3407 HOUR",
    9: "8567 HOUR",
    10: "21418 HOUR",
    11: "43800 HOUR",
  };

  if (level <= 0) {
    return "30 MINUTE";
  } else if (level > 11) {
    return "43800 HOUR"; // About 5 years
  } else {
    return intervals[level];
  }
}

module.exports = {
  getAllContent,
  getContentById,
  searchContent,
  addContent,
  updateContent,
  deleteContent,
  updateCount,
  updateSpacedRepetition,
};
