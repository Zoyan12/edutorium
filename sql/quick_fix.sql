-- Quick fix for the timestamp type mismatch in the update_user_status function

-- Drop the existing function if it exists
DROP FUNCTION IF EXISTS update_user_status(text);

-- Recreate the function with the correct timestamp handling
CREATE OR REPLACE FUNCTION update_user_status(status_value TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    -- Use CURRENT_TIMESTAMP which is explicitly a timestamp with time zone
    INSERT INTO user_status (user_id, status, last_active, updated_at)
    VALUES (auth.uid(), $1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ON CONFLICT (user_id) 
    DO UPDATE SET 
        status = $1,
        last_active = CURRENT_TIMESTAMP,
        updated_at = CURRENT_TIMESTAMP;
    
    RETURN TRUE;
EXCEPTION
    WHEN OTHERS THEN
        RAISE;
        RETURN FALSE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Verify the function signature to ensure it's created correctly
SELECT pg_get_functiondef(oid) 
FROM pg_proc 
WHERE proname = 'update_user_status'; 