-- Đảm bảo draft_content có đầy đủ các trường như content
ALTER TABLE draft_content
ADD COLUMN level int DEFAULT '0',
ADD COLUMN correct_count int DEFAULT '0',
ADD COLUMN incorrect_count int DEFAULT '0',
ADD COLUMN last_review timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN next_review timestamp NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN response_time int DEFAULT '0',
ADD COLUMN is_recovery tinyint(1) DEFAULT '0';

-- Thêm cột accepted vào bảng content
ALTER TABLE content ADD COLUMN accepted tinyint(1) DEFAULT '0';
