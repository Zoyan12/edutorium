import { supabase } from '../config/supabase.js';

export class AuthManager {
    constructor() {
        this.redirectIfAuthenticated();
        this.setupAuthListener();
    }

    // Check if user is authenticated and redirect if needed
    async redirectIfAuthenticated() {
        const { data: { session } } = await supabase.auth.getSession();
        
        if (session) {
            // User is logged in
            const currentPath = window.location.pathname;
            const basePath = this.getBaseUrl();
            
            // Check if user needs to complete profile
            const forceProfileCompletion = localStorage.getItem('forceProfileCompletion') === 'true';
            
            // If force profile completion is set, ensure user stays on profile page
            if (forceProfileCompletion && !currentPath.endsWith('/complete-profile.html')) {
                console.log('User needs to complete profile, redirecting to profile page');
                window.location.href = `${basePath}/pages/complete-profile.html`;
                return;
            }
            
            // List of pages that should redirect to dashboard if user is logged in
            const restrictedPages = [
                `${basePath}/pages/login.html`,
                `${basePath}/pages/signup.html`,
                `${basePath}/index.html`,
                // Add variations of the paths
                `${basePath}/login.html`,
                `${basePath}/signup.html`,
                `${basePath}/`
            ];
            
            // Check if current page is in the restricted list
            if (restrictedPages.includes(currentPath) || 
                currentPath.endsWith('/login.html') || 
                currentPath.endsWith('/signup.html') || 
                currentPath.endsWith('/index.html') ||
                currentPath === basePath || 
                currentPath === `${basePath}/`) {
                console.log('User is already logged in, redirecting to dashboard');
                window.location.href = `${basePath}/pages/dashboard.php`;
                return;
            }
        } else {
            // User is not logged in
            const currentPath = window.location.pathname;
            const basePath = this.getBaseUrl();
            
            // List of pages that require authentication
            const protectedPages = [
                `${basePath}/pages/dashboard.php`,
                `${basePath}/pages/complete-profile.html`
            ];
            
            // Check if current page requires authentication
            if (protectedPages.includes(currentPath) || 
                currentPath.endsWith('/dashboard.php') || 
                currentPath.endsWith('/complete-profile.html')) {
                console.log('User is not logged in, redirecting to login page');
                window.location.href = `${basePath}/pages/login.html`;
                return;
            }
        }
    }

    // Set up auth state change listener
    setupAuthListener() {
        supabase.auth.onAuthStateChange((event, session) => {
            console.log('Auth state changed:', event, session ? 'User logged in' : 'User logged out');
            
            if (event === 'SIGNED_IN') {
                this.redirectIfAuthenticated();
            } else if (event === 'SIGNED_OUT') {
                // Redirect to home page on logout
                const basePath = this.getBaseUrl();
                window.location.href = `${basePath}/index.html`;
            }
        });
    }

    // Get the base URL for redirects
    getBaseUrl() {
        const path = window.location.pathname;
        let basePath;
        // Handle different path formats
        if (path.includes('/pages/')) {
            basePath = path.split('/pages')[0];
        } else if (path.endsWith('.html') || path.endsWith('.php')) {
            basePath = path.substring(0, path.lastIndexOf('/'));
        } else {
            basePath = path;
        }
        
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
} 