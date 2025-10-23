-- Function to select 3 random questions and insert them into quick_battle_questions
CREATE OR REPLACE FUNCTION create_quick_battle_questions(
    p_battle_id UUID,
    p_difficulty VARCHAR DEFAULT 'medium'
)
RETURNS BOOLEAN AS $$
DECLARE
    v_question1_id INTEGER;
    v_question2_id INTEGER;
    v_question3_id INTEGER;
    v_questions_cursor CURSOR FOR
        SELECT 
            id
        FROM 
            public.questions
        WHERE 
            (p_difficulty = 'any' OR difficulty = p_difficulty)
            AND image_url IS NOT NULL
        ORDER BY 
            RANDOM()
        LIMIT 3;
    v_question_count INTEGER;
BEGIN
    -- Check if battle record exists
    IF NOT EXISTS (
        SELECT 1 FROM public.battle_records 
        WHERE battle_id = p_battle_id
    ) THEN
        RAISE EXCEPTION 'Battle with ID % does not exist', p_battle_id;
    END IF;

    -- Check if we already have questions for this battle
    IF EXISTS (
        SELECT 1 FROM public.quick_battle_questions
        WHERE battle_id = p_battle_id
    ) THEN
        RAISE NOTICE 'Questions already exist for battle %', p_battle_id;
        RETURN FALSE;
    END IF;

    -- Count available questions
    SELECT COUNT(*) INTO v_question_count
    FROM public.questions
    WHERE (p_difficulty = 'any' OR difficulty = p_difficulty)
          AND image_url IS NOT NULL;

    -- Check if we have enough questions
    IF v_question_count < 3 THEN
        RAISE EXCEPTION 'Not enough questions available with difficulty %', p_difficulty;
    END IF;

    -- Select 3 random questions
    OPEN v_questions_cursor;
    
    FETCH v_questions_cursor INTO v_question1_id;
    FETCH v_questions_cursor INTO v_question2_id;
    FETCH v_questions_cursor INTO v_question3_id;
    
    CLOSE v_questions_cursor;

    -- Insert the questions into the quick_battle_questions table
    INSERT INTO public.quick_battle_questions (
        battle_id, 
        question1_id, 
        question2_id, 
        question3_id
    ) VALUES (
        p_battle_id,
        v_question1_id,
        v_question2_id,
        v_question3_id
    );

    RAISE NOTICE 'Successfully created questions for quick battle %: questions %, %, %', 
        p_battle_id, v_question1_id, v_question2_id, v_question3_id;
    
    RETURN TRUE;
    
EXCEPTION
    WHEN OTHERS THEN
        RAISE NOTICE 'Error creating questions for quick battle %: %', p_battle_id, SQLERRM;
        RETURN FALSE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Example usage:
-- SELECT create_quick_battle_questions('12345678-1234-1234-1234-123456789012', 'medium');

-- Function to automatically create quick battle questions when a battle record with battle_mode='quick' is inserted
CREATE OR REPLACE FUNCTION auto_create_quick_battle_questions()
RETURNS TRIGGER AS $$
BEGIN
    -- Check if this is a quick battle
    IF NEW.battle_mode = 'quick' THEN
        -- Call the function to create questions
        PERFORM create_quick_battle_questions(
            NEW.battle_id,
            NEW.difficulty  -- Use the difficulty from the battle record if available
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create a trigger to automatically create questions when a new quick battle is created
CREATE TRIGGER quick_battle_questions_trigger
AFTER INSERT ON public.battle_records
FOR EACH ROW
EXECUTE FUNCTION auto_create_quick_battle_questions();

-- Grant appropriate permissions
GRANT EXECUTE ON FUNCTION create_quick_battle_questions(UUID, VARCHAR) TO authenticated;
GRANT EXECUTE ON FUNCTION auto_create_quick_battle_questions() TO authenticated; 