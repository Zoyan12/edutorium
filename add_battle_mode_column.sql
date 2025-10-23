-- Add battle_mode column to battle_records table
ALTER TABLE public.battle_records ADD COLUMN battle_mode character varying(20) NOT NULL DEFAULT 'arena'::character varying;

-- Add a check constraint to ensure only valid battle_mode values
ALTER TABLE public.battle_records ADD CONSTRAINT battle_records_battle_mode_check 
  CHECK (battle_mode::text = ANY (ARRAY['arena'::character varying, 'quick'::character varying]::text[]));

-- Create an index for improved query performance
CREATE INDEX IF NOT EXISTS idx_battle_records_battle_mode ON public.battle_records USING btree (battle_mode) TABLESPACE pg_default; 