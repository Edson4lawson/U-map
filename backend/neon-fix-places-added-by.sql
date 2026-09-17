-- Fix added_by column type from bigint to varchar to store usernames
ALTER TABLE places ALTER COLUMN added_by TYPE VARCHAR(255);
