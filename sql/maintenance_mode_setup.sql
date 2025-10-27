-- Maintenance Mode Setup SQL
-- This script creates the maintenance_mode table and initial settings

-- Create maintenance_mode table if it doesn't exist
CREATE TABLE IF NOT EXISTS public.maintenance_mode (
  id integer NOT NULL DEFAULT nextval('maintenance_mode_id_seq'::regclass),
  is_active boolean NOT NULL DEFAULT false,
  start_time timestamp with time zone NOT NULL DEFAULT now(),
  duration_minutes integer NOT NULL DEFAULT 60,
  started_by uuid,
  reason text NOT NULL DEFAULT 'Scheduled maintenance',
  user_message text NOT NULL DEFAULT 'We are currently performing scheduled maintenance. Please check back later.',
  expected_resolution text NOT NULL DEFAULT 'Soon',
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  updated_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT maintenance_mode_pkey PRIMARY KEY (id),
  CONSTRAINT maintenance_mode_started_by_fkey FOREIGN KEY (started_by) REFERENCES auth.users(id)
);

-- Create sequence if it doesn't exist
CREATE SEQUENCE IF NOT EXISTS maintenance_mode_id_seq;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_maintenance_mode_active ON public.maintenance_mode(is_active);
CREATE INDEX IF NOT EXISTS idx_maintenance_mode_start_time ON public.maintenance_mode(start_time);

-- Insert default maintenance mode settings into settings table
INSERT INTO public.settings (key, value, description) VALUES 
('maintenance_mode_enabled', 'false', 'Global maintenance mode toggle'),
('maintenance_mode_message', 'We are currently performing scheduled maintenance. Please check back later.', 'Default maintenance message'),
('maintenance_mode_duration', '60', 'Default maintenance duration in minutes'),
('maintenance_mode_reason', 'Scheduled maintenance', 'Default maintenance reason')
ON CONFLICT (key) DO NOTHING;

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON public.maintenance_mode TO authenticated;
-- GRANT USAGE ON SEQUENCE maintenance_mode_id_seq TO authenticated;
