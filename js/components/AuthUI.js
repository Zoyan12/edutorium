import { supabase } from '../config/supabase.js';

export class AuthUI {
    constructor() {
        console.log('AuthUI initialized');
        this.loginForm = document.getElementById('loginForm');
        this.signupForm = document.getElementById('signupForm');
        this.errorContainer = document.getElementById('errorContainer');
        
        if (!this.loginForm && !this.signupForm) {
            console.error('No login or signup form found');
            return;
        }
        
        this.initializeEventListeners();
    }

    getBaseUrl() {
        const path = window.location.pathname;
        let basePath = path.split('/pages')[0];
        
        // Handle root domain case (path is just "/")
        if (basePath === '/') {
            // Check if we're in a subdirectory by looking at document.location.pathname
            const fullPath = document.location.pathname;
            if (fullPath.includes('/client/')) {
                basePath = '/client';
            } else {
                basePath = '';
            }
        }
        
        // Remove trailing slash to avoid double slashes
        return basePath.endsWith('/') ? basePath.slice(0, -1) : basePath;
    }

    initializeEventListeners() {
        if (this.loginForm) {
            console.log('Login form found, adding event listener');
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        if (this.signupForm) {
            console.log('Signup form found, adding event listener');
            this.signupForm.addEventListener('submit', (e) => this.handleSignup(e));
            
            // Clear error messages when user types
            const inputs = this.signupForm.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', () => {
                    if (this.errorContainer) {
                        this.errorContainer.innerHTML = '';
                    }
                });
            });
        }
    }

    showError(message, type = 'error') {
        console.log('Showing error:', message);
        if (this.errorContainer) {
            this.errorContainer.innerHTML = `
                <div class="message ${type}">
                    <p>${message}</p>
                </div>
            `;
        }
    }

    async validateUsername(username) {
        if (!username) return false;
        
        try {
            const { data, error } = await supabase
                .from('profiles')
                .select('username')
                .eq('username', username)
                .single();

            if (error && error.code !== 'PGRST116') {
                throw error;
            }

            if (data) {
                this.showError('Username is already taken');
                return false;
            }
            return true;
        } catch (error) {
            console.error('Username validation error:', error);
            this.showError('Error checking username availability');
            return false;
        }
    }

    async checkProfileCompletion(userId) {
        try {
            console.log('Checking profile completion for user:', userId);
            
            const { data: profile, error } = await supabase
                .from('profiles')
                .select('*')
                .eq('user_id', userId)
                .single();

            if (error) {
                console.error('Profile check error:', error);
                
                if (error.code === '42P01' || error.code === '42501') {
                    console.error('Database table or permission issue:', error.message);
                    this.showError('Database configuration error. Please contact support.');
                    return;
                }
                
                if (error.code === 'PGRST116') {
                    console.log('No profile found, redirecting to profile completion');
                    // Store the user ID for profile creation
                    localStorage.setItem('userId', userId);
                    localStorage.setItem('forceProfileCompletion', 'true');
                    window.location.href = `${this.getBaseUrl()}/pages/complete-profile.html`;
                    return;
                }
                
                throw error;
            }

            if (profile && profile.is_complete) {
                console.log('Profile complete, redirecting to dashboard');
                // Clear any potential localStorage flags
                localStorage.removeItem('forceProfileCompletion');
                localStorage.removeItem('userId');
                localStorage.removeItem('userUsername');
                localStorage.removeItem('userField');
                
                window.location.href = `${this.getBaseUrl()}/pages/dashboard.php`;
            } else {
                console.log('Profile incomplete, redirecting to profile completion');
                // Store the user ID for profile completion
                localStorage.setItem('userId', userId);
                localStorage.setItem('forceProfileCompletion', 'true');
                window.location.href = `${this.getBaseUrl()}/pages/complete-profile.html`;
            }
        } catch (error) {
            console.error('Profile completion check error:', error);
            this.showError('Unable to verify your profile status. Please try again or contact support if the issue persists.');
        }
    }

    async handleLogin(e) {
        e.preventDefault();
        console.log('Login attempt started');
        
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;

        try {
            console.log('Attempting to sign in with email:', email);
            const { data, error } = await supabase.auth.signInWithPassword({
                email,
                password
            });

            if (error) {
                console.error('Login error:', error);
                throw error;
            }

            console.log('Login successful, checking profile completion');
            await this.checkProfileCompletion(data.user.id);
        } catch (error) {
            console.error('Login error:', error);
            this.showError(error.message);
        }
    }

    async handleSignup(e) {
        e.preventDefault();
        console.log('Signup attempt started');
    
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const username = document.getElementById('username').value;
        const field = document.getElementById('field').value;
    
        try {
            this.showError('Checking username availability...', 'info');
            console.log('Checking username:', username);
            
            const isUsernameAvailable = await this.validateUsername(username);
            if (!isUsernameAvailable) {
                console.log('Username not available, stopping signup.');
                return; // Stop execution
            }
        } catch (error) {
            console.error('Username validation error:', error);
        }
    
        try {
            this.showError('Creating your account...', 'info');
            console.log('Attempting to sign up with email:', email);
            
            // Sign up the user
            const { data, error } = await supabase.auth.signUp({ email, password });
    
            if (error) {
                console.error('Signup error:', error);
                throw error;
            }
    
            if (!data.user) {
                throw new Error('Signup successful, but no user object returned. Check email verification settings.');
            }
    
            const userId = data.user.id;
    
            console.log('Signup successful. User ID:', userId);
    
            // Store necessary values before inserting the profile
            localStorage.setItem('userField', field);
            localStorage.setItem('userUsername', username);
            localStorage.setItem('userId', userId);
            localStorage.setItem('forceProfileCompletion', 'true');
    
            // Ensure profile is inserted before redirection
            console.log('Creating initial profile record...');
    
            const profileData = {
                user_id: userId,
                username,
                field,
                full_name: username,
                phone: '',
                location: '',
                education: '',
                board: '',
                is_complete: false,
                avatar_url: '',
                points: 0
            };
    
            // Insert profile into Supabase
            const { error: profileError } = await supabase
                .from('profiles')
                .insert([profileData]); // Ensure it's an array of objects
    
            if (profileError) {
                console.error('Profile creation error:', profileError);
                throw profileError;
            }
    
            console.log('Profile created successfully.');
    
            // Redirect only after profile creation
            this.showError('Account created successfully! Redirecting to complete your profile...', 'success');
    
            setTimeout(() => {
                console.log('Redirecting to profile completion...');
                window.location.href = `${this.getBaseUrl()}/pages/complete-profile.html`;
            }, 2000);
    
        } catch (error) {
            console.error('Signup error:', error);
            this.showError(error.message || 'An error occurred during signup. Please try again.');
        }
    }
    
    
} 