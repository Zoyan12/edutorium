import { FriendService } from '../services/FriendService.js';

/**
 * Class for handling friends UI interactions
 */
export class FriendsUI {
    /**
     * Constructor for FriendsUI
     */
    constructor() {
        this.initElements();
        this.initEventListeners();
        this.friendsCache = new Map(); // Cache to store friend data with ID as key
        this.requestsCache = new Map(); // Cache to store request data with ID as key
        this.searchResultsCache = new Map(); // Cache to store search result data with ID as key
        this.onlineUserCount = 0;
        
        // Set initial online status
        this.updateUserStatus('online');
        
        // Poll for friend requests and online count every 30 seconds
        setInterval(() => this.checkFriendRequests(), 30000);
        setInterval(() => this.updateOnlineUserCount(), 60000);
        
    }
    
    /**
     * Initialize UI elements
     */
    initElements() {
        // Drawer elements
        this.friendsDrawer = document.getElementById('friendsDrawer');
        this.friendsClose = document.getElementById('friendsClose');
        this.friendsNavItem = document.getElementById('friendsNavItem');
        
        // Tab elements
        this.friendsTabs = document.querySelectorAll('.friends-tab');
        this.friendsTabContents = document.querySelectorAll('.friends-tab-content');
        
        // List containers
        this.friendsListContainer = document.getElementById('friendsListContainer');
        this.requestsContainer = document.getElementById('requestsContainer');
        this.searchResultsContainer = document.getElementById('searchResultsContainer');
        
        // Search elements
        this.searchBtn = document.getElementById('searchBtn');
        this.friendSearch = document.getElementById('friendSearch');
        
        // Request count element
        this.requestCount = document.getElementById('requestCount');
        
        // Online user count element
        this.onlineUsersElement = document.querySelector('.friends-header span');
        this.onlinePlayers = document.querySelector('.stat span');
    }
    
    /**
     * Initialize event listeners
     */
    initEventListeners() {
        // Open/close friends drawer
        this.friendsNavItem.addEventListener('click', (e) => {
            e.preventDefault();
            this.openFriendsDrawer();
        });
        
        this.friendsClose.addEventListener('click', () => {
            this.closeFriendsDrawer();
        });
        
        // Handle friends tabs
        this.friendsTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.activateTab(tab.getAttribute('data-tab'));
                
                // Load appropriate content for the tab
                const tabId = tab.getAttribute('data-tab');
                if (tabId === 'friendsList') {
                    this.loadFriends();
                } else if (tabId === 'friendRequests') {
                    this.loadFriendRequests();
                }
            });
        });
        
        // Handle search functionality
        this.searchBtn.addEventListener('click', () => this.searchFriends());
        this.friendSearch.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.searchFriends();
            }
        });
        
        // Add global event listeners
        window.addEventListener('beforeunload', () => {
            // Set status to offline when leaving the page
            this.updateUserStatus('offline');
        });
        
        // Set up activity tracking
        let inactivityTimer;
        const resetTimer = () => {
            clearTimeout(inactivityTimer);
            // If status was away, set it back to online
            if (this._lastStatus === 'away') {
                this.updateUserStatus('online');
            }
            
            // Set status to away after 5 minutes of inactivity
            inactivityTimer = setTimeout(() => {
                this.updateUserStatus('away');
            }, 5 * 60 * 1000);
        };
        
        // Reset timer on user activity
        ['mousemove', 'keypress', 'click', 'touchstart', 'scroll'].forEach(eventType => {
            document.addEventListener(eventType, resetTimer);
        });
        
        // Initial timer start
        resetTimer();
    }
    
    /**
     * Open friends drawer and load data
     */
    openFriendsDrawer() {
        this.friendsDrawer.classList.add('active');
        if (window.innerWidth <= 768) {
            document.body.classList.add('friends-open');
        }
        
        // Load friends list and check for requests
        this.loadFriends();
        this.loadFriendRequests();
        this.updateOnlineUserCount();
    }
    
    /**
     * Close friends drawer
     */
    closeFriendsDrawer() {
        this.friendsDrawer.classList.remove('active');
        document.body.classList.remove('friends-open');
    }
    
    /**
     * Activate a specific tab
     * @param {string} tabId - The ID of the tab to activate
     */
    activateTab(tabId) {
        // Remove active class from all tabs and contents
        this.friendsTabs.forEach(t => t.classList.remove('active'));
        this.friendsTabContents.forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding content
        document.querySelector(`[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(tabId).classList.add('active');
        
        // If "Find Friends" tab is active, focus the search input
        if (tabId === 'findFriends') {
            this.friendSearch.focus();
        }
    }
    
    /**
     * Load and display the user's friends list
     */
    async loadFriends() {
        try {
            this.showFriendsLoading();
            
            const friends = await FriendService.getFriends();
            
            // Clear cache and repopulate
            this.friendsCache.clear();
            friends.forEach(friend => {
                this.friendsCache.set(friend.friend_id, friend);
            });
            
            this.renderFriendsList(friends);
        } catch (error) {
            console.error('Error loading friends:', error);
            this.showFriendsError('Could not load your friends list. Please try again later.');
        }
    }
    
    /**
     * Show loading state in friends list
     */
    showFriendsLoading() {
        this.friendsListContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading your friends...</p>
            </div>
        `;
    }
    
    /**
     * Show error state in friends list
     * @param {string} message - Error message to display
     */
    showFriendsError(message) {
        this.friendsListContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Render the friends list
     * @param {Array} friends - Array of friend objects
     */
    renderFriendsList(friends) {
        if (!friends || friends.length === 0) {
            this.friendsListContainer.innerHTML = `
                <div class="no-friends-message">
                    <i class="fas fa-user-friends"></i>
                    <p>You haven't added any friends yet.</p>
                    <p>Search for users to add them as friends!</p>
                </div>
            `;
            return;
        }
        
        this.friendsListContainer.innerHTML = '';
        
        friends.forEach(friend => {
            const status = friend.status || 'offline';
            const avatar = friend.avatar_url || '../assets/default.png';
            
            const friendElement = document.createElement('div');
            friendElement.className = 'friend-item';
            friendElement.innerHTML = `
                <div class="friend-avatar">
                    <img src="${avatar}" alt="${friend.username}" onerror="this.src='../assets/default.png'">
                    <div class="friend-status status-${status}"></div>
                </div>
                <div class="friend-info">
                    <p class="friend-name">${friend.full_name || friend.username}</p>
                    <p class="friend-field">${friend.field || 'Student'}</p>
                </div>
                <div class="friend-actions">
                    <button class="friend-action-btn battle" title="Challenge to battle" data-id="${friend.friend_id}">
                        <i class="fas fa-gamepad"></i>
                    </button>
                    <button class="friend-action-btn remove-friend" title="Remove friend" data-id="${friend.friend_id}">
                        <i class="fas fa-user-minus"></i>
                    </button>
                </div>
            `;
            
            this.friendsListContainer.appendChild(friendElement);
            
            // Add event listeners for buttons
            const battleBtn = friendElement.querySelector('.battle');
            const removeBtn = friendElement.querySelector('.remove-friend');
            
            battleBtn.addEventListener('click', () => this.handleBattleRequest(friend.friend_id));
            removeBtn.addEventListener('click', () => this.handleRemoveFriend(friend.friend_id));
        });
    }
    
    /**
     * Load and display pending friend requests
     */
    async loadFriendRequests() {
        try {
            this.showRequestsLoading();
            
            const requests = await FriendService.getPendingRequests();
            
            // Update request count
            this.requestCount.textContent = `(${requests.length})`;
            
            // Clear cache and repopulate
            this.requestsCache.clear();
            requests.forEach(request => {
                this.requestsCache.set(request.request_id, request);
            });
            
            this.renderFriendRequests(requests);
        } catch (error) {
            console.error('Error loading friend requests:', error);
            this.showRequestsError('Could not load your friend requests. Please try again later.');
        }
    }
    
    /**
     * Show loading state in requests list
     */
    showRequestsLoading() {
        this.requestsContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Loading friend requests...</p>
            </div>
        `;
    }
    
    /**
     * Show error state in requests list
     * @param {string} message - Error message to display
     */
    showRequestsError(message) {
        this.requestsContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Render friend requests list
     * @param {Array} requests - Array of friend request objects
     */
    renderFriendRequests(requests) {
        if (!requests || requests.length === 0) {
            this.requestsContainer.innerHTML = `
                <div class="no-friends-message">
                    <i class="fas fa-user-clock"></i>
                    <p>No pending friend requests.</p>
                </div>
            `;
            return;
        }
        
        this.requestsContainer.innerHTML = '';
        
        requests.forEach(request => {
            const avatar = request.avatar_url || '../assets/default.png';
            
            const requestElement = document.createElement('div');
            requestElement.className = 'friend-item';
            requestElement.innerHTML = `
                <div class="friend-avatar">
                    <img src="${avatar}" alt="${request.username}" onerror="this.src='../assets/default.png'">
                </div>
                <div class="friend-info">
                    <p class="friend-name">${request.full_name || request.username}</p>
                    <p class="friend-field">${request.field || 'Student'}</p>
                </div>
                <div class="friend-actions">
                    <button class="friend-action-btn accept" title="Accept request" data-id="${request.request_id}">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="friend-action-btn reject" title="Reject request" data-id="${request.request_id}">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            this.requestsContainer.appendChild(requestElement);
            
            // Add event listeners for buttons
            const acceptBtn = requestElement.querySelector('.accept');
            const rejectBtn = requestElement.querySelector('.reject');
            
            acceptBtn.addEventListener('click', () => this.handleAcceptRequest(request.request_id));
            rejectBtn.addEventListener('click', () => this.handleRejectRequest(request.request_id));
        });
    }
    
    /**
     * Handle accepting a friend request
     * @param {string} requestId - The ID of the request to accept
     */
    async handleAcceptRequest(requestId) {
        try {
            // Disable the buttons to prevent multiple clicks
            const requestElement = document.querySelector(`.friend-action-btn.accept[data-id="${requestId}"]`).closest('.friend-item');
            const buttons = requestElement.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Optimistic UI update
            requestElement.querySelector('.friend-actions').innerHTML = `
                <div class="friend-action-status success">
                    <i class="fas fa-check-circle"></i> Accepted
                </div>
            `;
            
            // Make the API call
            await FriendService.acceptFriendRequest(requestId);
            
            // Update the counts
            const currentCount = parseInt(this.requestCount.textContent.match(/\d+/)[0] || '0');
            this.requestCount.textContent = `(${Math.max(0, currentCount - 1)})`;
            
            // Reload friends list after a short delay
            setTimeout(() => {
                this.loadFriends();
                
                // Remove the request from the UI
                setTimeout(() => {
                    requestElement.style.height = '0';
                    requestElement.style.opacity = '0';
                    requestElement.style.margin = '0';
                    requestElement.style.padding = '0';
                    requestElement.style.overflow = 'hidden';
                    
                    setTimeout(() => {
                        requestElement.remove();
                        
                        // Check if there are no more requests
                        if (!this.requestsContainer.querySelector('.friend-item')) {
                            this.renderFriendRequests([]);
                        }
                    }, 300);
                }, 1000);
            }, 500);
        } catch (error) {
            console.error('Error accepting friend request:', error);
            alert('Failed to accept friend request. Please try again.');
            
            // Reload requests to restore original state
            this.loadFriendRequests();
        }
    }
    
    /**
     * Handle rejecting a friend request
     * @param {string} requestId - The ID of the request to reject
     */
    async handleRejectRequest(requestId) {
        try {
            // Disable the buttons to prevent multiple clicks
            const requestElement = document.querySelector(`.friend-action-btn.reject[data-id="${requestId}"]`).closest('.friend-item');
            const buttons = requestElement.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Optimistic UI update
            requestElement.querySelector('.friend-actions').innerHTML = `
                <div class="friend-action-status">
                    <i class="fas fa-times-circle"></i> Rejected
                </div>
            `;
            
            // Make the API call
            await FriendService.rejectFriendRequest(requestId);
            
            // Update the counts
            const currentCount = parseInt(this.requestCount.textContent.match(/\d+/)[0] || '0');
            this.requestCount.textContent = `(${Math.max(0, currentCount - 1)})`;
            
            // Remove the request from the UI after animation
            setTimeout(() => {
                requestElement.style.height = '0';
                requestElement.style.opacity = '0';
                requestElement.style.margin = '0';
                requestElement.style.padding = '0';
                requestElement.style.overflow = 'hidden';
                
                setTimeout(() => {
                    requestElement.remove();
                    
                    // Check if there are no more requests
                    if (!this.requestsContainer.querySelector('.friend-item')) {
                        this.renderFriendRequests([]);
                    }
                }, 300);
            }, 1000);
        } catch (error) {
            console.error('Error rejecting friend request:', error);
            alert('Failed to reject friend request. Please try again.');
            
            // Reload requests to restore original state
            this.loadFriendRequests();
        }
    }
    
    /**
     * Handle removing a friend
     * @param {string} friendId - The ID of the friend to remove
     */
    async handleRemoveFriend(friendId) {
        if (!confirm('Are you sure you want to remove this friend?')) {
            return;
        }
        
        try {
            // Get the friend element and disable buttons
            const friendElement = document.querySelector(`.friend-action-btn.remove-friend[data-id="${friendId}"]`).closest('.friend-item');
            const buttons = friendElement.querySelectorAll('button');
            buttons.forEach(btn => btn.disabled = true);
            
            // Optimistic UI update
            friendElement.querySelector('.friend-actions').innerHTML = `
                <div class="friend-action-status">
                    <i class="fas fa-user-minus"></i> Removed
                </div>
            `;
            
            // Make the API call
            await FriendService.removeFriend(friendId);
            
            // Remove the friend from the UI after animation
            setTimeout(() => {
                friendElement.style.height = '0';
                friendElement.style.opacity = '0';
                friendElement.style.margin = '0';
                friendElement.style.padding = '0';
                friendElement.style.overflow = 'hidden';
                
                setTimeout(() => {
                    friendElement.remove();
                    
                    // Remove from cache
                    this.friendsCache.delete(friendId);
                    
                    // Check if there are no more friends
                    if (!this.friendsListContainer.querySelector('.friend-item')) {
                        this.renderFriendsList([]);
                    }
                }, 300);
            }, 1000);
        } catch (error) {
            console.error('Error removing friend:', error);
            alert('Failed to remove friend. Please try again.');
            
            // Reload friends to restore original state
            this.loadFriends();
        }
    }
    
    /**
     * Handle battle request for a friend
     * @param {string} friendId - The ID of the friend to battle
     */
    handleBattleRequest(friendId) {
        const friend = this.friendsCache.get(friendId);
        if (!friend) {
            console.error('Friend not found in cache');
            return;
        }
        
        alert(`Challenge sent to ${friend.full_name || friend.username}!\n\nThis feature is coming soon.`);
    }
    
    /**
     * Search for users by username or full name
     */
    async searchFriends() {
        const searchTerm = this.friendSearch.value.trim();
        
        if (!searchTerm) {
            alert('Please enter a search term');
            return;
        }
        
        // Switch to find friends tab
        this.activateTab('findFriends');
        
        try {
            this.showSearchLoading();
            
            const results = await FriendService.searchUsers(searchTerm);
            
            // Clear cache and repopulate
            this.searchResultsCache.clear();
            results.forEach(result => {
                this.searchResultsCache.set(result.user_id, result);
            });
            
            this.renderSearchResults(results, searchTerm);
        } catch (error) {
            console.error('Error searching users:', error);
            this.showSearchError('Could not complete your search. Please try again later.');
        }
    }
    
    /**
     * Show loading state for search results
     */
    showSearchLoading() {
        this.searchResultsContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Searching for users...</p>
            </div>
        `;
    }
    
    /**
     * Show error state for search results
     * @param {string} message - Error message to display
     */
    showSearchError(message) {
        this.searchResultsContainer.innerHTML = `
            <div class="no-friends-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>${message}</p>
            </div>
        `;
    }
    
    /**
     * Render search results
     * @param {Array} results - Array of search result objects
     * @param {string} searchTerm - The search term used
     */
    renderSearchResults(results, searchTerm) {
        if (!results || results.length === 0) {
            this.searchResultsContainer.innerHTML = `
                <div class="no-friends-message">
                    <i class="fas fa-search"></i>
                    <p>No users found matching "${searchTerm}"</p>
                    <p>Try a different search term</p>
                </div>
            `;
            return;
        }
        
        this.searchResultsContainer.innerHTML = '';
        
        results.forEach(result => {
            const avatar = result.avatar_url || '../assets/default.png';
            
            // Determine button state based on friendship status
            let buttonHtml = '';
            
            if (result.is_friend) {
                buttonHtml = `
                    <button class="add-friend-btn sent" disabled>
                        <i class="fas fa-check"></i> Friends
                    </button>
                `;
            } else if (result.request_status === 'pending') {
                buttonHtml = `
                    <button class="add-friend-btn sent" disabled>
                        <i class="fas fa-clock"></i> Request Pending
                    </button>
                `;
            } else {
                buttonHtml = `
                    <button class="add-friend-btn" data-id="${result.user_id}">
                        <i class="fas fa-user-plus"></i> Add Friend
                    </button>
                `;
            }
            
            const resultElement = document.createElement('div');
            resultElement.className = 'search-result-item';
            resultElement.innerHTML = `
                <div class="friend-avatar">
                    <img src="${avatar}" alt="${result.username}" onerror="this.src='../assets/default.png'">
                </div>
                <div class="friend-info">
                    <p class="friend-name">${result.full_name || result.username}</p>
                    <p class="friend-field">${result.field || 'Student'}</p>
                </div>
                ${buttonHtml}
            `;
            
            this.searchResultsContainer.appendChild(resultElement);
            
            // Add event listener for add friend button if it's not disabled
            if (!result.is_friend && result.request_status !== 'pending') {
                const addBtn = resultElement.querySelector('.add-friend-btn');
                addBtn.addEventListener('click', () => this.handleSendFriendRequest(result.user_id));
            }
        });
    }
    
    /**
     * Handle sending a friend request
     * @param {string} userId - The ID of the user to send a request to
     */
    async handleSendFriendRequest(userId) {
        try {
            // Get the button and disable it
            const button = document.querySelector(`.add-friend-btn[data-id="${userId}"]`);
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            // Make the API call
            await FriendService.sendFriendRequest(userId);
            
            // Update the button
            button.innerHTML = '<i class="fas fa-check"></i> Request Sent';
            button.classList.add('sent');
            
            // Update the cache
            const result = this.searchResultsCache.get(userId);
            if (result) {
                result.request_status = 'pending';
                this.searchResultsCache.set(userId, result);
            }
        } catch (error) {
            console.error('Error sending friend request:', error);
            alert('Failed to send friend request: ' + (error.message || 'Please try again.'));
            
            // Reset the button
            const button = document.querySelector(`.add-friend-btn[data-id="${userId}"]`);
            button.disabled = false;
            button.innerHTML = '<i class="fas fa-user-plus"></i> Add Friend';
        }
    }
    
    /**
     * Check for new friend requests
     */
    async checkFriendRequests() {
        if (!this.friendsDrawer.classList.contains('active')) {
            try {
                const requests = await FriendService.getPendingRequests();
                
                // Update the request count badge
                this.requestCount.textContent = `(${requests.length})`;
                
                // If there are new requests and we're on the requests tab, update the list
                if (document.querySelector('[data-tab="friendRequests"]').classList.contains('active')) {
                    this.renderFriendRequests(requests);
                }
            } catch (error) {
                console.error('Error checking friend requests:', error);
            }
        }
    }
    
    /**
     * Update the online user count
     * @returns {Promise<number>} The number of online users
     */
    async updateOnlineUserCount() {
        try {
            const count = await FriendService.getOnlineUserCount();
            this.onlineUserCount = count;
            
            if (this.onlineUsersElement) {
                this.onlineUsersElement.textContent = `${count} player(s) online`;
            }
            
            return count;
        } catch (error) {
            console.error('Error updating online user count:', error);
            return 0; // Return 0 as a fallback
        }
    }
    
    /**
     * Update the current user's status
     * @param {string} status - The status to set ('online', 'offline', 'away')
     */
    async updateUserStatus(status) {
        // Store the status locally regardless of API success
        this._lastStatus = status;
        
        // Skip server updates if previous attempt failed (prevents flooding with errors)
        if (this._statusUpdateFailed) {
            console.log(`Skipping status update to '${status}' due to previous failures`);
            return;
        }
        
        try {
            // Set a timeout to prevent hanging if the request takes too long
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('Status update timed out')), 5000);
            });
            
            // Try to update status with a timeout
            await Promise.race([
                FriendService.updateUserStatus(status),
                timeoutPromise
            ]);
            
            // If successful, reset failure flag
            this._statusUpdateFailed = false;
        } catch (error) {
            console.error('Error updating user status:', error);
            
            // Set failure flag to avoid continuous failed attempts
            this._statusUpdateFailed = true;
            
            // After 1 minute, try again to see if the issue is resolved
            setTimeout(() => {
                this._statusUpdateFailed = false;
                console.log('Resetting status update failure flag');
            }, 60000);
        }
    }
} 