<?php
// user/upload-profile.php - Mandatory profile picture with face scanning for new resident users
ob_start();

require_once __DIR__ . '/../includes/auth.php';

redirectIfNotLoggedIn();
if (!isUser()) {
    header('Location: /community-health-tracker/');
    exit();
}

$user_id = $_SESSION['user']['id'];
$full_name = $_SESSION['user']['full_name'] ?? 'User';
$profile_picture = null;

// Get user's profile picture if exists
$profile_dir = __DIR__ . '/../uploads/profiles/';
if (!file_exists($profile_dir)) {
    mkdir($profile_dir, 0777, true);
}

$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
foreach ($allowed_extensions as $ext) {
    $potential_file = $profile_dir . 'profile_' . $user_id . '.' . $ext;
    if (file_exists($potential_file)) {
        $profile_picture = '/community-health-tracker/uploads/profiles/profile_' . $user_id . '.' . $ext;
        break;
    }
}

// Set flag to prevent profile check redirect loop
$_SESSION['uploading_profile'] = true;

// Include the dashboard to render in background
ob_start();
include __DIR__ . '/dashboard.php';
$dashboard_content = ob_get_clean();

// Remove the uploading flag
unset($_SESSION['uploading_profile']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Profile - Photo Capture Required</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Face Detection with Landmarks -->
    <script async src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@3"></script>
    <script async src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script>
    <script async src="https://cdn.jsdelivr.net/npm/@tensorflow-models/face-landmarks-detection"></script>
    <style>
    /* Reset and base styles */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    body, html {
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    
    /* Dashboard background - visible and non-interactive */
    #dashboardBackground {
        position: fixed;
        inset: 0;
        filter: none;
        pointer-events: none;
        user-select: none;
        overflow: auto;
        z-index: 1;
    }
    
    /* Modal overlay container - clear and interactive */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(74, 144, 226, 0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        pointer-events: auto;
        filter: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    
    /* Camera Container */
    .camera-container {
        width: 100%;
        position: relative;
        margin-bottom: 1rem;
        border-radius: 20px;
        overflow: hidden;
        background: #000;
    }
    
    .camera-container video {
        width: 100%;
        height: auto;
        display: block;
    }
    
    /* Status Message */
    .status-message {
        text-align: center;
        padding: 1.5rem;
        color: rgba(74, 144, 226, 0.8);
        font-size: 0.95rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1.5rem;
        background: rgba(76, 175, 80, 0.12);
        border-radius: 15px;
        border: 1px solid rgba(76, 175, 80, 0.2);
        backdrop-filter: blur(8px);
        margin-bottom: 1.25rem;
    }
    
    .status-message i {
        font-size: 2.5rem;
        color: rgba(76, 175, 80, 0.75);
        min-width: 2.5rem;
    }
    
    .status-message span {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
    }
    
    .status-message span i {
        font-size: 1.5rem;
    }
    
    /* Instructions Panel */
    .instructions-panel {
        background: rgba(74, 144, 226, 0.06);
        border: 1px solid rgba(74, 144, 226, 0.15);
        border-radius: 15px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .instructions-title {
        font-weight: 600;
        color: rgba(55, 71, 79, 0.9);
        font-size: 0.95rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .instructions-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .instructions-list li {
        font-size: 0.85rem;
        color: rgba(107, 114, 128, 0.8);
        padding: 0.5rem 0;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .instructions-list i {
        color: rgba(74, 144, 226, 0.7);
        width: 20px;
        text-align: center;
    }
    
    /* Face Status Indicators */
    .face-status-indicators {
        position: absolute;
        bottom: 1rem;
        left: 1rem;
        right: 1rem;
        background: rgba(0, 0, 0, 0.7);
        border-radius: 10px;
        padding: 0.75rem;
        backdrop-filter: blur(4px);
    }
    
    .status-item {
        display: flex;
        justify-content: space-between;
        font-size: 0.8rem;
        color: white;
        margin-bottom: 0.5rem;
    }
    
    .status-item:last-child {
        margin-bottom: 0;
    }
    
    .status-label {
        font-weight: 500;
        opacity: 0.8;
    }
    
    .status-value {
        font-weight: 600;
        color: #4CAF50;
    }
    
    .status-value.warning {
        color: #FFA726;
    }
    
    .status-value.error {
        color: #EF5350;
    }
    
    .modal-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            width: 100%;
            max-width: 520px;
            margin: 1rem;
            overflow: hidden;
            position: relative;
            z-index: 10000;
            filter: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
            pointer-events: auto;
        }
        
        /* Ensure all modal content is crystal clear */
        .modal-card *,
        .modal-overlay * {
            filter: none !important;
            backdrop-filter: none !important;
            -webkit-backdrop-filter: none !important;
        }
        
        .modal-header {
            background: rgba(74, 144, 226, 0.08);
            padding: 2rem 1.5rem;
            text-align: center;
            color: rgba(74, 144, 226, 0.9);
            border-bottom: 1px solid rgba(74, 144, 226, 0.1);
        }
        
        .modal-header h2 {
            font-size: 1.35rem;
            font-weight: 600;
            margin: 0 0 0.4rem 0;
            color: rgba(55, 71, 79, 0.9);
        }
        
        .modal-header p {
            font-size: 0.95rem;
            opacity: 0.6;
            margin: 0;
            color: rgba(107, 114, 128, 0.8);
        }
        
        .modal-body {
            padding: 2rem 1.75rem;
        }
        
        .profile-preview-small {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(74, 144, 226, 0.15);
            margin: 0 auto 1.5rem;
            display: block;
            box-shadow: 0 2px 8px rgba(74, 144, 226, 0.1);
        }

        #capturePreview {
            margin: 1.25rem 0 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: rgba(55, 71, 79, 0.7);
            margin-bottom: 0.5rem;
        }
        
        .file-input-wrapper {
            position: relative;
            display: block;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            cursor: pointer;
        }
        
        .file-input-label {
            display: block;
            padding: 1.25rem 1rem;
            background: rgba(74, 144, 226, 0.04);
            border: 1.5px dashed rgba(74, 144, 226, 0.3);
            border-radius: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: rgba(74, 144, 226, 0.75);
            font-size: 0.95rem;
            font-weight: 400;
        }
        
        .file-input-wrapper:hover .file-input-label {
            background: rgba(74, 144, 226, 0.08);
            border-color: rgba(74, 144, 226, 0.5);
        }
        
        .file-input-label i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .form-hint {
            font-size: 0.7rem;
            color: rgba(107, 114, 128, 0.6);
            margin-top: 0.5rem;
        }
        
        .error-message {
            padding: 0.65rem;
            background: rgba(254, 226, 226, 0.5);
            border: 1px solid rgba(254, 202, 202, 0.4);
            border-radius: 0.4rem;
            color: rgba(220, 38, 38, 0.8);
            font-size: 0.75rem;
            margin-bottom: 0.875rem;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        .success-message {
            padding: 0.65rem;
            background: rgba(220, 252, 231, 0.5);
            border: 1px solid rgba(187, 247, 208, 0.4);
            border-radius: 0.4rem;
            color: rgba(22, 163, 74, 0.8);
            font-size: 0.75rem;
            margin-bottom: 0.875rem;
            display: none;
        }
        
        .success-message.show {
            display: block;
        }
        
        .modal-footer {
            padding: 1.5rem 2rem 2rem 2rem;
            border-top: 1px solid rgba(229, 231, 235, 0.5);
            display: flex;
            gap: 5px;
            justify-content: center;
            width: 100%;
            box-sizing: border-box;
        }
        
        .btn-upload {
            flex: 1;
            width: 100%;
            padding: 0.9rem 1rem;
            background: rgba(74, 144, 226, 0.85);
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        
        .btn-upload:hover {
            background: rgba(74, 144, 226, 0.95);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(74, 144, 226, 0.2);
        }
        
        .btn-upload:active {
            transform: translateY(0);
        }
        
        .btn-upload:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #E0F2FE;
            border-top: 2px solid #4A90E2;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .required-badge {
            display: inline-block;
            background: rgba(219, 234, 254, 0.5);
            color: rgba(74, 144, 226, 0.7);
            font-size: 0.65rem;
            font-weight: 500;
            padding: 0.2rem 0.4rem;
            border-radius: 0.25rem;
            margin-left: 0.5rem;
        }

        .modal-card.preview-mode #captureBtn {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Dashboard Background (Blurred) -->
    <div id="dashboardBackground">
        <?php echo $dashboard_content; ?>
    </div>
    
    <!-- Modal Overlay -->
    <div class="modal-overlay">
        <div class="modal-card">
            <!-- Header -->
            <div class="modal-header">
                <h2>Complete Profile</h2>
                <p>Scan your face to continue</p>
            </div>
            
            <!-- Body -->
            <div class="modal-body">
                <!-- Instructions Panel -->
                <div id="instructionsPanel" class="instructions-panel">
                    <div class="instructions-title">
                        <i class="fas fa-info-circle"></i> Capture Instructions
                    </div>
                    <ul class="instructions-list">
                        <li><i class="fas fa-camera"></i> Keep your face centered and visible</li>
                        <li><i class="fas fa-face-smile"></i> Your entire face must fit in the camera</li>
                        <li><i class="fas fa-minus"></i> Include from forehead to neck</li>
                        <li><i class="fas fa-sun"></i> Ensure good lighting</li>
                    </ul>
                </div>
                
                <!-- Camera Container -->
                <div id="cameraContainer" class="camera-container" style="display: none;">
                    <video id="videoElement" width="100%" height="auto" autoplay playsinline style="border-radius: 20px;"></video>
                    <canvas id="canvasElement" style="display: none;"></canvas>
                </div>
                
                <!-- Status Message -->
                <div id="statusMessage" class="status-message">
                    <i id="statusIcon" class="fas fa-camera"></i>
                    <span id="statusText">Ready to capture your profile photo</span>
                </div>
                
                <!-- Error Message -->
                <div id="errorMessage" class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <span id="errorText"></span>
                </div>
                
                <!-- Success Message -->
                <div id="successMessage" class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <span id="successText"></span>
                </div>
                
                <!-- Captured Photo Preview -->
                <div id="capturePreview" style="display: none; text-align: center;">
                    <img id="capturedImage" src="" alt="Captured Photo" class="profile-preview-small">
                    <p style="color: rgba(22, 163, 74, 0.8); font-size: 0.9rem; margin: 1rem 0 0 0;">Photo captured</p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn-upload" id="startCameraBtn">
                    <i class="fas fa-camera"></i>
                    <span>Start Camera</span>
                </button>
                <button type="button" class="btn-upload" id="captureBtn" style="display: none;">
                    <i class="fas fa-check"></i>
                    <span>Capture Photo</span>
                </button>
                <button type="button" class="btn-upload" id="uploadBtn" style="display: none;">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <span>Upload</span>
                </button>
                <button type="button" class="btn-upload" id="retryBtn" style="display: none;">
                    <i class="fas fa-redo"></i>
                    <span>Retake Photo</span>
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Camera Capture Variables
        const videoElement = document.getElementById('videoElement');
        const canvasElement = document.getElementById('canvasElement');
        const statusMessage = document.getElementById('statusMessage');
        const statusText = document.getElementById('statusText');
        const errorMessage = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');
        const successMessage = document.getElementById('successMessage');
        const successText = document.getElementById('successText');
        const cameraContainer = document.getElementById('cameraContainer');
        const capturePreview = document.getElementById('capturePreview');
        const capturedImage = document.getElementById('capturedImage');
        const instructionsPanel = document.getElementById('instructionsPanel');
        const modalCard = document.querySelector('.modal-card');
        
        const startCameraBtn = document.getElementById('startCameraBtn');
        const captureBtn = document.getElementById('captureBtn');
        const uploadBtn = document.getElementById('uploadBtn');
        const retryBtn = document.getElementById('retryBtn');
        
        let stream = null;
        let capturedImageData = null;
        let faceDetector = null;
        let detectionRunning = false;
        
        // Initialize face detection on page load
        window.addEventListener('load', async function() {
            try {
                // Load BlazeFace model for face detection
                faceDetector = await blazeface.load();
            } catch (error) {
                console.log('Face detection model loaded with fallback mode');
            }
        });
        
        // Start Camera
        startCameraBtn.addEventListener('click', async function() {
            try {
                modalCard.classList.remove('preview-mode');
                updateStatus('Requesting camera access...');
                
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 640, height: 480, facingMode: 'user' }
                });
                
                videoElement.srcObject = stream;
                // Mirror the camera preview
                videoElement.style.transform = 'scaleX(-1)';
                cameraContainer.style.display = 'block';
                instructionsPanel.style.display = 'none';
                startCameraBtn.style.display = 'none';
                statusText.textContent = 'Position your face in the camera';
                detectionRunning = true;
                
                // Start face detection loop
                detectFace();
            } catch (error) {
                showError('Camera access denied. Please allow camera permission.');
                updateStatus('Click "Start Camera" to try again.');
            }
        });
        
        // Detect Face
        async function detectFace() {
            if (!stream || !detectionRunning) return;
            
            try {
                if (faceDetector) {
                    const predictions = await faceDetector.estimateFaces(videoElement, false);
                    
                    if (predictions && predictions.length > 0) {
                        document.getElementById('statusIcon').style.display = 'none';
                        statusText.innerHTML = '<i class="fas fa-check-circle" style="color: rgba(22, 163, 74, 0.9); font-size: 1.5rem; margin-right: 0.25rem;"></i>Face detected! Click "Capture Photo"';
                        statusText.style.color = 'rgba(22, 163, 74, 0.9)';
                        captureBtn.style.display = 'block';
                        captureBtn.style.visibility = 'visible';
                    } else {
                        document.getElementById('statusIcon').style.display = 'block';
                        document.getElementById('statusIcon').className = 'fas fa-camera';
                        document.getElementById('statusIcon').style.color = 'rgba(74, 144, 226, 0.8)';
                        statusText.textContent = 'Position your face in the camera';
                        statusText.style.color = 'rgba(74, 144, 226, 0.8)';
                        captureBtn.style.display = 'none';
                        captureBtn.style.visibility = 'hidden';
                    }
                }
            } catch (err) {
                console.error('Detection error:', err);
            }
            
            if (detectionRunning) {
                requestAnimationFrame(detectFace);
            }
        }
        
        // Capture Photo
        captureBtn.addEventListener('click', function() {
            
            // Draw mirrored video frame to canvas
            const ctx = canvasElement.getContext('2d');
            canvasElement.width = videoElement.videoWidth;
            canvasElement.height = videoElement.videoHeight;
            ctx.save();
            ctx.translate(canvasElement.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(videoElement, 0, 0, canvasElement.width, canvasElement.height);
            ctx.restore();
            
            // Get image data
            capturedImageData = canvasElement.toDataURL('image/jpeg', 0.95);
            capturedImage.src = capturedImageData;
            
            // Stop camera
            detectionRunning = false;
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            // Update UI
            cameraContainer.style.display = 'none';
            captureBtn.style.display = 'none';
            captureBtn.style.visibility = 'hidden';
            capturePreview.style.display = 'block';
            uploadBtn.style.display = 'block';
            retryBtn.style.display = 'block';
            statusText.textContent = 'Photo captured. Click "Upload" to save your profile.';
            modalCard.classList.add('preview-mode');
        });
        
        // Retry Photo
        retryBtn.addEventListener('click', function() {
            capturePreview.style.display = 'none';
            retryBtn.style.display = 'none';
            uploadBtn.style.display = 'none';
            instructionsPanel.style.display = 'block';
            statusText.textContent = 'Starting camera again...';
            modalCard.classList.remove('preview-mode');
            
            // Clear previous stream
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            
            setTimeout(() => {
                startCameraBtn.click();
            }, 500);
        });
        
        // Upload Photo
        uploadBtn.addEventListener('click', async function() {
            if (!capturedImageData) {
                showError('No photo captured. Please take a photo first.');
                return;
            }
            
            uploadBtn.disabled = true;
            statusText.textContent = 'Uploading profile photo...';
            
            try {
                // Convert data URL to blob
                const response = await fetch(capturedImageData);
                const blob = await response.blob();
                
                const formData = new FormData();
                formData.append('profile_image', blob, 'profile_' + Date.now() + '.jpg');
                formData.append('user_id', '<?php echo $user_id; ?>');
                formData.append('user_type', 'user');
                
                const uploadResponse = await fetch('/community-health-tracker/auth/upload_profile.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await uploadResponse.json();
                
                if (result.success) {
                    showSuccess('Profile photo uploaded successfully!');
                    statusText.textContent = 'Redirecting to dashboard...';
                    setTimeout(() => {
                        const redirectUrl = '<?php echo $_SESSION['redirect_after_profile'] ?? '/community-health-tracker/user/dashboard.php'; ?>';
                        window.location.href = redirectUrl;
                    }, 1500);
                } else {
                    showError(result.message || 'Upload failed.');
                    statusText.textContent = 'Upload failed. Click "Retake Photo" to try again.';
                    uploadBtn.disabled = false;
                }
            } catch (error) {
                showError('Upload error: ' + error.message);
                statusText.textContent = 'Upload error. Click "Retake Photo" to try again.';
                uploadBtn.disabled = false;
            }
        });
        
        // Helper Functions
        function updateStatus(message) {
            statusText.textContent = message;
        }
        
        function showError(message) {
            errorText.textContent = message;
            errorMessage.classList.add('show');
            successMessage.classList.remove('show');
        }
        
        function showSuccess(message) {
            successText.textContent = message;
            successMessage.classList.add('show');
            errorMessage.classList.remove('show');
        }
    </script>
</body>
</html>
