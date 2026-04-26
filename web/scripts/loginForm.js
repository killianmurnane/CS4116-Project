const loginForm = document.getElementById('login-form');
const registerForm = document.getElementById('register-form');
const showLoginButton = document.getElementById('show-login');
const showRegisterButton = document.getElementById('show-register');

// Get active form from query param
const activeForm = new URLSearchParams(window.location.search).get('activeForm') || 'login';

function showLogin() {
  loginForm.style.display = 'flex';
  registerForm.style.display = 'none';
  showLoginButton.classList.add('login-toggle-button-active');
  showRegisterButton.classList.remove('login-toggle-button-active');
}

function showRegister() {
  loginForm.style.display = 'none';
  registerForm.style.display = 'flex';
  showRegisterButton.classList.add('login-toggle-button-active');
  showLoginButton.classList.remove('login-toggle-button-active');
}

showLoginButton.addEventListener('click', showLogin);
showRegisterButton.addEventListener('click', showRegister);

if (activeForm === 'register') {
  showRegister();
} else {
  showLogin();
}
