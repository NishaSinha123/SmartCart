<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartCart - Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .register-wrapper {
            max-width: 1200px;
            width: 100%;
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-wrap: wrap;
            overflow: hidden;
        }
        .hero-panel {
            flex: 1.2;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 2.5rem;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .hero-panel::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 160%;
            height: 160%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            animation: slowDrift 18s infinite alternate;
        }
        @keyframes slowDrift {
            0% { transform: scale(1) translate(0,0); opacity: 0.3; }
            100% { transform: scale(1.1) translate(-20px, -20px); opacity: 0.6; }
        }
        .hero-panel h1 { font-size: 2.6rem; font-weight: 800; margin-bottom: 1.25rem; position: relative; z-index: 2; }
        .hero-panel p { font-size: 1.05rem; line-height: 1.5; opacity: 0.92; margin-bottom: 2rem; position: relative; z-index: 2; }
        .feature-list { display: flex; flex-direction: column; gap: 1.2rem; margin-top: 1rem; position: relative; z-index: 2; }
        .feature-item { display: flex; align-items: center; gap: 1rem; font-size: 1rem; }
        .feature-item i { width: 1.8rem; font-size: 1.3rem; }
        .form-panel {
            flex: 1;
            background: white;
            padding: 2.5rem 2rem;
            overflow-y: auto;
            max-height: 90vh;
        }
        .logo-brand { text-align: center; margin-bottom: 1.5rem; }
        .logo-brand h2 {
            font-size: 1.9rem;
            font-weight: 800;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .logo-brand p { color: #6b7280; font-size: 0.85rem; margin-top: 6px; }
        .input-group { margin-bottom: 1.5rem; }
        .input-group label { display: block; font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem; color: #1f2937; }
        
        /* Base input styling */
        .input-icon {
            position: relative;
        }
        .input-icon i:first-child {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1rem;
            pointer-events: none;
            z-index: 2;
        }
        .input-icon input, .input-icon select {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 2.6rem;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.2s ease;
        }
        .input-icon input:focus, .input-icon select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        /* Password field with eye toggle - FIXED: SINGLE EYE ONLY, NO DUPLICATION */
        .password-wrapper {
            position: relative;
        }
        .password-wrapper input {
            width: 100%;
            padding: 0.85rem 2.6rem 0.85rem 2.6rem;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
        }
        .password-wrapper input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .password-wrapper .left-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            z-index: 2;
        }
        /* SINGLE EYE BUTTON - always visible, no duplicate */
        .password-wrapper .toggle-eye {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            z-index: 10;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            font-size: 1rem;
            transition: color 0.2s;
        }
        .password-wrapper .toggle-eye:hover { color: #667eea; }
        
        /* Hide browser's native password reveal button (Edge/Chrome) */
        .password-wrapper input::-ms-reveal,
        .password-wrapper input::-ms-clear {
            display: none;
        }
        
        .role-group { display: flex; gap: 1rem; margin-top: 0.5rem; }
        .role-card {
            flex: 1;
            border: 2px solid #e2e8f0;
            border-radius: 1rem;
            padding: 0.8rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        .role-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102,126,234,0.08) 0%, rgba(118,75,162,0.08) 100%);
        }
        .role-card input { display: none; }
        .role-icon { font-size: 2rem; margin-bottom: 4px; }
        .role-title { font-weight: 700; font-size: 0.9rem; }
        .role-desc { font-size: 0.7rem; color: #6b7280; }
        .strength-meter { margin-top: 8px; height: 4px; background: #e2e8f0; border-radius: 6px; overflow: hidden; }
        .strength-bar { width: 0%; height: 100%; transition: width 0.25s; }
        .strength-label { font-size: 0.7rem; margin-top: 5px; }
        .input-icon input.error-field, .password-wrapper input.error-field { border-color: #f43f5e; background-color: #fff5f5; }
        .input-icon input.valid-field, .password-wrapper input.valid-field { border-color: #10b981; }
        .error-text { color: #e11d48; font-size: 0.7rem; margin-top: 0.35rem; display: none; }
        .global-error {
            background: #fee2e2;
            border-left: 4px solid #dc2626;
            padding: 0.9rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            color: #991b1b;
            margin-bottom: 1.5rem;
        }
        .register-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.9rem;
            border-radius: 2rem;
            font-weight: 700;
            font-size: 1rem;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.25s;
            margin-top: 0.5rem;
        }
        .register-btn:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 12px 20px -12px #667eea; }
        .register-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        .login-link { text-align: center; margin-top: 1.5rem; font-size: 0.85rem; }
        .login-link a { color: #667eea; font-weight: 700; text-decoration: none; }
        .login-link a:hover { color: #764ba2; }
        
        /* Back to Home - HIGHLIGHTED BUTTON */
        .back-home {
            text-align: center;
            margin-top: 15px;
        }
        .back-home a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            background: #f3f4f6;
            color: #4b5563;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: 50px;
            transition: all 0.3s;
        }
        .back-home a:hover {
            background: #e5e7eb;
            color: #667eea;
            transform: translateX(-3px);
        }
        
        .checkbox-flex { display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .checkbox-flex input { width: 18px; height: 18px; cursor: pointer; accent-color: #667eea; }
        .toast-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 14px 24px;
            border-radius: 40px;
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
            z-index: 9999;
            transform: translateX(120%);
            transition: transform 0.25s ease;
        }
        .toast-message.show { transform: translateX(0); }
        .toast-success { border-left: 5px solid #10b981; }
        .toast-error { border-left: 5px solid #ef4444; }
        @media (max-width: 780px) { .hero-panel { display: none; } .form-panel { max-height: none; padding: 2rem 1.5rem; } }
    </style>
</head>
<body>
<div id="globalToast" class="toast-message"></div>

<div class="register-wrapper">
    <div class="hero-panel">
        <h1>Join SmartCart!</h1>
        <p>Shop smarter, track your budget, and never exceed your spending limits.</p>
        <div class="feature-list">
            <div class="feature-item"><i class="fas fa-shopping-cart"></i><span>Smart Shopping Cart</span></div>
            <div class="feature-item"><i class="fas fa-chart-line"></i><span>Real-time Budget Tracker</span></div>
            <div class="feature-item"><i class="fas fa-shield-alt"></i><span>Secure Payments</span></div>
        </div>
    </div>

    <div class="form-panel">
        <div class="logo-brand">
            <h2>🛒 SmartCart</h2>
            <p>Create your free account</p>
        </div>

        @if($errors->any())
        <div class="global-error">
            <i class="fas fa-exclamation-triangle"></i> 
            @foreach($errors->all() as $err) {{ $err }}<br> @endforeach
        </div>
        @endif

        <form method="POST" action="{{ route('register') }}" id="registerForm">
            @csrf

            <!-- Name -->
            <div class="input-group">
                <label for="name">Full name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="John Carter">
                </div>
                <div class="error-text" id="error-name"></div>
            </div>

            <!-- Email -->
            <div class="input-group">
                <label for="email">Email address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="hello@example.com">
                </div>
                <div class="error-text" id="error-email"></div>
            </div>

            <!-- Role -->
            <div class="input-group">
                <label>Account type</label>
                <div class="role-group">
                    <div class="role-card selected" data-role="user">
                        <input type="radio" name="role" value="user" checked>
                        <div class="role-icon">👤</div>
                        <div class="role-title">User</div>
                        <div class="role-desc">Shop & explore</div>
                    </div>
                    <div class="role-card" data-role="seller">
                        <input type="radio" name="role" value="seller">
                        <div class="role-icon">🏪</div>
                        <div class="role-title">Seller</div>
                        <div class="role-desc">Sell products</div>
                    </div>
                </div>
            </div>

            <!-- Password - FIXED: SINGLE EYE ONLY, NO DUPLICATE -->
            <div class="input-group">
                <label for="password">Password</label>
                <div class="password-wrapper" id="passwordWrapper">
                    <i class="fas fa-lock left-icon"></i>
                    <input type="password" id="password" name="password" placeholder="Create a strong password">
                    <i class="fas fa-eye toggle-eye" id="togglePassword"></i>
                </div>
                <div class="strength-meter"><div class="strength-bar" id="strengthBar"></div></div>
                <div class="strength-label" id="strengthMessage"></div>
                <div class="error-text" id="error-password"></div>
            </div>

            <!-- Confirm Password - FIXED: SINGLE EYE ONLY, NO DUPLICATE -->
            <div class="input-group">
                <label for="password_confirmation">Confirm password</label>
                <div class="password-wrapper" id="confirmWrapper">
                    <i class="fas fa-lock left-icon"></i>
                    <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password">
                    <i class="fas fa-eye toggle-eye" id="toggleConfirmPassword"></i>
                </div>
                <div class="error-text" id="error-confirm"></div>
            </div>

            <!-- Terms -->
            <div class="input-group">
                <label class="checkbox-flex">
                    <input type="checkbox" name="terms" id="termsCheckbox">
                    <span>I agree to the <a href="#" style="color:#667eea;">Terms of Service</a> and <a href="#" style="color:#667eea;">Privacy Policy</a></span>
                </label>
                <div class="error-text" id="error-terms"></div>
            </div>

            <button type="submit" class="register-btn" id="submitBtn">
                <i class="fas fa-user-plus"></i> Create account
            </button>
        </form>

        <div class="login-link">
            <p>Already registered? <a href="{{ route('login') }}">Sign in →</a></p>
        </div>

        <!-- Back to Home -->
        <div class="back-home">
            <a href="{{ url('/') }}">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<script>
    
    const passwordInput = document.getElementById('password');
    const confirmInput = document.getElementById('password_confirmation');
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirm = document.getElementById('toggleConfirmPassword');
    
    // Helper function to safely setup eye toggle 
    function setupEyeToggle(button, inputField) {
        if (!button || !inputField) return;
        
        // Remove any existing click listeners to avoid duplicates
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);
        
        // Update reference
        const finalButton = newButton;
        
        // Add fresh click handler
        finalButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const currentType = inputField.getAttribute('type');
            const newType = currentType === 'password' ? 'text' : 'password';
            inputField.setAttribute('type', newType);
            
            // Toggle eye icon classes
            if (newType === 'text') {
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
        
        return finalButton;
    }
    
    // Setup both eye buttons
    setupEyeToggle(togglePassword, passwordInput);
    setupEyeToggle(toggleConfirm, confirmInput);
    
    // Additional protection: MutationObserver to prevent any duplicate eye buttons from appearing
    function protectPasswordWrappers() {
        const wrappers = [document.getElementById('passwordWrapper'), document.getElementById('confirmWrapper')];
        wrappers.forEach(wrapper => {
            if (!wrapper) return;
            
            // Observe for any DOM changes that might add extra eye buttons
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        // Find all toggle-eye elements inside this wrapper
                        const allEyes = wrapper.querySelectorAll('.toggle-eye');
                        if (allEyes.length > 1) {
                            // Keep only the first one, remove extras
                            for (let i = 1; i < allEyes.length; i++) {
                                if (allEyes[i] && allEyes[i].parentNode) {
                                    allEyes[i].parentNode.removeChild(allEyes[i]);
                                }
                            }
                        }
                    }
                });
            });
            
            observer.observe(wrapper, { childList: true, subtree: true });
        });
    }
    
    protectPasswordWrappers();
    
    // Form validation and other functionality
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    const toastEl = document.getElementById('globalToast');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const termsCheck = document.getElementById('termsCheckbox');
    const errorName = document.getElementById('error-name');
    const errorEmail = document.getElementById('error-email');
    const errorPassword = document.getElementById('error-password');
    const errorConfirm = document.getElementById('error-confirm');
    const errorTerms = document.getElementById('error-terms');
    const strengthBar = document.getElementById('strengthBar');
    const strengthMsg = document.getElementById('strengthMessage');
    const roleCards = document.querySelectorAll('.role-card');

    function showToast(message, isError = true) {
        toastEl.innerHTML = `<i class="fas ${isError ? 'fa-circle-exclamation' : 'fa-circle-check'}"></i> ${message}`;
        toastEl.className = `toast-message ${isError ? 'toast-error' : 'toast-success'}`;
        toastEl.classList.add('show');
        setTimeout(() => toastEl.classList.remove('show'), 3200);
    }

    function setFieldValid(field, errorDiv) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        if (errorDiv) errorDiv.style.display = 'none';
    }

    function setFieldInvalid(field, errorDiv, message) {
        field.classList.add('error-field');
        field.classList.remove('valid-field');
        if (errorDiv) { errorDiv.innerText = message; errorDiv.style.display = 'block'; }
    }

    function validateName() {
        const val = nameInput.value.trim();
        if (val === '') { setFieldInvalid(nameInput, errorName, 'Full name is required'); return false; }
        if (!/^[a-zA-Z\s]{2,}$/.test(val)) { setFieldInvalid(nameInput, errorName, 'Use only letters and spaces (min 2 characters)'); return false; }
        setFieldValid(nameInput, errorName);
        return true;
    }

    function validateEmail() {
        const val = emailInput.value.trim();
        if (val === '') { setFieldInvalid(emailInput, errorEmail, 'Email address is required'); return false; }
        if (!/^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(val)) { setFieldInvalid(emailInput, errorEmail, 'Enter a valid email address'); return false; }
        setFieldValid(emailInput, errorEmail);
        return true;
    }

    function evaluatePasswordStrength(pwd) {
        if (pwd === '') return 0;
        let score = 0;
        if (pwd.length >= 8) score++;
        if (/[a-z]/.test(pwd)) score++;
        if (/[A-Z]/.test(pwd)) score++;
        if (/[0-9]/.test(pwd)) score++;
        if (/[\W_]/.test(pwd)) score++;
        return score;
    }

    function updateStrengthMeter() {
        const pwd = passwordInput.value;
        if (pwd === '') { strengthBar.style.width = '0%'; strengthMsg.innerText = ''; return; }
        const score = evaluatePasswordStrength(pwd);
        if (score <= 2) {
            strengthBar.style.width = '33%';
            strengthBar.style.backgroundColor = '#f43f5e';
            strengthMsg.innerHTML = '⚠️ Weak password';
            strengthMsg.style.color = '#e11d48';
        } else if (score <= 4) {
            strengthBar.style.width = '66%';
            strengthBar.style.backgroundColor = '#f59e0b';
            strengthMsg.innerHTML = '🟡 Medium strength';
            strengthMsg.style.color = '#b45309';
        } else {
            strengthBar.style.width = '100%';
            strengthBar.style.backgroundColor = '#10b981';
            strengthMsg.innerHTML = '✅ Strong password!';
            strengthMsg.style.color = '#0d9488';
        }
    }

    function validatePassword() {
        const pwd = passwordInput.value;
        if (pwd === '') { setFieldInvalid(passwordInput, errorPassword, 'Password is required'); return false; }
        if (pwd.length < 8) { setFieldInvalid(passwordInput, errorPassword, 'Password must be at least 8 characters'); return false; }
        if (!/[A-Za-z]/.test(pwd) || !/[0-9]/.test(pwd)) { setFieldInvalid(passwordInput, errorPassword, 'Use at least one letter and one number'); return false; }
        setFieldValid(passwordInput, errorPassword);
        return true;
    }

    function validateConfirm() {
        if (confirmInput.value === '') { setFieldInvalid(confirmInput, errorConfirm, 'Please confirm your password'); return false; }
        if (passwordInput.value !== confirmInput.value) { setFieldInvalid(confirmInput, errorConfirm, 'Passwords do not match'); return false; }
        setFieldValid(confirmInput, errorConfirm);
        return true;
    }

    function validateTerms() {
        if (!termsCheck.checked) { errorTerms.innerText = 'You must accept the Terms & Conditions'; errorTerms.style.display = 'block'; return false; }
        errorTerms.style.display = 'none';
        return true;
    }

    nameInput.addEventListener('input', validateName);
    emailInput.addEventListener('input', validateEmail);
    passwordInput.addEventListener('input', () => { updateStrengthMeter(); validatePassword(); if (confirmInput.value) validateConfirm(); });
    confirmInput.addEventListener('input', validateConfirm);
    termsCheck.addEventListener('change', () => { if (termsCheck.checked) errorTerms.style.display = 'none'; });

    roleCards.forEach(card => {
        card.addEventListener('click', () => {
            roleCards.forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            card.querySelector('input').checked = true;
        });
    });

    form.addEventListener('submit', function(e) {
        let isValid = true;
        if (!validateName()) isValid = false;
        if (!validateEmail()) isValid = false;
        if (!validatePassword()) isValid = false;
        if (!validateConfirm()) isValid = false;
        if (!validateTerms()) isValid = false;
        
        if (!isValid) {
            e.preventDefault();
            showToast('Please fix errors before submitting', true);
            const firstError = document.querySelector('.error-field');
            if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
    });
    
    // Also prevent browser's native password reveal from adding extra icons
    if (passwordInput) {
        passwordInput.addEventListener('focus', function() {
            // Ensure no duplicate eyes appear after focus (Edge/Chrome workaround)
            setTimeout(() => protectPasswordWrappers(), 10);
        });
    }
    
    console.log('Registration page ready - Eye toggle buttons are fixed (single eye, no duplication)');
</script>
</body>
</html>