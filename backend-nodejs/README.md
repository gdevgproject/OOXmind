# OOXmind Backend API

This is a Node.js Express backend for the OOXmind application. It provides API endpoints for managing vocabulary content, practice sessions, and statistics.

## Setup

1. **Install dependencies:**

   ```bash
   npm install
   ```

2. **Configure environment variables:**
   Create a `.env` file in the root directory with the following variables:

   ```
   PORT=3000
   DB_HOST=localhost
   DB_USER=root
   DB_PASSWORD=
   DB_NAME=openyourself
   NODE_ENV=development
   ```

3. **Set up database:**
   Make sure MySQL is running and a database named 'openyourself' exists.

4. **Copy assets (optional):**
   If you're migrating from the PHP version, run this script to copy assets:

   ```bash
   node scripts/copy-assets.js
   ```

5. **Start the server:**
   ```bash
   npm start
   ```
   For development with auto-reload:
   ```bash
   npm run dev
   ```

## API Endpoints

### Content Endpoints

- `GET /api/content` - Get all content items
- `GET /api/content/:id` - Get content by ID
- `GET /api/content/search/:query` - Search content
- `POST /api/content` - Add new content
- `PUT /api/content/:id` - Update content
- `DELETE /api/content/:id` - Delete content
- `PUT /api/content/update-count/:id` - Update correct/incorrect counts
- `PUT /api/content/update-repetition/:id` - Update spaced repetition data

### Draft Content Endpoints

- `GET /api/draft` - Get all drafts
- `GET /api/draft/:id` - Get draft by ID
- `GET /api/draft/search/:query` - Search drafts
- `POST /api/draft` - Add new draft
- `PUT /api/draft/:id` - Update draft
- `DELETE /api/draft/:id` - Delete draft
- `POST /api/draft/accept/:id` - Accept draft (move to content)

### Practice Endpoints

- `GET /api/practice/vocab` - Get vocabulary for practice
- `GET /api/practice/vocab-time` - Get vocabulary based on time criteria
- `PUT /api/practice/update-time` - Update time spent
- `PUT /api/practice/update-reviewed-count` - Update vocabulary reviewed count
- `GET /api/practice/draft` - Get drafts for practice

### Statistics Endpoints

- `GET /api/statistic/activity` - Get activity statistics
- `GET /api/statistic/mastery` - Get mastery statistics
- `GET /api/statistic/count` - Get count statistics
- `PUT /api/statistic/count/:name` - Update count

## File Uploads

The API supports file uploads for images, audio, and video files. When creating or updating content, you can include file uploads as form-data with the following fields:

- `image` - Image file
- `audio` - Audio file
- `video` - Video file

## Database Structure

The database consists of the following tables:

- `content` - Main vocabulary content
- `draft_content` - Draft vocabulary content
- `activity_log` - Usage activity log
- `count` - Various counters
