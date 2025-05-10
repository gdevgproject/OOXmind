#!/bin/bash

echo "======================================"
echo "OOXmind Node.js Backend Setup"
echo "======================================"
echo ""
echo "This script will help you set up the OOXmind Node.js backend."
echo ""

echo "Step 1: Installing dependencies..."
npm install
if [ $? -ne 0 ]; then
    echo "Error installing dependencies. Please make sure Node.js is installed."
    exit 1
fi
echo "Dependencies installed successfully."
echo ""

echo "Step 2: Initializing database..."
npm run init-db
if [ $? -ne 0 ]; then
    echo "Error initializing database. Please check your database connection."
    exit 1
fi
echo "Database initialized successfully."
echo ""

echo "Step 3: Copying assets from PHP backend..."
npm run copy-assets
if [ $? -ne 0 ]; then
    echo "Error copying assets. Please check that the PHP backend assets directory exists."
    exit 1
fi
echo "Assets copied successfully."
echo ""

echo "======================================"
echo "Setup complete!"
echo "======================================"
echo ""
echo "To start the server in development mode:"
echo "npm run dev"
echo ""
echo "To start the server in production mode:"
echo "npm start"
echo ""
echo "The server will run on port 3000 by default."
echo "You can change this in the .env file."
echo ""
echo "Thank you for using OOXmind Node.js backend!"
echo "======================================"
