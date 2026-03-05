</main>
    
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Page</title>
    <style>
        html, body {
            height: 100%;
        }
        body {
            display: flex;
            flex-direction: column;
        }
        main {
            flex: 1 0 auto;
        }
        footer {
            flex-shrink: 0;
        }
        
        /* Social Media Icons Styling */
        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: #5b8fd9;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-icon:hover {
            transform: scale(1.1);
            background-color: #4a7acc;
        }
        
        .facebook-icon:hover {
            background-color: #1877f2;
        }
        
        .twitter-icon:hover {
            background-color: #1da1f2;
        }
    </style>
</head>
<body>
    <main>
        <!-- Your page content goes here -->
    </main>

    <footer class="bg-gray-900 text-gray-100 py-12">
        <div class="container mx-auto px-6">
            <div class="flex flex-col items-center">
                <div class="w-full max-w-6xl">
                    <!-- Main footer content -->
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-semibold tracking-tight mb-4">CHM Tracking System</h3>
                        <p class="text-gray-300 max-w-2xl mx-auto leading-relaxed">
                            Comprehensive tracking solution for Batch 2025-2026. Streamlining operations and enhancing productivity.
                        </p>
                    </div>

                    <!-- Social Media Icons -->
                    <div class="flex justify-center gap-6 mb-8">
                        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="social-icon facebook-icon" title="Facebook">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="social-icon twitter-icon" title="Twitter">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 002.856-3.515 10.009 10.009 0 01-2.8.856 4.958 4.958 0 002.165-2.724c-.951.564-2.005.974-3.127 1.195a4.948 4.948 0 00-8.506 4.513A14.025 14.025 0 011.671 3.149a4.93 4.93 0 001.523 6.574 4.903 4.903 0 01-2.243-.616c-.053 2.281 1.585 4.405 3.997 4.882a4.935 4.935 0 01-2.212.085 4.963 4.963 0 004.631 3.438A9.88 9.88 0 010 19.539a13.989 13.989 0 007.557 2.226c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                        </a>
                    </div>
                    
                    <!-- Bottom section with copyright and version -->
                    <div class="border-t border-gray-700 pt-8 flex flex-col md:flex-row justify-between items-center">
                        <!-- Copyright (left side) -->
                        <p class="text-gray-400 text-sm md:text-base order-2 md:order-1 mt-4 md:mt-0">
                            &copy; <?= date('Y') ?> CHM Tracking System / Cabaloquimiralabmanja / Batch 2025-2026. All rights reserved.
                        </p>
                        
                        <!-- Version (right side) -->
                        <span class="text-gray-500 text-xs order-1 md:order-2">
                            Version 1.0
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
</body>
</html>