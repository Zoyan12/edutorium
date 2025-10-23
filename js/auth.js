/**
 * Authentication Manager for EduBattle
 * Manages user authentication state and interactions with Supabase
 */
class AuthManager {
    constructor() {
        // Initialize Supabase client
        this.supabase = supabase.createClient(
            'https://your-supabase-url.supabase.co',  // Replace with your actual Supabase URL
            'your-supabase-anon-key'                  // Replace with your actual Supabase anon key
        );
        
        this.currentUser = null;
        this.authChangeCallbacks = [];
        
        // Set up auth state change listener
        this.supabase.auth.onAuthStateChange((event, session) => {
            if (event === 'SIGNED_IN') {
                this.currentUser = session.user;
                this._notifyAuthChange(true, this.currentUser);
            } else if (event === 'SIGNED_OUT') {
                this.currentUser = null;
                this._notifyAuthChange(false, null);
            }
        });
    }
    
    /**
     * Get the current user if logged in
     */
    async getCurrentUser() {
        try {
            // Check if we already have the current user
            if (this.currentUser) {
                return this.currentUser;
            }
            
            // Get user from session
            const { data, error } = await this.supabase.auth.getUser();
            
            if (error) {
                console.error('Error getting user:', error.message);
                return null;
            }
            
            if (data && data.user) {
                this.currentUser = data.user;
                return this.currentUser;
            }
            
            return null;
        } catch (err) {
            console.error('Error in getCurrentUser:', err);
            return null;
        }
    }
    
    /**
     * Sign in with email and password
     */
    async signIn(email, password) {
        try {
            const { data, error } = await this.supabase.auth.signInWithPassword({
                email,
                password
            });
            
            if (error) {
                throw error;
            }
            
            this.currentUser = data.user;
            return data.user;
        } catch (err) {
            console.error('Error signing in:', err);
            throw err;
        }
    }
    
    /**
     * Sign up with email and password
     */
    async signUp(email, password, metadata = {}) {
        try {
            const { data, error } = await this.supabase.auth.signUp({
                email,
                password,
                options: {
                    data: metadata
                }
            });
            
            if (error) {
                throw error;
            }
            
            return data;
        } catch (err) {
            console.error('Error signing up:', err);
            throw err;
        }
    }
    
    /**
     * Sign out the current user
     */
    async signOut() {
        try {
            const { error } = await this.supabase.auth.signOut();
            
            if (error) {
                throw error;
            }
            
            this.currentUser = null;
            return true;
        } catch (err) {
            console.error('Error signing out:', err);
            throw err;
        }
    }
    
    /**
     * Update user profile data
     */
    async updateProfile(profileData) {
        try {
            const { data, error } = await this.supabase
                .from('profiles')
                .upsert({
                    user_id: this.currentUser.id,
                    updated_at: new Date(),
                    ...profileData
                });
            
            if (error) {
                throw error;
            }
            
            return data;
        } catch (err) {
            console.error('Error updating profile:', err);
            throw err;
        }
    }
    
    /**
     * Get user profile data
     */
    async getProfile(userId) {
        try {
            const id = userId || (this.currentUser ? this.currentUser.id : null);
            
            if (!id) {
                throw new Error('No user ID provided');
            }
            
            const { data, error } = await this.supabase
                .from('profiles')
                .select('*')
                .eq('user_id', id)
                .single();
            
            if (error) {
                throw error;
            }
            
            return data;
        } catch (err) {
            console.error('Error getting profile:', err);
            throw err;
        }
    }
    
    /**
     * Add callback for auth state changes
     */
    onAuthChange(callback) {
        this.authChangeCallbacks.push(callback);
        return () => {
            this.authChangeCallbacks = this.authChangeCallbacks.filter(cb => cb !== callback);
        };
    }
    
    /**
     * Notify all registered callbacks about auth changes
     */
    _notifyAuthChange(isAuthenticated, user) {
        this.authChangeCallbacks.forEach(callback => {
            try {
                callback(isAuthenticated, user);
            } catch (err) {
                console.error('Error in auth change callback:', err);
            }
        });
    }
}

// Mock implementation for testing if Supabase is not available
if (typeof supabase === 'undefined') {
    console.warn('Supabase not found, using mock implementation');
    
    // Create a global mock
    window.supabase = {
        createClient: () => ({
            auth: {
                onAuthStateChange: (callback) => {},
                getUser: async () => ({ 
                    data: { 
                        user: { 
                            id: 'test-user-123',
                            email: 'test@example.com',
                            user_metadata: {
                                username: 'Test Player'
                            }
                        } 
                    }, 
                    error: null 
                }),
                signInWithPassword: async () => ({ data: { user: {} }, error: null }),
                signUp: async () => ({ data: {}, error: null }),
                signOut: async () => ({ error: null })
            },
            from: () => ({
                select: () => ({
                    eq: () => ({
                        single: async () => ({ 
                            data: { 
                                username: 'Test Player',
                                avatar_url: null
                            }, 
                            error: null 
                        })
                    })
                }),
                upsert: async () => ({ data: {}, error: null })
            })
        })
    };
} 