-- Function to fetch question details for a quick battle
CREATE OR REPLACE FUNCTION fetch_quick_battle_questions(
    p_battle_id UUID
)
RETURNS TABLE (
    question_number INTEGER,
    question_id INTEGER,
    image_url TEXT,
    correct_answer CHAR(1),
    subject VARCHAR(50),
    difficulty VARCHAR(20)
) AS $$
BEGIN
    -- Check if battle exists and has questions
    IF NOT EXISTS (
        SELECT 1 
        FROM public.quick_battle_questions 
        WHERE battle_id = p_battle_id
    ) THEN
        RAISE EXCEPTION 'No questions found for battle with ID %', p_battle_id;
    END IF;
    
    -- Return question details in the correct order
    RETURN QUERY
    SELECT 
        1 AS question_number,
        q1.id AS question_id,
        q1.image_url,
        q1.correct_answer,
        q1.subject,
        q1.difficulty
    FROM 
        public.quick_battle_questions qbq
    JOIN 
        public.questions q1 ON qbq.question1_id = q1.id
    WHERE 
        qbq.battle_id = p_battle_id
        
    UNION ALL
    
    SELECT 
        2 AS question_number,
        q2.id AS question_id,
        q2.image_url,
        q2.correct_answer,
        q2.subject,
        q2.difficulty
    FROM 
        public.quick_battle_questions qbq
    JOIN 
        public.questions q2 ON qbq.question2_id = q2.id
    WHERE 
        qbq.battle_id = p_battle_id
        
    UNION ALL
    
    SELECT 
        3 AS question_number,
        q3.id AS question_id,
        q3.image_url,
        q3.correct_answer,
        q3.subject,
        q3.difficulty
    FROM 
        public.quick_battle_questions qbq
    JOIN 
        public.questions q3 ON qbq.question3_id = q3.id
    WHERE 
        qbq.battle_id = p_battle_id
    
    ORDER BY question_number;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Create a trigger function that runs after questions are inserted
CREATE OR REPLACE FUNCTION after_questions_inserted()
RETURNS TRIGGER AS $$
BEGIN
    -- Log that questions have been inserted and are ready to be fetched
    RAISE NOTICE 'Questions inserted for battle %. Ready to fetch questions.', NEW.battle_id;
    
    -- The actual fetching and display will be handled by the client-side code
    -- This trigger mainly serves as a notification mechanism
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create a trigger that fires after a new row is inserted into quick_battle_questions
CREATE TRIGGER after_quick_battle_questions_trigger
AFTER INSERT ON public.quick_battle_questions
FOR EACH ROW
EXECUTE FUNCTION after_questions_inserted();

-- Grant permissions
GRANT EXECUTE ON FUNCTION fetch_quick_battle_questions(UUID) TO authenticated; 