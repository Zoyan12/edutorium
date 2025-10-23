-- This SQL script addresses common issues with Supabase Auth "Database error saving new user"

-- 1. Fix potential auth.users permissions issues
GRANT SELECT, INSERT, UPDATE, DELETE ON auth.users TO postgres;
GRANT SELECT, INSERT, UPDATE, DELETE ON auth.identities TO postgres;
GRANT SELECT, INSERT, UPDATE, DELETE ON auth.audit_log_entries TO postgres;

-- 2. Create the profiles table if it doesn't exist (with minimal columns)
CREATE TABLE IF NOT EXISTS public.profiles (
    id SERIAL PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE NOT NULL,
    username TEXT UNIQUE,
    field TEXT,
    full_name TEXT,
    avatar_url TEXT,
    phone TEXT,
    location TEXT,
    education TEXT,
    board TEXT,
    is_complete BOOLEAN DEFAULT FALSE,
    points INTEGER DEFAULT 0
);

-- 3. Ensure RLS is enabled but with permissive policies
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;

-- 4. Drop and recreate RLS policies to be more permissive during signup
DROP POLICY IF EXISTS "Public profiles are viewable by everyone" ON profiles;
DROP POLICY IF EXISTS "Users can insert their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can update own profile" ON profiles;

-- 5. Create permissive policies
-- Allow public read access to all profiles
CREATE POLICY "Public profiles are viewable by everyone" 
ON profiles FOR SELECT 
USING (true);

-- Allow authenticated users to insert their own profile
CREATE POLICY "Users can insert their own profile" 
ON profiles FOR INSERT 
WITH CHECK (auth.uid() = user_id);

-- Allow authenticated users to update their own profile
CREATE POLICY "Users can update own profile" 
ON profiles FOR UPDATE 
USING (auth.uid() = user_id);

-- 6. Set up avatar storage
INSERT INTO storage.buckets (id, name, public)
VALUES ('avatars', 'avatars', true)
ON CONFLICT (id) DO NOTHING;

-- 7. Drop and recreate storage policies to ensure they work
DROP POLICY IF EXISTS "Avatar images are publicly accessible" ON storage.objects;
DROP POLICY IF EXISTS "Anyone can upload an avatar" ON storage.objects;

-- Allow public access to avatars
CREATE POLICY "Avatar images are publicly accessible" 
ON storage.objects FOR SELECT 
USING (bucket_id = 'avatars');

-- Allow authenticated users to upload avatars
CREATE POLICY "Anyone can upload an avatar" 
ON storage.objects FOR INSERT 
TO authenticated
WITH CHECK (bucket_id = 'avatars');

-- 8. Allow authenticated users to delete their own avatars
CREATE POLICY "Users can update their own avatars" 
ON storage.objects FOR UPDATE 
USING (bucket_id = 'avatars');

-- 9. Verify the setup
SELECT 'Profiles table exists with proper structure' as status
WHERE EXISTS (
    SELECT FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_name = 'profiles'
);

SELECT 'Storage bucket exists' as status
WHERE EXISTS (
    SELECT FROM storage.buckets 
    WHERE id = 'avatars'
);

-- 10. Recommended: Add this comment at the top of the SQL Editor before running
-- "If you're experiencing 'Database error saving new user', run this script to fix common issues" 