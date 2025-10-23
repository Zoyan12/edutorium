document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    // Add event listeners to buttons
    const getStartedBtn = document.getElementById('getStartedBtn');
    const loginBtn = document.getElementById('loginBtn');

    getStartedBtn?.addEventListener('click', () => {
        window.location.href = 'pages/signup.html';
    });

    loginBtn?.addEventListener('click', () => {
        window.location.href = 'pages/login.html';
    });
} 