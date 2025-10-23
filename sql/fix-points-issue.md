# How to Fix the Missing 'points' Column Issue

Follow these steps to fix the "column points does not exist" error:

## Option 1: Run the SQL Script in Supabase

1. Log in to your Supabase dashboard
2. Go to the SQL Editor
3. Copy and paste the following SQL script:

```sql
-- Add the missing points column to the profiles table
ALTER TABLE profiles ADD COLUMN IF NOT EXISTS points INTEGER DEFAULT 0;

-- Update any existing rows to have points=0
UPDATE profiles SET points = 0 WHERE points IS NULL;

-- Create an index on the points column for faster leaderboard queries
CREATE INDEX IF NOT EXISTS idx_profiles_points ON profiles(points DESC);
```

4. Run the script
5. Verify the column was added by running: 
```sql
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'profiles' AND column_name = 'points';
```

## Option 2: Use the full database setup script

If you're setting up your database from scratch, use the `setup-database.sql` file which includes all necessary tables, columns, and configuration.

1. Log in to your Supabase dashboard
2. Go to the SQL Editor
3. Copy and paste the entire content of the `setup-database.sql` file
4. Run the script

## Testing

After applying the fix:

1. Try signing up a new user
2. Complete the profile
3. Check that the user appears in the profiles table
4. Verify that the Top Players section on the dashboard works correctly

## What Was Changed?

The code has been updated to be more resilient:

1. The profile creation code now checks if the 'points' column exists before using it
2. The dashboard's Top Players section will fall back to ID-based sorting if points column is missing
3. All profile updates and inserts conditionally include the points field

These changes should ensure the application works properly whether or not the points column exists. 