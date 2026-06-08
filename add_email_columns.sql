-- Add email column to walas table
ALTER TABLE walas 
ADD COLUMN email varchar(100) DEFAULT NULL UNIQUE AFTER password;

-- Add email column to bk table
ALTER TABLE bk 
ADD COLUMN email varchar(100) DEFAULT NULL UNIQUE AFTER password;

-- Optional: Set placeholder emails for existing records (based on NPSN)
-- Uncomment these if you want to auto-generate placeholder emails
-- UPDATE walas SET email = CONCAT('walas_', npsn, '@school.local') WHERE email IS NULL;
-- UPDATE bk SET email = CONCAT('bk_', npsn, '@school.local') WHERE email IS NULL;
