/**
 * Simple request logger middleware
 */
const fs = require("fs");
const path = require("path");

// Ensure logs directory exists
const logsDir = path.join(__dirname, "..", "logs");
if (!fs.existsSync(logsDir)) {
  fs.mkdirSync(logsDir, { recursive: true });
}

// Log format: [timestamp] method url ip statusCode responseTime ms
function requestLogger(req, res, next) {
  // Record start time
  const start = process.hrtime();

  // Add response event listener to log once the response is sent
  res.on("finish", () => {
    // Calculate response time
    const hrtime = process.hrtime(start);
    const responseTime = hrtime[0] * 1000 + hrtime[1] / 1000000;

    // Format the log entry
    const timestamp = new Date().toISOString();
    const method = req.method;
    const url = req.originalUrl || req.url;
    const ip = req.ip || req.connection.remoteAddress;
    const statusCode = res.statusCode;

    const logEntry = `[${timestamp}] ${method} ${url} ${ip} ${statusCode} ${responseTime.toFixed(
      2
    )}ms\n`;

    // Write to log file (daily rotation)
    const today = new Date().toISOString().split("T")[0]; // YYYY-MM-DD
    const logFile = path.join(logsDir, `api-${today}.log`);

    fs.appendFile(logFile, logEntry, (err) => {
      if (err) {
        console.error("Error writing to log file:", err);
      }
    });

    // Also log to console in development
    if (process.env.NODE_ENV !== "production") {
      console.log(logEntry.trim());
    }
  });

  next();
}

module.exports = requestLogger;
