-- Step 1: Ensure the profiles table exists
DO $$
BEGIN
    -- Check if table exists
    IF NOT EXISTS (
        SELECT FROM pg_tables 
        WHERE schemaname = 'public' 
        AND tablename = 'profiles'
    ) THEN
        -- Create the table if it doesn't exist
        CREATE TABLE public.profiles (
            id SERIAL PRIMARY KEY,
            user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE NOT NULL,
            username TEXT UNIQUE,
            full_name TEXT,
            avatar_url TEXT,
            phone TEXT,
            location TEXT,
            education TEXT,
            board TEXT,
            field TEXT,
            is_complete BOOLEAN DEFAULT FALSE,
            points INTEGER DEFAULT 0
        );
        
        -- Enable Row Level Security
        ALTER TABLE public.profiles ENABLE ROW LEVEL SECURITY;
    ELSE
        -- If the table exists, add the points column if needed
        IF NOT EXISTS (
            SELECT FROM information_schema.columns 
            WHERE table_schema = 'public' 
            AND table_name = 'profiles' 
            AND column_name = 'points'
        ) THEN
            ALTER TABLE public.profiles ADD COLUMN points INTEGER DEFAULT 0;
        END IF;
    END IF;
END
$$;

-- Step 2: Create essential policies
-- First drop any existing policies to avoid errors
DROP POLICY IF EXISTS "Users can view their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can update their own profile" ON profiles;
DROP POLICY IF EXISTS "Users can insert their own profile" ON profiles;
DROP POLICY IF EXISTS "Allow public access to profiles" ON profiles;

-- Create new policies
CREATE POLICY "Users can view their own profile" ON profiles 
    FOR SELECT USING (auth.uid() = user_id);
    
CREATE POLICY "Users can update their own profile" ON profiles 
    FOR UPDATE USING (auth.uid() = user_id);
    
CREATE POLICY "Users can insert their own profile" ON profiles 
    FOR INSERT WITH CHECK (auth.uid() = user_id);
    
CREATE POLICY "Allow public access to profiles" ON profiles 
    FOR SELECT USING (true);

-- Step 3: Ensure the avatars bucket exists
INSERT INTO storage.buckets (id, name, public)
VALUES ('avatars', 'avatars', true)
ON CONFLICT (id) DO NOTHING;

-- Step 4: Create storage policy for avatar uploads
DROP POLICY IF EXISTS "Allow authenticated users to upload avatars" ON storage.objects;

CREATE POLICY "Allow authenticated users to upload avatars" ON storage.objects
    FOR INSERT TO authenticated
    WITH CHECK (bucket_id = 'avatars'); 