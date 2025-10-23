import { supabase } from '../config/supabase.js';

export class ProfileUI {
    constructor() {
        this.profileForm = document.getElementById('profileForm');
        this.errorContainer = document.getElementById('errorContainer');
        this.profilePicture = document.getElementById('profilePicture');
        this.profilePreview = document.getElementById('profilePreview');
        this.uploadOverlay = document.querySelector('.upload-overlay');
        
        this.initializeEventListeners();
        this.checkExistingProfile();
    }

    getBaseUrl() {
        // Get the current URL path
        const path = window.location.pathname;
        // Extract the base path (e.g., /new)
        const basePath = path.split('/pages')[0];
        return basePath;
    }

    initializeEventListeners() {
        if (this.profileForm) {
            this.profileForm.addEventListener('submit', (e) => this.handleProfileSubmit(e));
        }

        // Profile picture upload handling
        if (this.profilePicture) {
            this.profilePicture.addEventListener('change', (e) => this.handleProfilePictureChange(e));
            this.uploadOverlay.addEventListener('click', () => this.profilePicture.click());
        }
    }

    showError(message, type = 'error') {
        if (this.errorContainer) {
            this.errorContainer.innerHTML = `
                <div class="message ${type}">
                    <p>${message}</p>
                </div>
            `;
        }
    }

    async handleProfilePictureChange(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showError('Please upload an image file');
            return;
        }

        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('Image size should be less than 5MB');
            return;
        }

        // Preview image
        const reader = new FileReader();
        reader.onload = (e) => {
            this.profilePreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    async checkExistingProfile() {
        try {
            // First check if we have a userId in localStorage (from signup)
            const storedUserId = localStorage.getItem('userId');
            const forceProfileCompletion = localStorage.getItem('forceProfileCompletion') === 'true';
            
            // Get current authenticated user
            const { data: { user }, error: userError } = await supabase.auth.getUser();
            
            if (userError) {
                console.error('Auth error:', userError);
            }
            
            // Use either the authenticated user or the stored userId
            const userId = user?.id || storedUserId;
            
            if (!userId) {
                console.log('No user ID found, redirecting to login');
                window.location.href = `${this.getBaseUrl()}/pages/login.html`;
                return;
            }

            console.log('Checking profile for user:', userId);

            // Check if profile exists
            try {
                const { data: profile, error } = await supabase
                    .from('profiles')
                    .select('*')
                    .eq('user_id', userId)
                    .maybeSingle();  // Use maybeSingle to avoid errors if not found

                if (error && error.code !== 'PGRST116') {
                    console.error('Error fetching profile:', error);
                    throw error;
                }

                console.log('Profile check result:', profile);
                console.log('Force profile completion:', forceProfileCompletion);

                // If a profile exists and is complete and we're not forcing profile completion
                if (profile && profile.is_complete === true && !forceProfileCompletion) {
                    console.log('Profile is already complete and not forced to complete, redirecting to dashboard');
                    // Clear localStorage data
                    this.clearLocalStorage();
                    
                    window.location.href = `${this.getBaseUrl()}/pages/dashboard.html`;
                    return;
                }
                
                // If profile exists but is incomplete or we're forcing completion
                if (profile) {
                    console.log('Profile found, pre-filling form for completion');
                    this.populateForm(profile);
                } else {
                    console.log('No profile found, will create one during form submission');
                    
                    // Pre-fill form fields from localStorage if available
                    const storedUsername = localStorage.getItem('userUsername');
                    const storedField = localStorage.getItem('userField');
                    
                    if (storedUsername) {
                        // If we have stored data, we can pre-fill a dummy profile
                        const dummyProfile = {
                            username: storedUsername,
                            field: storedField || ''
                        };
                        this.populateForm(dummyProfile);
                    }
                    
                    // Create initial profile if it doesn't exist yet
                    if (userId) {
                        console.log('Creating initial profile for user:', userId);
                        await this.createInitialProfile(userId);
                    }
                }
            } catch (error) {
                console.error('Error checking profile:', error);
                this.showError('Error checking profile: ' + error.message);
            }
        } catch (error) {
            console.error('Profile check error:', error);
            this.showError('An error occurred while checking your profile. Please try logging in again.');
            setTimeout(() => {
                window.location.href = `${this.getBaseUrl()}/pages/login.html`;
            }, 2000);
        }
    }

    clearLocalStorage() {
        localStorage.removeItem('userId');
        localStorage.removeItem('userUsername');
        localStorage.removeItem('userField');
        localStorage.removeItem('forceProfileCompletion');
    }

    async createInitialProfile(userId) {
        try {
            // Get user metadata from auth
            const { data: { user }, error: userError } = await supabase.auth.getUser();
            
            if (userError) throw userError;
            
            // Extract field and username from user metadata
            const field = user.user_metadata?.field || '';
            const username = user.user_metadata?.username || '';
            
            // Create initial profile record with basic fields only
            // Omitting points field to avoid errors if it doesn't exist
            const profileData = {
                user_id: userId,
                username: username,
                field: field,
                full_name: '',
                phone: '',
                location: '',
                education: '',
                board: '',
                is_complete: false
            };
            
            // Try to add points field if it exists
            try {
                // Check if points column exists
                const { data, error } = await supabase
                    .from('profiles')
                    .select('points')
                    .limit(1);
                
                // If no error, the column exists
                if (!error) {
                    profileData.points = 0;
                }
            } catch (columnError) {
                console.log('Points column check failed, omitting points field');
            }
                
            const { error: insertError } = await supabase
                .from('profiles')
                .insert(profileData);
                
            if (insertError) throw insertError;
            
            console.log('Initial profile created successfully');
        } catch (error) {
            console.error('Error creating initial profile:', error);
            this.showError('Failed to initialize your profile. Please try again.');
        }
    }

    populateForm(profile) {
        // Pre-fill form with existing data if available
        if (profile) {
            if (profile.full_name) {
                document.getElementById('fullName').value = profile.full_name;
            }
            if (profile.phone) {
                document.getElementById('phone').value = profile.phone;
            }
            if (profile.location) {
                document.getElementById('location').value = profile.location;
            }
            if (profile.education) {
                document.getElementById('education').value = profile.education;
            }
            if (profile.board) {
                document.getElementById('board').value = profile.board;
            }
            // If avatar URL exists, show it in the preview
            if (profile.avatar_url) {
                this.profilePreview.src = profile.avatar_url;
            }
        }
    }

    validateForm() {
        const fullName = document.getElementById('fullName').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const location = document.getElementById('location').value.trim();
        const education = document.getElementById('education').value;
        const board = document.getElementById('board').value;

        if (!fullName) {
            this.showError('Please enter your full name');
            return false;
        }

        if (!phone || !/^\d{10}$/.test(phone)) {
            this.showError('Please enter a valid 10-digit phone number');
            return false;
        }

        if (!location) {
            this.showError('Please enter your location');
            return false;
        }

        if (!education) {
            this.showError('Please select your class');
            return false;
        }

        if (!board) {
            this.showError('Please select your board');
            return false;
        }

        return true;
    }

    async handleProfileSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            return;
        }

        try {
            // Show loading indicator
            this.showError('Updating your profile...', 'info');
            
            const fullName = document.getElementById('fullName').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const location = document.getElementById('location').value.trim();
            const education = document.getElementById('education').value;
            const board = document.getElementById('board').value;
            const profilePicture = this.profilePicture.files[0];

            // Try to get user from auth
            const { data: { user }, error: userError } = await supabase.auth.getUser();
            
            // Get userId from localStorage as fallback
            const storedUserId = localStorage.getItem('userId');
            
            // Use authenticated user ID or stored ID
            const userId = user?.id || storedUserId;
            
            if (!userId) {
                throw new Error('No user ID found. Please try logging in again.');
            }

            console.log('Updating profile for user ID:', userId);

            // Get username and field from localStorage if available
            const userUsername = localStorage.getItem('userUsername') || 
                               user?.user_metadata?.username || 
                               'user_' + Math.random().toString(36).substring(2, 10);
            
            const userField = localStorage.getItem('userField') || 
                            user?.user_metadata?.field || '';

            let avatarUrl = null;

            // Upload profile picture if selected
            if (profilePicture) {
                try {
                    console.log('Uploading profile picture');
                    const fileExt = profilePicture.name.split('.').pop();
                    const fileName = `${userId}-${Math.random().toString(36).substring(2, 10)}.${fileExt}`;
                    
                    const { data: uploadData, error: uploadError } = await supabase.storage
                        .from('avatars')
                        .upload(fileName, profilePicture, {
                            cacheControl: '3600',
                            upsert: false
                        });

                    if (uploadError) {
                        console.error('Error uploading file:', uploadError);
                        throw uploadError;
                    }

                    // Get public URL
                    const { data: { publicUrl } } = supabase.storage
                        .from('avatars')
                        .getPublicUrl(fileName);

                    avatarUrl = publicUrl;
                    console.log('Profile picture uploaded, URL:', avatarUrl);
                } catch (storageError) {
                    console.error('Storage error:', storageError);
                    this.showError('Failed to upload profile picture, but continuing with profile update', 'warning');
                }
            }

            // Prepare basic profile data
            const profileData = {
                user_id: userId,
                username: userUsername,
                field: userField,
                full_name: fullName,
                phone: phone,
                location: location,
                education: education,
                board: board,
                is_complete: true
            };
            
            // Add avatar if available
            if (avatarUrl) {
                profileData.avatar_url = avatarUrl;
            }
            
            // Check if points column exists and add a default value if it does
            try {
                const { data: pointsCheck, error: pointsError } = await supabase
                    .from('profiles')
                    .select('points')
                    .limit(1);
                    
                if (!pointsError) {
                    profileData.points = 0;
                    profileData.wins = 0;
                    profileData.streak = 0;
                    profileData.battles = 0;
                }
            } catch (e) {
                console.log('Points columns may not exist, skipping');
            }
            
            // Check if profile already exists
            const { data: existingProfile, error: checkError } = await supabase
                .from('profiles')
                .select('id, is_complete')
                .eq('user_id', userId)
                .maybeSingle();  // Use maybeSingle to avoid errors if not found
                
            console.log('Existing profile check:', existingProfile);

            let updateResult;
            
            if (existingProfile) {
                // Update existing profile
                console.log('Updating existing profile');
                updateResult = await supabase
                    .from('profiles')
                    .update(profileData)
                    .eq('user_id', userId);
            } else {
                // Create new profile
                console.log('Creating new profile');
                updateResult = await supabase
                    .from('profiles')
                    .insert([profileData]);
            }

            if (updateResult.error) {
                console.error('Profile update/insert error:', updateResult.error);
                throw updateResult.error;
            }

            // Clear all localStorage items
            this.clearLocalStorage();

            console.log('Profile saved successfully');
            this.showError('Profile completed successfully! Redirecting...', 'success');
            
            setTimeout(() => {
                window.location.href = `${this.getBaseUrl()}/pages/dashboard.html`;
            }, 1500);
        } catch (error) {
            console.error('Profile update error:', error);
            this.showError('Error updating profile: ' + error.message);
        }
    }
} 