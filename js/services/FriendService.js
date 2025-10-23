import { supabase } from '../config/supabase.js';

/**
 * Service class for handling friend-related operations
 */
export class FriendService {
    /**
     * Get a list of current user's friends with their online status
     * @returns {Promise<Array>} Array of friend objects
     */
    static async getFriends() {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) return [];
            
            // Get accepted friendships where user is either user_id or friend_id
            const { data: friendships, error } = await supabase
                .from('friend_relationships')
                .select('*, profiles!friend_relationships_friend_id_fkey(*)')
                .eq('status', 'accepted')
                .or(`user_id.eq.${user.id},friend_id.eq.${user.id}`);
            
            if (error) {
                console.error('Error fetching friends:', error);
                throw error;
            }
            
            // Transform data to match expected format
            const friends = friendships.map(friendship => {
                const isFriend = friendship.friend_id === user.id;
                const friendProfile = friendship.profiles;
                const friendId = isFriend ? friendship.user_id : friendship.friend_id;
                
                return {
                    friend_id: friendId,
                    username: friendProfile?.username || 'Unknown',
                    full_name: friendProfile?.full_name || '',
                    avatar_url: friendProfile?.avatar_url || '',
                    field: friendProfile?.field || '',
                    status: 'offline' // Default status
                };
            });
            
            return friends || [];
        } catch (error) {
            console.error('Error in getFriends:', error);
            throw error;
        }
    }
    
    /**
     * Get pending friend requests for the current user
     * @returns {Promise<Array>} Array of friend request objects
     */
    static async getPendingRequests() {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) return [];
            
            // Get pending requests where current user is the friend_id (receiver)
            const { data: requests, error } = await supabase
                .from('friend_relationships')
                .select('*, profiles!friend_relationships_user_id_fkey(*)')
                .eq('friend_id', user.id)
                .eq('status', 'pending');
            
            if (error) {
                console.error('Error fetching pending requests:', error);
                throw error;
            }
            
            // Transform data to match expected format
            const pendingRequests = requests.map(request => {
                return {
                    request_id: request.id,
                    sender_id: request.user_id,
                    username: request.profiles?.username || 'Unknown',
                    full_name: request.profiles?.full_name || '',
                    avatar_url: request.profiles?.avatar_url || '',
                    field: request.profiles?.field || '',
                    created_at: request.created_at
                };
            });
            
            return pendingRequests || [];
        } catch (error) {
            console.error('Error in getPendingRequests:', error);
            throw error;
        }
    }
    
    /**
     * Get friend requests sent by the current user
     * @returns {Promise<Array>} Array of sent friend request objects
     */
    static async getSentRequests() {
        try {
            const { data, error } = await supabase.rpc('get_sent_friend_requests');
            
            if (error) {
                console.error('Error fetching sent requests:', error);
                throw error;
            }
            
            return data || [];
        } catch (error) {
            console.error('Error in getSentRequests:', error);
            throw error;
        }
    }
    
    /**
     * Send a friend request to another user
     * @param {string} receiverId - The ID of the user to send the request to
     * @returns {Promise<boolean>} - True if the request was sent successfully
     */
    static async sendFriendRequest(receiverId) {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('User not authenticated');
            
            // Check if user is trying to add themselves
            if (user.id === receiverId) {
                throw new Error('Cannot send friend request to yourself');
            }
            
            // Check if friendship or request already exists
            const { data: existingRelationships, error: checkError } = await supabase
                .from('friend_relationships')
                .select('*')
                .or(`and(user_id.eq.${user.id},friend_id.eq.${receiverId}),and(user_id.eq.${receiverId},friend_id.eq.${user.id})`);
            
            if (checkError) {
                console.error('Error checking existing relationships:', checkError);
                throw checkError;
            }
            
            if (existingRelationships && existingRelationships.length > 0) {
                const relationship = existingRelationships[0];
                if (relationship.status === 'accepted') {
                    throw new Error('You are already friends with this user');
                } else {
                    throw new Error('A friend request already exists between you and this user');
                }
            }
            
            // Insert the new friend request
            const { data, error } = await supabase
                .from('friend_relationships')
                .insert([{
                    user_id: user.id,
                    friend_id: receiverId,
                    status: 'pending'
                }])
                .select();
            
            if (error) {
                console.error('Error sending friend request:', error);
                throw error;
            }
            
            return true;
        } catch (error) {
            console.error('Error in sendFriendRequest:', error);
            throw error;
        }
    }
    
    /**
     * Accept a friend request
     * @param {string} requestId - The ID of the friend request to accept
     * @returns {Promise<boolean>} - True if the request was accepted successfully
     */
    static async acceptFriendRequest(requestId) {
        try {
            const { data, error } = await supabase.rpc('accept_friend_request', {
                request_id: requestId
            });
            
            if (error) {
                console.error('Error accepting friend request:', error);
                throw error;
            }
            
            return data;
        } catch (error) {
            console.error('Error in acceptFriendRequest:', error);
            throw error;
        }
    }
    
    /**
     * Reject a friend request
     * @param {string} requestId - The ID of the friend request to reject
     * @returns {Promise<boolean>} - True if the request was rejected successfully
     */
    static async rejectFriendRequest(requestId) {
        try {
            const { data, error } = await supabase.rpc('reject_friend_request', {
                request_id: requestId
            });
            
            if (error) {
                console.error('Error rejecting friend request:', error);
                throw error;
            }
            
            return data;
        } catch (error) {
            console.error('Error in rejectFriendRequest:', error);
            throw error;
        }
    }
    
    /**
     * Remove a friend
     * @param {string} friendId - The ID of the friend to remove
     * @returns {Promise<boolean>} - True if the friend was removed successfully
     */
    static async removeFriend(friendId) {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) throw new Error('User not authenticated');
            
            // Delete the friendship relationship (in both directions)
            const { error } = await supabase
                .from('friend_relationships')
                .delete()
                .or(`and(user_id.eq.${user.id},friend_id.eq.${friendId}),and(user_id.eq.${friendId},friend_id.eq.${user.id})`)
                .eq('status', 'accepted');
            
            if (error) {
                console.error('Error removing friend:', error);
                throw error;
            }
            
            return true;
        } catch (error) {
            console.error('Error in removeFriend:', error);
            throw error;
        }
    }
    
    /**
     * Search for users by username or full name
     * @param {string} searchTerm - The search term
     * @returns {Promise<Array>} - Array of user objects matching the search term
     */
    static async searchUsers(searchTerm) {
        try {
            const { data: { user } } = await supabase.auth.getUser();
            if (!user) return [];
            
            // Search for profiles that match the search term
            const { data: profiles, error } = await supabase
                .from('profiles')
                .select('user_id, username, full_name, field, avatar_url')
                .neq('user_id', user.id)
                .or(`username.ilike.%${searchTerm}%,full_name.ilike.%${searchTerm}%`)
                .limit(10);
            
            if (error) {
                console.error('Error searching users:', error);
                throw error;
            }
            
            // Check friendship status for each user
            const { data: friendships, error: friendshipError } = await supabase
                .from('friend_relationships')
                .select('*')
                .or(`user_id.eq.${user.id},friend_id.eq.${user.id}`);
                
            if (friendshipError) {
                console.error('Error fetching friendships:', friendshipError);
                throw friendshipError;
            }
            
            // Map profiles to search results with friendship status
            const results = profiles.map(profile => {
                const friendship = friendships?.find(f => 
                    (f.user_id === user.id && f.friend_id === profile.user_id) || 
                    (f.user_id === profile.user_id && f.friend_id === user.id)
                );
                
                const isFriend = friendship?.status === 'accepted';
                const requestPending = friendship?.status === 'pending';
                
                return {
                    user_id: profile.user_id,
                    username: profile.username || 'Unknown',
                    full_name: profile.full_name || '',
                    avatar_url: profile.avatar_url || '',
                    field: profile.field || '',
                    is_friend: isFriend,
                    request_status: requestPending ? 'pending' : null
                };
            });
            
            return results || [];
        } catch (error) {
            console.error('Error in searchUsers:', error);
            throw error;
        }
    }
    
    /**
     * Update the current user's online status
     * @param {string} status - The status to set ('online', 'offline', 'away')
     * @returns {Promise<boolean>} - True if the status was updated successfully
     */
    static async updateUserStatus(status) {
        try {
            const { data, error } = await supabase.rpc('update_user_status', {
                status_value: status
            });
            
            if (error) {
                console.error('Error updating user status:', error);
                throw error;
            }
            
            return data;
        } catch (error) {
            console.error('Error in updateUserStatus:', error);
            throw error;
        }
    }
    
    /**
     * Get the count of online users
     * @returns {Promise<number>} - The number of online users
     */
    static async getOnlineUserCount() {
        try {
            const { data, error } = await supabase.rpc('count_online_users');
            
            if (error) {
                console.error('Error getting online user count:', error);
                throw error;
            }
            
            return data || 0;
        } catch (error) {
            console.error('Error in getOnlineUserCount:', error);
            throw error;
        }
    }
    
    /**
     * Get friendship notifications for the current user
     * @param {number} limit - Maximum number of notifications to return
     * @returns {Promise<Array>} - Array of notification objects
     */
    static async getFriendshipNotifications(limit = 10) {
        try {
            const { data, error } = await supabase.rpc('get_friendship_notifications', {
                limit_val: limit
            });
            
            if (error) {
                console.error('Error fetching friendship notifications:', error);
                throw error;
            }
            
            return data || [];
        } catch (error) {
            console.error('Error in getFriendshipNotifications:', error);
            throw error;
        }
    }
    
    /**
     * Mark notifications as read
     * @param {Array<string>} notificationIds - Array of notification IDs to mark as read
     * @returns {Promise<boolean>} - True if the notifications were marked as read successfully
     */
    static async markNotificationsAsRead(notificationIds) {
        try {
            const { data, error } = await supabase.rpc('mark_notifications_read', {
                notification_ids: notificationIds
            });
            
            if (error) {
                console.error('Error marking notifications as read:', error);
                throw error;
            }
            
            return data;
        } catch (error) {
            console.error('Error in markNotificationsAsRead:', error);
            throw error;
        }
    }
} 