const db = require("../config/database");

// Get all drafts
const getAllDrafts = async (req, res) => {
  try {
    const results = await db.getAll(
      "SELECT * FROM draft_content ORDER BY create_time DESC"
    );
    res.status(200).json(results);
  } catch (error) {
    console.error("Error fetching drafts:", error);
    res
      .status(500)
      .json({ message: "Error fetching drafts", error: error.message });
  }
};

// Get draft by ID
const getDraftById = async (req, res) => {
  try {
    const { id } = req.params;
    const draft = await db.getOne(
      "SELECT * FROM draft_content WHERE draft_id = ?",
      [id]
    );

    if (!draft) {
      return res.status(404).json({ message: "Draft not found" });
    }

    res.status(200).json(draft);
  } catch (error) {
    console.error("Error fetching draft by ID:", error);
    res
      .status(500)
      .json({ message: "Error fetching draft", error: error.message });
  }
};

// Search drafts
const searchDrafts = async (req, res) => {
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
      `SELECT * FROM draft_content WHERE 
      LOWER(vocab) = ? OR 
      LOWER(def) = ? OR 
      LOWER(question) = ? OR 
      LOWER(answer) = ?`,
      [exactSearchTerm, exactSearchTerm, exactSearchTerm, exactSearchTerm]
    );

    // Partial match query
    const partialMatches = await db.getAll(
      `SELECT * FROM draft_content WHERE 
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
          (exactMatch) => exactMatch.draft_id === match.draft_id
        )
      ) {
        combinedResults.push(match);
      }
    });

    res.status(200).json(combinedResults);
  } catch (error) {
    console.error("Error searching drafts:", error);
    res
      .status(500)
      .json({ message: "Error searching drafts", error: error.message });
  }
};

// Add new draft
const addDraft = async (req, res) => {
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
      `INSERT INTO draft_content (vocab, part_of_speech, ipa, def, ex, question, answer, image_path, audio_path, video_path)
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
      message: "Draft added successfully",
      draftId: result.insertId,
    });
  } catch (error) {
    console.error("Error adding draft:", error);
    res
      .status(500)
      .json({ message: "Error adding draft", error: error.message });
  }
};

// Update draft
const updateDraft = async (req, res) => {
  try {
    const { id } = req.params;
    const { vocab, part_of_speech, ipa, def, ex, question, answer } = req.body;
    // Import file helper utilities
    const fileHelper = require("../utils/fileHelper");

    // Get existing draft to check file paths
    const existingDraft = await db.getOne(
      "SELECT image_path, audio_path, video_path FROM draft_content WHERE draft_id = ?",
      [id]
    );

    if (!existingDraft) {
      return res.status(404).json({ message: "Draft not found" });
    }

    // Initialize with existing paths
    let imagePath = existingDraft.image_path;
    let audioPath = existingDraft.audio_path;
    let videoPath = existingDraft.video_path;

    // Track old files that might need to be deleted
    const oldFiles = [];

    // Update paths if new files are uploaded
    if (req.files) {
      if (req.files.image && req.files.image[0]) {
        if (imagePath) oldFiles.push(imagePath);
        imagePath = fileHelper.normalizePath(req.files.image[0].path);
      }

      if (req.files.audio && req.files.audio[0]) {
        if (audioPath) oldFiles.push(audioPath);
        audioPath = fileHelper.normalizePath(req.files.audio[0].path);
      }

      if (req.files.video && req.files.video[0]) {
        if (videoPath) oldFiles.push(videoPath);
        videoPath = fileHelper.normalizePath(req.files.video[0].path);
      }
    }

    // Use transaction to ensure database consistency
    await db.withTransaction(async (connection) => {
      await connection.execute(
        `UPDATE draft_content
         SET vocab = ?, part_of_speech = ?, ipa = ?, def = ?, ex = ?, question = ?, answer = ?,
             image_path = ?, audio_path = ?, video_path = ?
         WHERE draft_id = ?`,
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
        fileHelper.safeDeleteFile(filePath);
      });
    });

    res.status(200).json({
      message: "Draft updated successfully",
      draftId: id,
    });
  } catch (error) {
    console.error("Error updating draft:", error);
    res
      .status(500)
      .json({ message: "Error updating draft", error: error.message });
  }
};

// Delete draft
const deleteDraft = async (req, res) => {
  try {
    const { id } = req.params;
    const fileHelper = require("../utils/fileHelper");

    // Get the draft to find file paths before deletion
    const draft = await db.getOne(
      "SELECT image_path, audio_path, video_path FROM draft_content WHERE draft_id = ?",
      [id]
    );

    if (!draft) {
      return res.status(404).json({ message: "Draft not found" });
    }

    // Use transaction to ensure database consistency
    await db.withTransaction(async (connection) => {
      // Delete the draft from database first
      await connection.execute("DELETE FROM draft_content WHERE draft_id = ?", [
        id,
      ]);

      // Delete physical files if they exist
      const filesToDelete = [
        draft.image_path,
        draft.audio_path,
        draft.video_path,
      ];
      filesToDelete.forEach((filePath) => {
        if (filePath) {
          fileHelper.safeDeleteFile(filePath);
        }
      });
    });

    res.status(200).json({
      message: "Draft deleted successfully",
      draftId: id,
    });
  } catch (error) {
    console.error("Error deleting draft:", error);
    res
      .status(500)
      .json({ message: "Error deleting draft", error: error.message });
  }
};

// Accept draft (move to content table)
const acceptDraft = async (req, res) => {
  try {
    const { id } = req.params;
    const fs = require("fs");
    const path = require("path");

    // Get the draft content
    const draft = await db.getOne(
      "SELECT * FROM draft_content WHERE draft_id = ?",
      [id]
    );

    if (!draft) {
      return res.status(404).json({ message: "Draft not found" });
    }

    // Function to copy and rename files if they exist
    const copyAndRenameFile = (oldFilePath, folder) => {
      if (!oldFilePath || !fs.existsSync(oldFilePath)) {
        return ""; // Return empty string if file doesn't exist
      }

      const timestamp = new Date()
        .toISOString()
        .replace(/[-:T.]/g, "_")
        .replace(/Z/g, "");
      const extension = path.extname(oldFilePath);
      const newFileName = `${timestamp}_${folder}${extension}`;
      const newFilePath = path.join(
        __dirname,
        "..",
        "uploads",
        folder,
        newFileName
      );

      try {
        fs.copyFileSync(oldFilePath, newFilePath);
        return newFilePath.replace(/\\/g, "/");
      } catch (err) {
        console.error(`Error copying file ${oldFilePath}:`, err);
        return "";
      }
    };

    // Copy files if they exist
    const newImagePath = copyAndRenameFile(draft.image_path, "image");
    const newAudioPath = copyAndRenameFile(draft.audio_path, "audio");
    const newVideoPath = copyAndRenameFile(draft.video_path, "video");

    // Insert into content table with new file paths
    await db.execute(
      `INSERT INTO content (vocab, part_of_speech, ipa, def, ex, question, answer, image_path, audio_path, video_path)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        draft.vocab,
        draft.part_of_speech,
        draft.ipa,
        draft.def,
        draft.ex,
        draft.question,
        draft.answer,
        newImagePath || draft.image_path,
        newAudioPath || draft.audio_path,
        newVideoPath || draft.video_path,
      ]
    );

    // Update the draft as accepted
    await db.execute(
      "UPDATE draft_content SET accepted = 1 WHERE draft_id = ?",
      [id]
    );

    res.status(200).json({
      message: "Draft accepted and moved to content successfully",
      draftId: id,
    });
  } catch (error) {
    console.error("Error accepting draft:", error);
    res
      .status(500)
      .json({ message: "Error accepting draft", error: error.message });
  }
};

module.exports = {
  getAllDrafts,
  getDraftById,
  searchDrafts,
  addDraft,
  updateDraft,
  deleteDraft,
  acceptDraft,
};
