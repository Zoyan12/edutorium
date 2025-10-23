-- Fix for user status stack depth issue

-- First, disable the problematic trigger
DROP TRIGGER IF EXISTS update_inactive_users_trigger ON user_status;

-- Clear any potentially problematic records
TRUNCATE TABLE user_status;

-- Recreate the trigger with proper logic to prevent recursion
CREATE OR REPLACE FUNCTION update_inactive_users() RETURNS TRIGGER AS $$
BEGIN
    -- Skip processing if this is an update triggered by this function itself
    IF TG_OP = 'UPDATE' AND NEW.status = 'offline' THEN
        RETURN NULL;
    END IF;

    -- Only update other records, not the one that triggered this function
    UPDATE user_status
    SET status = 'offline'
    WHERE status = 'online' 
    AND last_active < NOW() - INTERVAL '15 minutes'
    AND user_id <> COALESCE(NEW.user_id, OLD.user_id);
    
    RETURN NULL;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Recreate the trigger with FOR EACH ROW
CREATE TRIGGER update_inactive_users_trigger
AFTER INSERT OR UPDATE ON user_status
FOR EACH ROW
EXECUTE FUNCTION update_inactive_users();

-- Create a simpler function for updating user status (without triggering recursion)
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