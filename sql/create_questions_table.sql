-- Create the questions table for storing quiz questions with images
CREATE TABLE IF NOT EXISTS public.questions (
    id SERIAL PRIMARY KEY,
    image_url TEXT NOT NULL,
    correct_answer CHAR(1) NOT NULL CHECK (correct_answer IN ('A', 'B', 'C', 'D')),
    subject VARCHAR(50),
    difficulty VARCHAR(20) CHECK (difficulty IN ('easy', 'medium', 'hard')) DEFAULT 'medium',
    created_by UUID REFERENCES auth.users(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_questions_subject ON questions(subject);
CREATE INDEX IF NOT EXISTS idx_questions_difficulty ON questions(difficulty);
CREATE INDEX IF NOT EXISTS idx_questions_created_by ON questions(created_by);

-- Enable Row Level Security
ALTER TABLE public.questions ENABLE ROW LEVEL SECURITY;

-- Create RLS policies to control access
-- 1. Anyone can view questions
CREATE POLICY "Anyone can view questions"
ON public.questions
FOR SELECT
USING (true);

-- 2. Only admins can insert questions
CREATE POLICY "Admins can insert questions"
ON public.questions
FOR INSERT
TO authenticated
WITH CHECK (
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- 3. Only admins can update questions
CREATE POLICY "Admins can update questions"
ON public.questions
FOR UPDATE
USING (
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- 4. Only admins can delete questions
CREATE POLICY "Admins can delete questions"
ON public.questions
FOR DELETE
USING (
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- Create a function to automatically update the updated_at timestamp
CREATE OR REPLACE FUNCTION update_questions_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger to call the function when a record is updated
CREATE TRIGGER update_questions_timestamp
BEFORE UPDATE ON public.questions
FOR EACH ROW
EXECUTE FUNCTION update_questions_updated_at();

-- Create a storage bucket for question images
INSERT INTO storage.buckets (id, name, public)
VALUES ('question-images', 'question-images', true)
ON CONFLICT (id) DO NOTHING;

-- Create RLS policies for the storage bucket
-- 1. Public access to question images
CREATE POLICY "Question images are publicly accessible"
ON storage.objects
FOR SELECT
USING (bucket_id = 'question-images');

-- 2. Admins can upload question images
CREATE POLICY "Admins can upload question images"
ON storage.objects
FOR INSERT
TO authenticated
WITH CHECK (
    bucket_id = 'question-images' AND
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- 3. Admins can update question images
CREATE POLICY "Admins can update question images"
ON storage.objects
FOR UPDATE
USING (
    bucket_id = 'question-images' AND
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- 4. Admins can delete question images
CREATE POLICY "Admins can delete question images"
ON storage.objects
FOR DELETE
USING (
    bucket_id = 'question-images' AND
    auth.uid() IN (
        SELECT user_id FROM public.profiles WHERE is_admin = true
    )
);

-- Add is_admin column to profiles table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'profiles' 
        AND column_name = 'is_admin'
    ) THEN
        ALTER TABLE public.profiles ADD COLUMN is_admin BOOLEAN DEFAULT false;
    END IF;
END
$$; 