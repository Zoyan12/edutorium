# How to Fix the Database Errors

You're encountering two main errors:

1. `ERROR: 42703: column "points" does not exist` - The points column is missing in your profiles table
2. `ERROR: 42601: syntax error at or near "NOT"` - The SQL syntax for policy creation is incorrect

## Quick Fix Instructions

1. Log in to your Supabase dashboard
2. Go to SQL Editor
3. Copy and paste the following SQL script:

```sql
-- This script fixes database issues with your profiles table and policies
DO $$
BEGIN
    -- Add points column if it doesn't exist
    IF NOT EXISTS (
        SELECT FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'profiles' 
        AND column_name = 'points'
    ) THEN
        ALTER TABLE public.profiles ADD COLUMN points INTEGER DEFAULT 0;
    END IF;
END
$$;

-- Fix policies (first drop existing ones)
DROP POLICY IF EXISTS "Users can view their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can update their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can insert their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can view all profiles" ON profiles;

-- Create correct policies
CREATE POLICY "Users can view their own profile" ON profiles 
    FOR SELECT USING (auth.uid() = user_id);
    
CREATE POLICY "Users can update their own profile" ON profiles 
    FOR UPDATE USING (auth.uid() = user_id);
    
CREATE POLICY "Users can insert their own profile" ON profiles 
    FOR INSERT WITH CHECK (auth.uid() = user_id);
    
CREATE POLICY "Users can view all profiles" ON profiles 
    FOR SELECT USING (true);

-- Make sure storage is set up
INSERT INTO storage.buckets (id, name, public)
VALUES ('avatars', 'avatars', true)
ON CONFLICT (id) DO NOTHING;

DROP POLICY IF EXISTS "Allow avatar uploads" ON storage.objects;

CREATE POLICY "Allow avatar uploads" ON storage.objects
    FOR INSERT TO authenticated
    WITH CHECK (bucket_id = 'avatars');
```

4. Run the script
5. Try signing up a new user again

## What These Issues Mean

### Issue 1: Missing "points" Column
The code is trying to access or store a "points" field, but this column doesn't exist in your database yet. The fix adds this column.

### Issue 2: SQL Syntax Error
PostgreSQL doesn't support `IF NOT EXISTS` for policy creation. The fix removes this syntax and instead drops existing policies before creating new ones.

## Additional Database Setup

If you need to set up your database from scratch, use the `simple-db-fix.sql` script which is more comprehensive and handles both table creation and policy setup in a more robust way.

## Testing After Fix

After applying these fixes:
1. Try signing up with a new email/username
2. Complete the profile form
3. Visit the dashboard to see if everything works correctly

Let me know if you encounter any other issues after applying these fixes! 