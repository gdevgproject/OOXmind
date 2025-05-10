require("dotenv").config();
const express = require("express");
const cors = require("cors");
const path = require("path");
const fs = require("fs");

// Import routes
const contentRoutes = require("./routes/contentRoutes");
const draftContentRoutes = require("./routes/draftContentRoutes");
const practiceRoutes = require("./routes/practiceRoutes");
const statisticRoutes = require("./routes/statisticRoutes");

// Initialize express app
const app = express();
const PORT = process.env.PORT || 3000;

// Import rate limiter, compression, and security middlewares
const rateLimit = require("express-rate-limit");
const compression = require("compression");
const helmet = require("helmet");

// Rate limiting
const apiLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 100, // Limit each IP to 100 requests per windowMs
  standardHeaders: true, // Return rate limit info in the `RateLimit-*` headers
  legacyHeaders: false, // Disable the `X-RateLimit-*` headers
  message: "Too many requests from this IP, please try again after 15 minutes",
});

// Middleware
app.use(helmet()); // Add security headers
app.use(compression()); // Add compression for all routes
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Import middlewares
const fileExtensionFilter = require("./middleware/fileExtensionFilter");
const requestLogger = require("./middleware/logger");

// Add request logger
app.use(requestLogger);

// Serve static files with security filtering
app.use(
  "/assets",
  fileExtensionFilter,
  express.static(path.join(__dirname, "assets"))
);
app.use(
  "/uploads",
  fileExtensionFilter,
  express.static(path.join(__dirname, "uploads"))
);

// Check and create upload directories if they don't exist
const uploadDirs = ["uploads/image", "uploads/audio", "uploads/video"];
uploadDirs.forEach((dir) => {
  const dirPath = path.join(__dirname, dir);
  if (!fs.existsSync(dirPath)) {
    fs.mkdirSync(dirPath, { recursive: true });
  }
});

// Routes - Apply rate limiting to all API routes
app.use("/api/content", apiLimiter, contentRoutes);
app.use("/api/draft", apiLimiter, draftContentRoutes);
app.use("/api/practice", apiLimiter, practiceRoutes);
app.use("/api/statistic", apiLimiter, statisticRoutes);

// Root endpoint
app.get("/", (req, res) => {
  res.send("Welcome to OOXmind API");
});

// Health check endpoint for monitoring
app.get("/health", (req, res) => {
  const db = require("./config/database");
  db.testConnection()
    .then((connected) => {
      if (connected) {
        res.status(200).json({
          status: "UP",
          database: "UP",
          uptime: process.uptime(),
          timestamp: new Date().toISOString(),
        });
      } else {
        res.status(503).json({
          status: "UP",
          database: "DOWN",
          uptime: process.uptime(),
          timestamp: new Date().toISOString(),
        });
      }
    })
    .catch((err) => {
      res.status(503).json({
        status: "UP",
        database: "DOWN",
        error: err.message,
        uptime: process.uptime(),
        timestamp: new Date().toISOString(),
      });
    });
});

// Handle 404
app.use((req, res) => {
  res.status(404).json({
    message: "Route not found",
  });
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).json({
    message: "Something went wrong!",
    error: process.env.NODE_ENV === "development" ? err.message : {},
  });
});

// Start server
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
