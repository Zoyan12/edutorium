-- From add_battle_mode_column.sql
-- Add battle_mode column to battle_records table
ALTER TABLE public.battle_records ADD COLUMN battle_mode character varying(20) NOT NULL DEFAULT 'arena'::character varying;

-- Add a check constraint to ensure only valid battle_mode values
ALTER TABLE public.battle_records ADD CONSTRAINT battle_records_battle_mode_check 
  CHECK (battle_mode::text = ANY (ARRAY[
    'arena'::character varying, 
    'quick'::character varying,
    'Success'::character varying,
    'Cancelled'::character varying,
    'Player1Left'::character varying,
    'Player2Left'::character varying,
    'Timeout'::character varying
  ]::text[]));

-- Create an index for improved query performance
CREATE INDEX IF NOT EXISTS idx_battle_records_battle_mode ON public.battle_records USING btree (battle_mode) TABLESPACE pg_default; 

-- From sql/update_battle_records_for_quick_battles.sql
-- Migration to ensure battle_mode is set correctly for quick battles

-- Update any existing quick battles to have the correct battle_mode
UPDATE public.battle_records
SET battle_mode = 'quick'
WHERE battle_type = 'quick' AND battle_mode != 'quick';

-- Create or replace function to ensure battle_mode is set correctly based on battle_type
CREATE OR REPLACE FUNCTION set_battle_mode_from_type()
RETURNS TRIGGER AS $$
BEGIN
    -- If battle_type is 'quick', set battle_mode to 'quick'
    IF NEW.battle_type = 'quick' THEN
        NEW.battle_mode = 'quick';
    -- If battle_type is 'arena', set battle_mode to 'arena'
    ELSIF NEW.battle_type = 'arena' THEN
        NEW.battle_mode = 'arena';
    -- For completion statuses, keep battle_mode as is or default to 'arena'
    ELSIF NEW.battle_type IN ('Success', 'Cancelled', 'Player1Left', 'Player2Left', 'Timeout') THEN
        NEW.battle_mode = COALESCE(NEW.battle_mode, 'arena');
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger to call the function when a record is inserted or updated
CREATE TRIGGER set_battle_mode_trigger
BEFORE INSERT OR UPDATE ON public.battle_records
FOR EACH ROW
EXECUTE FUNCTION set_battle_mode_from_type(); 

-- From update_battle_server.sql
-- Create a logging function for battle server operations
CREATE OR REPLACE FUNCTION log_battle_server_operation(
    p_operation VARCHAR,
    p_battle_id UUID DEFAULT NULL,
    p_details JSONB DEFAULT NULL
)
RETURNS VOID AS $$
BEGIN
    INSERT INTO public.server_logs (
        operation,
        battle_id,
        details,
        created_at
    ) VALUES (
        p_operation,
        p_battle_id,
        p_details,
        now()
    );
    
    -- No return value needed
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create server_logs table if it doesn't exist
CREATE TABLE IF NOT EXISTS public.server_logs (
    id SERIAL PRIMARY KEY,
    operation VARCHAR(100) NOT NULL,
    battle_id UUID,
    details JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- Add index for better query performance on logs
CREATE INDEX IF NOT EXISTS idx_server_logs_battle_id ON public.server_logs(battle_id);
CREATE INDEX IF NOT EXISTS idx_server_logs_operation ON public.server_logs(operation);
CREATE INDEX IF NOT EXISTS idx_server_logs_created_at ON public.server_logs(created_at);

-- Add documentation comments to the existing functions in quick_battle_feature.sql
COMMENT ON FUNCTION get_random_quick_battle_questions IS 
'Selects 3 random questions with images for quick battles.
Parameters:
- p_subject: The subject to filter by (default "general")
- p_difficulty: The difficulty level to filter by (default "medium")
Returns: Table with question details (id, image_url, correct_answer, subject, difficulty)';

COMMENT ON FUNCTION record_quick_battle_response IS
'Records a player''s response to a question in a quick battle and calculates points.
Points awarded: +4 for correct, -1 for incorrect, 0 for skipped or timeout.
Parameters:
- p_battle_id: UUID of the battle
- p_player_id: UUID of the player
- p_question_id: ID of the question being answered
- p_answer_given: The letter answer given (A, B, C, D) or NULL
- p_is_skipped: Whether the question was skipped
- p_time_taken_ms: Time taken to answer in milliseconds
Returns: Integer representing points awarded';

COMMENT ON FUNCTION both_players_answered IS
'Checks if both players have answered a specific question in a quick battle.
Parameters:
- p_battle_id: UUID of the battle
- p_question_id: ID of the question
Returns: Boolean indicating if both players have answered';

COMMENT ON FUNCTION get_quick_battle_scores IS
'Calculates current scores for both players in a quick battle.
Parameters:
- p_battle_id: UUID of the battle
Returns: Table with player details (player_id, total_score, correct_answers, avg_response_time_ms)';

COMMENT ON FUNCTION complete_quick_battle IS
'Handles the completion of a quick battle, determining the winner and updating the battle record.
In case of tied scores, the player with faster average response time wins.
Parameters:
- p_battle_id: UUID of the battle
- p_forced_end: Whether the battle was ended forcefully
- p_quit_by: Player UUID who quit (if applicable)
Returns: Boolean indicating success';

-- Create a materialized view for quick battle statistics 
-- to improve dashboard performance
CREATE MATERIALIZED VIEW IF NOT EXISTS quick_battle_stats AS
SELECT
    EXTRACT(DOW FROM br.created_at) AS day_of_week,
    COUNT(*) AS total_battles,
    COUNT(*) FILTER (WHERE br.battle_result = 'Draw') AS draws,
    AVG(br.duration_seconds) AS avg_duration,
    AVG(br.player1_correct_answers + br.player2_correct_answers) AS avg_correct_answers
FROM
    public.battle_records br
WHERE
    br.battle_mode = 'quick'
GROUP BY
    EXTRACT(DOW FROM br.created_at)
WITH DATA;

-- Create a function to refresh the materialized view
CREATE OR REPLACE FUNCTION refresh_quick_battle_stats()
RETURNS VOID AS $$
BEGIN
    REFRESH MATERIALIZED VIEW quick_battle_stats;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create an index on the materialized view for better performance
CREATE INDEX IF NOT EXISTS idx_quick_battle_stats_day ON quick_battle_stats(day_of_week); 

-- From sql/quick_battle_feature.sql
-- Create tables for the Quick Battle feature

-- Table to store the three randomly selected questions for each quick battle
CREATE TABLE IF NOT EXISTS public.quick_battle_questions (
    id SERIAL PRIMARY KEY,
    battle_id UUID NOT NULL REFERENCES public.battle_records(battle_id) ON DELETE CASCADE,
    question1_id INTEGER NOT NULL REFERENCES public.questions(id),
    question2_id INTEGER NOT NULL REFERENCES public.questions(id),
    question3_id INTEGER NOT NULL REFERENCES public.questions(id),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    CONSTRAINT unique_battle_questions UNIQUE (battle_id)
);

-- Table to store player responses in quick battles
CREATE TABLE IF NOT EXISTS public.quick_battle_responses (
    id SERIAL PRIMARY KEY,
    battle_id UUID NOT NULL REFERENCES public.battle_records(battle_id) ON DELETE CASCADE,
    player_id UUID NOT NULL REFERENCES auth.users(id),
    question_id INTEGER NOT NULL REFERENCES public.questions(id),
    answer_given CHAR(1) CHECK (answer_given IS NULL OR answer_given IN ('A', 'B', 'C', 'D')),
    is_correct BOOLEAN NOT NULL DEFAULT false,
    is_skipped BOOLEAN NOT NULL DEFAULT false,
    points_awarded INTEGER NOT NULL DEFAULT 0,
    time_taken_ms INTEGER DEFAULT NULL,
    answered_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    CONSTRAINT unique_player_question_response UNIQUE (battle_id, player_id, question_id)
);

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_quick_battle_questions_battle_id ON public.quick_battle_questions(battle_id);
CREATE INDEX IF NOT EXISTS idx_quick_battle_responses_battle_id ON public.quick_battle_responses(battle_id);
CREATE INDEX IF NOT EXISTS idx_quick_battle_responses_player_id ON public.quick_battle_responses(player_id);
CREATE INDEX IF NOT EXISTS idx_quick_battle_responses_question_id ON public.quick_battle_responses(question_id);
CREATE INDEX IF NOT EXISTS idx_quick_battle_responses_time_taken ON public.quick_battle_responses(time_taken_ms);

-- Enable Row Level Security
ALTER TABLE public.quick_battle_questions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.quick_battle_responses ENABLE ROW LEVEL SECURITY;

-- Create RLS policies for quick_battle_questions
CREATE POLICY "Anyone can view quick battle questions"
ON public.quick_battle_questions
FOR SELECT
USING (true);

CREATE POLICY "Battle participants can insert quick battle questions"
ON public.quick_battle_questions
FOR INSERT
TO authenticated
WITH CHECK (
    EXISTS (
        SELECT 1 FROM public.battle_records 
        WHERE battle_id = quick_battle_questions.battle_id
        AND (player1_id = auth.uid() OR player2_id = auth.uid())
    )
);

-- Create RLS policies for quick_battle_responses
CREATE POLICY "Anyone can view quick battle responses"
ON public.quick_battle_responses
FOR SELECT
USING (true);

CREATE POLICY "Players can insert their own responses"
ON public.quick_battle_responses
FOR INSERT
TO authenticated
WITH CHECK (player_id = auth.uid());

CREATE POLICY "Players can update their own responses"
ON public.quick_battle_responses
FOR UPDATE
USING (player_id = auth.uid());

-- Function to select 3 random questions with images for quick battles
CREATE OR REPLACE FUNCTION get_random_quick_battle_questions(
    p_subject VARCHAR DEFAULT 'general',
    p_difficulty VARCHAR DEFAULT 'medium'
)
RETURNS TABLE (
    question_id INTEGER,
    image_url TEXT,
    correct_answer CHAR(1),
    subject VARCHAR,
    difficulty VARCHAR
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        q.id AS question_id,
        q.image_url,
        q.correct_answer,
        q.subject,
        q.difficulty
    FROM public.questions q
    WHERE 
        (p_subject = 'general' OR q.subject = p_subject)
        AND (p_difficulty = 'any' OR q.difficulty = p_difficulty)
        AND q.image_url IS NOT NULL
    ORDER BY RANDOM()
    LIMIT 3;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to record a player's response and calculate points
CREATE OR REPLACE FUNCTION record_quick_battle_response(
    p_battle_id UUID,
    p_player_id UUID,
    p_question_id INTEGER,
    p_answer_given CHAR(1) DEFAULT NULL,
    p_is_skipped BOOLEAN DEFAULT false,
    p_time_taken_ms INTEGER DEFAULT NULL
)
RETURNS INTEGER AS $$
DECLARE
    v_correct_answer CHAR(1);
    v_is_correct BOOLEAN := false;
    v_points_awarded INTEGER := 0;
BEGIN
    -- Get the correct answer for this question
    SELECT correct_answer INTO v_correct_answer
    FROM public.questions
    WHERE id = p_question_id;
    
    -- Calculate if the answer is correct and the points awarded
    IF p_is_skipped THEN
        -- Skipped question, no points
        v_is_correct := false;
        v_points_awarded := 0;
    ELSIF p_answer_given IS NULL THEN
        -- Timeout, no points
        v_is_correct := false;
        v_points_awarded := 0;
    ELSIF p_answer_given = v_correct_answer THEN
        -- Correct answer, +4 points
        v_is_correct := true;
        v_points_awarded := 4;
    ELSE
        -- Wrong answer, -1 point
        v_is_correct := false;
        v_points_awarded := -1;
    END IF;
    
    -- Insert or update the response
    INSERT INTO public.quick_battle_responses (
        battle_id,
        player_id,
        question_id,
        answer_given,
        is_correct,
        is_skipped,
        points_awarded,
        time_taken_ms,
        answered_at
    ) VALUES (
        p_battle_id,
        p_player_id,
        p_question_id,
        p_answer_given,
        v_is_correct,
        p_is_skipped,
        v_points_awarded,
        p_time_taken_ms,
        now()
    )
    ON CONFLICT (battle_id, player_id, question_id) 
    DO UPDATE SET
        answer_given = EXCLUDED.answer_given,
        is_correct = EXCLUDED.is_correct,
        is_skipped = EXCLUDED.is_skipped,
        points_awarded = EXCLUDED.points_awarded,
        time_taken_ms = EXCLUDED.time_taken_ms,
        answered_at = EXCLUDED.answered_at;
    
    -- Return the points awarded
    RETURN v_points_awarded;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to check if both players have answered a question
CREATE OR REPLACE FUNCTION both_players_answered(
    p_battle_id UUID,
    p_question_id INTEGER
)
RETURNS BOOLEAN AS $$
DECLARE
    player1_id UUID;
    player2_id UUID;
    player1_answered BOOLEAN := false;
    player2_answered BOOLEAN := false;
BEGIN
    -- Get player IDs from battle record
    SELECT br.player1_id, br.player2_id 
    INTO player1_id, player2_id
    FROM public.battle_records br
    WHERE br.battle_id = p_battle_id;
    
    -- Check if player 1 answered
    SELECT EXISTS(
        SELECT 1 FROM public.quick_battle_responses
        WHERE battle_id = p_battle_id
        AND player_id = player1_id
        AND question_id = p_question_id
    ) INTO player1_answered;
    
    -- Check if player 2 answered
    SELECT EXISTS(
        SELECT 1 FROM public.quick_battle_responses
        WHERE battle_id = p_battle_id
        AND player_id = player2_id
        AND question_id = p_question_id
    ) INTO player2_answered;
    
    -- Return true if both players answered
    RETURN player1_answered AND player2_answered;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to calculate current scores for a battle
CREATE OR REPLACE FUNCTION get_quick_battle_scores(
    p_battle_id UUID
)
RETURNS TABLE (
    player_id UUID,
    total_score INTEGER,
    correct_answers INTEGER,
    avg_response_time_ms INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        qbr.player_id,
        COALESCE(SUM(qbr.points_awarded), 0)::INTEGER AS total_score,
        COUNT(qbr.id) FILTER (WHERE qbr.is_correct = true)::INTEGER AS correct_answers,
        COALESCE(AVG(qbr.time_taken_ms)::INTEGER, 0) AS avg_response_time_ms
    FROM 
        public.quick_battle_responses qbr
    WHERE 
        qbr.battle_id = p_battle_id
        AND qbr.time_taken_ms IS NOT NULL  -- Only include responses with valid time
    GROUP BY 
        qbr.player_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Function to handle quick battle completion
CREATE OR REPLACE FUNCTION complete_quick_battle(
    p_battle_id UUID,
    p_forced_end BOOLEAN DEFAULT false,
    p_quit_by UUID DEFAULT NULL
)
RETURNS BOOLEAN AS $$
DECLARE
    player1_id UUID;
    player2_id UUID;
    player1_score INTEGER := 0;
    player2_score INTEGER := 0;
    player1_correct INTEGER := 0;
    player2_correct INTEGER := 0;
    player1_avg_time INTEGER := 0;
    player2_avg_time INTEGER := 0;
    battle_result VARCHAR(20) := 'Incomplete';
    battle_type VARCHAR(20) := 'quick';
BEGIN
    -- Get player IDs from battle record
    SELECT br.player1_id, br.player2_id 
    INTO player1_id, player2_id
    FROM public.battle_records br
    WHERE br.battle_id = p_battle_id;
    
    -- Calculate scores
    SELECT 
        s.total_score, s.correct_answers, s.avg_response_time_ms
    INTO 
        player1_score, player1_correct, player1_avg_time
    FROM 
        get_quick_battle_scores(p_battle_id) s
    WHERE 
        s.player_id = player1_id;
    
    SELECT 
        s.total_score, s.correct_answers, s.avg_response_time_ms
    INTO 
        player2_score, player2_correct, player2_avg_time
    FROM 
        get_quick_battle_scores(p_battle_id) s
    WHERE 
        s.player_id = player2_id;
    
    -- Determine battle result
    IF p_forced_end AND p_quit_by IS NOT NULL THEN
        IF p_quit_by = player1_id THEN
            battle_result := 'Player2Wins';
            battle_type := 'Player1Left';
        ELSE
            battle_result := 'Player1Wins';
            battle_type := 'Player2Left';
        END IF;
    ELSIF player1_score > player2_score THEN
        battle_result := 'Player1Wins';
    ELSIF player2_score > player1_score THEN
        battle_result := 'Player2Wins';
    ELSE
        -- If scores are tied, the player with the faster average response time wins
        IF player1_avg_time > 0 AND player2_avg_time > 0 AND player1_avg_time != player2_avg_time THEN
            IF player1_avg_time < player2_avg_time THEN
                battle_result := 'Player1Wins';
            ELSE
                battle_result := 'Player2Wins';
            END IF;
        ELSE
            battle_result := 'Draw';
        END IF;
    END IF;
    
    -- Update battle record
    UPDATE public.battle_records
    SET 
        end_time = now(),
        battle_result = battle_result,
        battle_type = battle_type,
        player1_correct_answers = player1_correct,
        player2_correct_answers = player2_correct,
        duration_seconds = EXTRACT(EPOCH FROM (now() - start_time))::INTEGER
    WHERE 
        battle_id = p_battle_id;
    
    RETURN true;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER; 

