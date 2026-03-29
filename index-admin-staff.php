<?php
require_once __DIR__ . '/includes/auth.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirectBasedOnRole();
}

// Logic to handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = $_POST['role'] ?? '';

    if (!empty($username) && !empty($password) && !empty($role)) {
        // This function should be defined in your includes/auth.php
        $result = loginUser($username, $password, $role);
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            if ($result === true) {
                echo json_encode(['success' => true, 'role' => $role]);
            } else {
                echo json_encode(['success' => false, 'message' => $result ?: 'Invalid username or password.']);
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Barangay Luz, Cebu City</title>
    <link rel="icon" type="image/png" href="./asssets/images/Luz.jpg">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Local Font Awesome for offline support -->
    <link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {  
            min-height: 100vh;
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0;
            margin: 0;
            background: white; /* Changed to white background */
            position: relative;
        }

        .container {
            display: flex;
            width: 100vw;
            height: 100vh;
            max-width: none;
            background: transparent;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* --- Left Branding with Background Image --- */
        .left-section {
            flex: 1;
            background: linear-gradient(135deg, rgba(58, 148, 223, 0.85) 0%, rgba(46, 124, 192, 0.85) 100%), 
                        url('./asssets/images/brgyluz.jpg') no-repeat center center/cover;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 80px 60px; 
            color: white;
            text-align: center;
            min-height: 100vh;
            position: relative;
        }

        .logo-container {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            overflow: hidden;
            margin-bottom: 35px;
            border: 6px solid rgba(255, 255, 255, 0.25);
            background: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .logo-container img { width: 100%; height: 100%; object-fit: cover; }
        .left-title { font-size: 32px; font-weight: 500; margin-bottom: 12px; letter-spacing: -0.5px; }
        .left-subtitle { font-size: 18px; opacity: 0.95; margin-bottom: 45px; font-weight: 400; }

        .feature {
            display: flex;
            align-items: center;
            padding: 15px 18px;
            background: rgba(255, 255, 255, 0.12);
            border-radius: 6px;
            font-size: 15px;
            margin-bottom: 14px;
            width: 100%;
            max-width: 360px;
            text-align: left;
            line-height: 1.5;
        }
        .feature i { margin-right: 14px; font-size: 1.15rem; color: rgba(255, 255, 255, 0.85); flex-shrink: 0; }

        .instructions-box {
            background: rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 25px 20px;
            margin-top: 30px;
            max-width: 380px;
        }

        .instructions-title {
            font-size: 16px;
            font-weight: 600;
            color: white;
            margin-bottom: 16px;
            text-align: left;
        }

        .instruction-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 14px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.5;
        }

        .instruction-item:last-child {
            margin-bottom: 0;
        }

        .instruction-item i {
            font-size: 1rem;
            color: #86efac;
            margin-right: 12px;
            margin-top: 2px;
            flex-shrink: 0;
        }

        /* --- Right Login Section with White Background --- */
        .right-section { 
            flex: 1; 
            padding: 0;
            display: flex; 
            flex-direction: column; 
            justify-content: center;
            align-items: center;
            background: white; /* Changed to solid white */
            min-height: 100vh;
        }
        
        .login-box {
            width: 100%;
            max-width: 450px;
            background: transparent;
            padding: 45px 40px;
        }

        .login-title { font-size: 24px; font-weight: 500; color: #1f2937; text-align: center; margin-bottom: 10px; letter-spacing: -0.3px; }
        .login-subtitle { color: black; font-size: 14px; text-align: center; margin-bottom: 32px; font-weight: 200; }

        .form-group { margin-bottom: 20px; position: relative; }
        .form-label { display: block; font-size: 15px; font-weight: 500; color: #1f2937; margin-bottom: 8px; margin-left: 0; }

        .required { color: #ef4444; font-weight: 700; }

        .form-select, .form-input {
            width: 100%;
            padding: 8px 20px;
            border: 0.5px solid #CACACA;
            border-radius: 8px;
            font-size: 15px;
            height: 50px;
            outline: none;
            transition: all 0.25s ease;
            background-color: #ffffff;
            appearance: none;
            color: #374151;
        }

        .form-input::placeholder { color: #aeb7c1; }

        .form-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%233C96E1' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 20px center;
            background-size: 20px;
            cursor: pointer;
            padding-right: 10px;
        }

        .form-select:focus, .form-input:focus {
            border-color: #3a94df;
            background-color: #fff;
            box-shadow: 0 0 0 2px rgba(58, 148, 223, 0.05);
        }

        .btn-login {
            width: 100%;
            height: 52px;
            background: linear-gradient(135deg, #3a94df 0%, #2e7cc0 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-top: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: 0.2px;
        }

        .btn-login:disabled { background: #d1d5db; cursor: not-allowed; }
        .btn-login:hover:not(:disabled) { background: linear-gradient(135deg, #2e7cc0 0%, #1f5a9f 100%); box-shadow: 0 6px 16px rgba(58, 148, 223, 0.28); transform: translateY(-1px); }

        .support-text { text-align: center; margin-top: 36px; color: black; font-size: 16px; font-weight: 400; }

        /* --- BIGGER MODAL DESIGN --- */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(8px);
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .modal.show { display: flex; opacity: 1; }

        .modal-content {
            background: white;
            padding: 40px 32px; 
            border-radius: 16px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            transform: scale(0.85);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(229, 231, 235, 0.8);
        }

        .modal.show .modal-content { transform: scale(1); }

        .modal-icon {
            font-size: 80px;
            margin-bottom: 24px;
            min-height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-title { font-size: 22px; font-weight: 600; margin-bottom: 12px; color: #1f2937; letter-spacing: -0.3px; }
        .modal-message { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 0; font-weight: 400; }

        .pulsing-circle {
            display: inline-block;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            opacity: 0.8;
            animation: pulse-animation 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
            margin: 0 auto;
        }

        .pulsing-blue {
            background-color: #38BDF8;
        }

        @keyframes pulse-animation {
            0%, 100% {
                transform: scale(1);
                opacity: 0.8;
            }
            50% {
                transform: scale(1.3);
                opacity: 0.4;
            }
        }

        .modal-btn {
            padding: 12px 32px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.25s ease;
            letter-spacing: 0.2px;
            margin-top: 20px;
        }

        .btn-close { background: #f3f4f6; color: #4b5563; }
        .btn-close:hover { background: #e5e7eb; }

        @media (max-width: 1024px) { 
            .left-section { padding: 60px 40px; }
            .login-box { max-width: 340px; padding: 35px 30px; }
            .left-title { font-size: 28px; }
        }

        @media (max-width: 768px) { 
            .left-section { display: none; }
            .right-section { padding: 20px; max-width: 100%; }
            .login-box { max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="left-section">
            <div class="logo-container">
                <img src="asssets/images/Luz.jpg" alt="Logo">
            </div>
            <h1 class="left-title">Barangay Luz, Cebu City</h1>
            <p class="left-subtitle">Healthcare Management System</p>
            
            <div class="instructions-box">
                <h3 class="instructions-title">Before You Login</h3>
                <div class="instruction-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Ensure your account credentials are active</span>
                </div>
                <div class="instruction-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Select your appropriate role (Admin)</span>
                </div>
                <div class="instruction-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Check your internet connection is stable</span>
                </div>
                <div class="instruction-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Keep your password confidential</span>
                </div>
                <div class="instruction-item">
                    <i class="fas fa-check-circle"></i>
                    <span>Contact IT support if access is denied</span>
                </div>
            </div>
        </div>

        <div class="right-section">
            <div class="login-box">
                <h2 class="login-title">Administrator Access</h2>
                <p class="login-subtitle">Please sign in to your workstation</p>

                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label">System Role <span class="required">*</span></label>
                        <select id="role" name="role" class="form-select" required>
                            <option value="">Select Role Type</option>
                            <option value="admin">Super Admin</option>
                            <option value="staff">Admin</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Username <span class="required">*</span></label>
                        <input type="text" name="username" class="form-input" placeholder="Enter Username" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password <span class="required">*</span></label>
                        <div style="position: relative;">
                            <input type="password" id="staffPassword" name="password" class="form-input" placeholder="Enter Password" required>
                            <button type="button" onclick="toggleStaffPassword()" style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); background: none; border: none; color: #3C96E1; cursor: pointer;">
                                <i id="staffEyeIcon" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login" id="loginButton" disabled>
                        <span id="roleText">Select Role Type</span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M21.7372 10.6865L5.98724 1.69679C5.72157 1.54724 5.41664 1.48218 5.11307 1.5103C4.8095 1.53841 4.52171 1.65836 4.28803 1.85417C4.05435 2.04997 3.88588 2.31233 3.80506 2.60629C3.72425 2.90026 3.73493 3.21187 3.83568 3.49961L6.70724 11.999L3.83568 20.4993C3.75576 20.7255 3.73126 20.9675 3.76424 21.2052C3.79721 21.4428 3.8867 21.669 4.02519 21.8649C4.16367 22.0608 4.34712 22.2206 4.56014 22.3309C4.77316 22.4413 5.00953 22.4989 5.24943 22.499C5.51005 22.4984 5.76613 22.4306 5.99286 22.3021L21.7354 13.2974C21.9676 13.1673 22.161 12.9778 22.2957 12.7483C22.4305 12.5188 22.5018 12.2576 22.5023 11.9914C22.5028 11.7253 22.4324 11.4638 22.2985 11.2338C22.1645 11.0038 21.9718 10.8136 21.74 10.6827L21.7372 10.6865ZM5.24943 20.999C5.24983 20.9952 5.24983 20.9915 5.24943 20.9877L8.03755 12.749H13.4994C13.6983 12.749 13.8891 12.67 14.0298 12.5293C14.1704 12.3887 14.2494 12.1979 14.2494 11.999C14.2494 11.8001 14.1704 11.6093 14.0298 11.4687C13.8891 11.328 13.6983 11.249 13.4994 11.249H8.03755L5.25505 3.01398C5.25413 3.00867 5.25222 3.00359 5.24943 2.99898L20.9994 11.983L5.24943 20.999Z" fill="white"/>
</svg>

                    </button>
                </form>
                <div class="support-text">Production Version • Version 1.0</div>
            </div>
        </div>
    </div>

    <div id="validationModal" class="modal">
        <div class="modal-content">
            <div id="modalLoading">
                <div class="modal-icon">
                    <span class="pulsing-circle pulsing-blue"></span>
                </div>
                <h3 class="modal-title">Verifying Identity</h3>
                <p class="modal-message">Connecting to the secure health server...</p>
            </div>

            <div id="modalResponse" style="display:none;">
                <div id="iconContainer" class="modal-icon"></div>
                <h3 id="resTitle" class="modal-title"></h3>
                <p id="resMsg" class="modal-message"></p>
                <button id="modalActionBtn" class="modal-btn" onclick="closeModal()"></button>
            </div>
        </div>
    </div>

    <script>
        const roleSelect = document.getElementById('role');
        const loginButton = document.getElementById('loginButton');
        const roleText = document.getElementById('roleText');
        const modal = document.getElementById('validationModal');

        // Toggle button state and text
        roleSelect.addEventListener('change', function() {
            if (this.value) {
                roleText.textContent = 'Login as ' + (this.value === 'admin' ? 'Super Admin' : 'Admin');
                loginButton.disabled = false;
            } else {
                roleText.textContent = 'Select Role Type';
                loginButton.disabled = true;
            }
        });

        // AJAX Handle
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show Modal and animate in
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            
            // Reset to loading state
            document.getElementById('modalLoading').style.display = 'block';
            document.getElementById('modalResponse').style.display = 'none';

            fetch('', {
                method: 'POST',
                body: new FormData(this),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => {
                if (!res.ok) throw new Error('Network error');
                return res.json();
            })
            .then(data => {
                setTimeout(() => {
                    document.getElementById('modalLoading').style.display = 'none';
                    document.getElementById('modalResponse').style.display = 'block';
                    
                    const iconBox = document.getElementById('iconContainer');
                    const actionBtn = document.getElementById('modalActionBtn');

                    if(data.success) {
                        iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" style="width: 80px; height: 80px; stroke: #38BDF8;" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>';
                        document.getElementById('resTitle').textContent = "You've successfully signed in.";
                        document.getElementById('resMsg').textContent = 'Taking you to your dashboard…';
                        actionBtn.style.display = 'none';
                        
                        setTimeout(() => {
                            window.location.href = data.role + '/dashboard.php';
                        }, 1500);
                    } else {
                        iconBox.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" style="width: 80px; height: 80px; stroke: #FACC15;" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-2.5L13.73 4c-.77-.83-1.96-.83-2.73 0L3.34 16.5c-.77.83.19 2.5 1.73 2.5z" /></svg>';
                        document.getElementById('resTitle').textContent = 'Login Failed';
                        document.getElementById('resMsg').textContent = data.message;
                        actionBtn.textContent = 'Try Again';
                        actionBtn.className = 'modal-btn btn-close';
                    }
                }, 800);
            })
            .catch(err => {
                document.getElementById('modalLoading').style.display = 'none';
                document.getElementById('modalResponse').style.display = 'block';
                document.getElementById('iconContainer').innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" style="width: 80px; height: 80px; stroke: #FACC15;" fill="none" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-2.5L13.73 4c-.77-.83-1.96-.83-2.73 0L3.34 16.5c-.77.83.19 2.5 1.73 2.5z" /></svg>';
                document.getElementById('resTitle').textContent = 'Connection Error';
                document.getElementById('resMsg').textContent = 'Unable to connect to the server. Please check your internet.';
                document.getElementById('modalActionBtn').textContent = 'Close';
                document.getElementById('modalActionBtn').className = 'modal-btn btn-close';
            });
        });

        function closeModal() {
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 400);
        }

        function toggleStaffPassword() {
            const input = document.getElementById("staffPassword");
            const icon = document.getElementById("staffEyeIcon");

            if (input.type === "password") {
                input.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                input.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>
