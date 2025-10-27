-- Maintenance Logs Table Setup
-- This script creates the maintenance_logs table for tracking maintenance events

-- Create maintenance_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS public.maintenance_logs (
  id integer NOT NULL DEFAULT nextval('maintenance_logs_id_seq'::regclass),
  maintenance_id integer NOT NULL,
  event_type text NOT NULL,
  description text,
  created_at timestamp with time zone NOT NULL DEFAULT now(),
  CONSTRAINT maintenance_logs_pkey PRIMARY KEY (id),
  CONSTRAINT maintenance_logs_maintenance_id_fkey FOREIGN KEY (maintenance_id) REFERENCES public.maintenance_mode(id)
);

-- Create sequence if it doesn't exist
CREATE SEQUENCE IF NOT EXISTS maintenance_logs_id_seq;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_maintenance_logs_maintenance_id ON public.maintenance_logs(maintenance_id);
CREATE INDEX IF NOT EXISTS idx_maintenance_logs_event_type ON public.maintenance_logs(event_type);
CREATE INDEX IF NOT EXISTS idx_maintenance_logs_created_at ON public.maintenance_logs(created_at);

-- Grant necessary permissions (adjust as needed for your setup)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON public.maintenance_logs TO authenticated;
-- GRANT USAGE ON SEQUENCE maintenance_logs_id_seq TO authenticated;
