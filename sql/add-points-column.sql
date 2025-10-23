-- Add the missing points column to the profiles table
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS points INTEGER DEFAULT 0;

-- Update any existing rows to have points=0
UPDATE profiles SET points = 0 WHERE points IS NULL;

-- Create an index on the points column for faster leaderboard queries
CREATE INDEX IF NOT EXISTS idx_profiles_points ON profiles(points DESC);

-- Verify the column was added
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'profiles' AND column_name = 'points'; 