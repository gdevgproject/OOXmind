require("dotenv").config();
const mysql = require("mysql2/promise");
const fs = require("fs");
const path = require("path");

// Read the schema file
const schemaPath = path.join(__dirname, "..", "config", "schema.sql");
const schema = fs.readFileSync(schemaPath, "utf8");

// Split the schema into separate statements
const statements = schema.split(";").filter((statement) => statement.trim());

async function initializeDatabase() {
  let connection;
  try {
    // Create a connection without database name
    connection = await mysql.createConnection({
      host: process.env.DB_HOST || "localhost",
      user: process.env.DB_USER || "root",
      password: process.env.DB_PASSWORD || "",
    });

    console.log("Connected to MySQL server");

    // Create database if it doesn't exist
    await connection.query(
      `CREATE DATABASE IF NOT EXISTS ${process.env.DB_NAME || "openyourself"}`
    );
    console.log(
      `Database ${
        process.env.DB_NAME || "openyourself"
      } created or already exists`
    );

    // Use the database
    await connection.query(`USE ${process.env.DB_NAME || "openyourself"}`);

    // Execute each SQL statement
    for (const statement of statements) {
      if (statement.trim()) {
        await connection.query(statement);
      }
    }

    console.log("Database schema created successfully");
  } catch (error) {
    console.error("Failed to initialize database:", error);
  } finally {
    if (connection) {
      await connection.end();
      console.log("Database connection closed");
    }
  }
}

// Run the initialization
initializeDatabase();
