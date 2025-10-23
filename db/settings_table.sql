-- Create the settings table for dynamic application configuration
CREATE TABLE IF NOT EXISTS settings (
  id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
  key TEXT NOT NULL UNIQUE,
  value TEXT NOT NULL,
  description TEXT,
  updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert default setting for WebSocket URL
INSERT INTO settings (key, value, description)
VALUES ('websocket_url', 'wss://your-supabase-project-id.supabase.co/realtime/v1', 'WebSocket URL for real-time features')
ON CONFLICT (key) 
DO UPDATE SET 
  value = EXCLUDED.value,
  description = EXCLUDED.description,
  updated_at = CURRENT_TIMESTAMP;

-- Add Row Level Security policies
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;

-- Create a policy that allows admins to update settings
CREATE POLICY "Allow admins to manage settings" 
ON settings 
FOR ALL
USING (auth.role() = 'authenticated' AND auth.jwt() ->> 'is_admin' = 'true')
WITH CHECK (auth.role() = 'authenticated' AND auth.jwt() ->> 'is_admin' = 'true');

-- Create a policy that allows all authenticated users to read settings
CREATE POLICY "Allow authenticated users to read settings" 
ON settings 
FOR SELECT
USING (auth.role() = 'authenticated');

-- Create a policy that allows anonymous access to read settings
CREATE POLICY "Allow anonymous to read settings" 
ON settings 
FOR SELECT
USING (true); 