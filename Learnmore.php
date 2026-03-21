<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Luz · System Overview</title>
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Poppins font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --warm-blue: #3a7bd5;
            --warm-blue-light: #4a90e2;
            --off-white: #f8fafc;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--off-white);
            line-height: 1.6;
        }
        .section-title {
            position: relative;
            padding-bottom: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background-color: var(--warm-blue);
            border-radius: 2px;
        }
        .info-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f6;
            transition: all 0.2s ease;
        }
        .info-card:hover {
            box-shadow: 0 8px 20px rgba(58, 123, 213, 0.08);
            border-color: #e0e7ff;
        }
        .stat-badge {
            background: #eef2ff;
            color: #1e4a6b;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .simple-list {
            list-style: none;
            padding-left: 0;
        }
        .simple-list li {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }
        .simple-list li i {
            color: var(--warm-blue);
            margin-top: 0.2rem;
        }
        hr {
            border-color: #e2e8f0;
        }
        /* Back button transition effect */
        .back-btn {
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            transform: translateX(-2px);
            background-color: #f1f5f9;
            border-color: #cbd5e1;
        }
    </style>
</head>
<body class="antialiased text-gray-700">

    <main class="max-w-4xl mx-auto px-5 py-12 md:py-16 relative">
        
        <!-- Back Button - positioned at top left corner -->
        <div class="absolute left-5 top-6 md:left-8 md:top-8 z-10">
            <a href="landing.html" id="backButton" class="back-btn inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white border border-gray-200 shadow-sm text-gray-700 text-sm font-medium hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-[#3a7bd5] focus:ring-offset-2">
                <i class="fas fa-arrow-left text-[#3a7bd5] text-sm"></i>
                <span>Back</span>
            </a>
        </div>

        <!-- Header / Title (clean, text-focused) with slight margin-top to avoid overlap with back button on mobile -->
        <div class="text-center mb-10 pt-2 md:pt-0">
            <h1 class="text-3xl md:text-4xl font-semibold text-[#1e4a6b] tracking-tight">Barangay Luz</h1>
            <p class="text-xl text-[#2b6c9e] mt-1 font-medium">Monitoring & Tracking System</p>
            <div class="w-20 h-0.5 bg-[#3a7bd5] mx-auto mt-4 rounded-full"></div>
        </div>

        <!-- Lead description / Learn more -->
        <div class="bg-white rounded-2xl p-6 md:p-8 mb-10 shadow-sm border border-gray-100">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-2xl text-[#3a7bd5] mt-1"></i>
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Learn more — the Barangay Luz system</h2>
                    <p class="text-gray-600 leading-relaxed">A straightforward digital platform built to organise resident records, track health updates, and log incidents. All information is centralised to help the barangay respond faster, plan better, and serve the community with transparency. Authorised staff can update records in real time, while residents benefit from quicker document processing and better-coordinated services.</p>
                </div>
            </div>
        </div>

        <!-- Simple stats row (clean text emphasis) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <div class="text-center p-3">
                <div class="text-2xl font-bold text-[#3a7bd5]">3,200+</div>
                <div class="text-sm text-gray-500">residents profiled</div>
            </div>
            <div class="text-center p-3">
                <div class="text-2xl font-bold text-[#3a7bd5]">98%</div>
                <div class="text-sm text-gray-500">incident coverage</div>
            </div>
            <div class="text-center p-3">
                <div class="text-2xl font-bold text-[#3a7bd5]">500+</div>
                <div class="text-sm text-gray-500">monthly transactions</div>
            </div>
            <div class="text-center p-3">
                <div class="text-2xl font-bold text-[#3a7bd5]">24/7</div>
                <div class="text-sm text-gray-500">system availability</div>
            </div>
        </div>

        <!-- Core modules (3 cards with descriptive text) -->
        <div class="mb-12">
            <h2 class="section-title text-2xl font-semibold text-gray-800">Core modules & capabilities</h2>
            <div class="grid md:grid-cols-3 gap-6 mt-5">
                <div class="info-card">
                    <i class="fas fa-users text-2xl text-[#3a7bd5] mb-3 block"></i>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Resident profiles</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Complete family profiles including senior citizen / PWD tags, vaccination history, and contact details. Authorised staff can update records in real time. Approximately 3,200 residents are currently mapped within the system, ensuring accurate household records.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-exclamation-triangle text-2xl text-[#3a7bd5] mb-3 block"></i>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Incident tracking</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Log accidents, fires, or any public safety concern. The system keeps a timestamped log so responders can review and follow up. Helpful for blotter reports and coordination. Integrated with barangay tanod alerts for faster emergency response.</p>
                </div>
                <div class="info-card">
                    <i class="fas fa-heartbeat text-2xl text-[#3a7bd5] mb-3 block"></i>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Health & development</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">Track immunisation, nutrition programs, and 4Ps beneficiaries. Health center staff generate simple reports for monthly updates. Over 1,800 health records maintained, supporting targeted medical missions and community wellness programs.</p>
                </div>
            </div>
        </div>

        <!-- Simple workflow + Why it matters (two columns text) -->
        <div class="grid md:grid-cols-2 gap-8 mb-12">
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-chalkboard-user text-[#3a7bd5]"></i> Simple workflow</h3>
                <ul class="simple-list space-y-3">
                    <li><i class="fas fa-circle text-xs mt-1.5"></i> <span>Staff encode resident / incident data using a secure form with validation.</span></li>
                    <li><i class="fas fa-circle text-xs mt-1.5"></i> <span>Information is stored in a central database (cloud / local) with encryption.</span></li>
                    <li><i class="fas fa-circle text-xs mt-1.5"></i> <span>Officials view summaries and generate reports with one click for planning.</span></li>
                    <li><i class="fas fa-circle text-xs mt-1.5"></i> <span>Automated alerts (SMS / Viber) for meetings, emergencies, or health programs.</span></li>
                </ul>
            </div>
            <div class="bg-white rounded-xl p-6 border border-gray-100 shadow-sm">
                <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2"><i class="fas fa-chart-simple text-[#3a7bd5]"></i> Why it matters</h3>
                <ul class="simple-list space-y-2">
                    <li><i class="fas fa-check-circle text-green-600"></i> <span>Transparency – residents can request service summaries and track requests.</span></li>
                    <li><i class="fas fa-check-circle text-green-600"></i> <span>Better planning – identify zones needing medical missions or infrastructure.</span></li>
                    <li><i class="fas fa-check-circle text-green-600"></i> <span>Time savings – monthly reports generated in minutes instead of days.</span></li>
                    <li><i class="fas fa-check-circle text-green-600"></i> <span>Accountability – every entry is logged with timestamp and user ID.</span></li>
                    <li><i class="fas fa-check-circle text-green-600"></i> <span>Inclusive – special tags for solo parents, PWDs, senior citizens, and 4Ps.</span></li>
                </ul>
            </div>
        </div>

        <!-- Who uses the system (text grid) -->
        <div class="mb-12">
            <h2 class="section-title text-2xl font-semibold text-gray-800">👥 Who uses the system</h2>
            <div class="grid sm:grid-cols-2 gap-4 mt-5">
                <div class="bg-gray-50 p-4 rounded-xl"><span class="font-semibold text-[#1e4a6b]">Barangay Captain & Council</span> — overview dashboards, report generation, decision support for programs.</div>
                <div class="bg-gray-50 p-4 rounded-xl"><span class="font-semibold text-[#1e4a6b]">Secretary & Treasurers</span> — record encoding, certificate issuance, data verification, resident inquiries.</div>
                <div class="bg-gray-50 p-4 rounded-xl"><span class="font-semibold text-[#1e4a6b]">Tanod / Safety Officers</span> — incident logging, alert monitoring, blotter management, emergency coordination.</div>
                <div class="bg-gray-50 p-4 rounded-xl"><span class="font-semibold text-[#1e4a6b]">Health workers (BHW)</span> — nutrition tracking, immunization schedules, health records, home visit logs.</div>
            </div>
        </div>

        <!-- National integrations & Data privacy (two cards) -->
        <div class="grid md:grid-cols-2 gap-6 mb-12">
            <div class="info-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-link text-[#3a7bd5]"></i> National integrations</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>• PhilSys / National ID verification for faster resident validation</li>
                    <li>• 4Ps (Pantawid Pamilya) beneficiary tracking and updates</li>
                    <li>• Department of Health immunization registry synchronization</li>
                    <li>• PNP blotter system (partial sync for incident reports)</li>
                    <li>• DSWD social welfare programs integration for aid distribution</li>
                </ul>
            </div>
            <div class="info-card">
                <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2"><i class="fas fa-lock text-[#3a7bd5]"></i> Data privacy & security</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    <li>✓ End-to-end encryption for all sensitive personal data</li>
                    <li>✓ Role-based access control – staff only see what they need</li>
                    <li>✓ Regular security audits conducted by DICT and local IT</li>
                    <li>✓ Automated backup every 6 hours to prevent data loss</li>
                    <li>✓ Fully compliant with Data Privacy Act of 2012 (RA 10173)</li>
                </ul>
            </div>
        </div>
        
        <!-- Additional subtle footer note (optional) -->
        <div class="text-center text-xs text-gray-400 pt-6 border-t border-gray-200 mt-4">
            <i class="fas fa-shield-alt mr-1"></i> Barangay Luz Information System · Secure & Transparent Governance
        </div>
    </main>

    <!-- JavaScript for back navigation: uses history.back() as fallback, but also supports direct landing.html if available -->
    <script>
        (function() {
            const backBtn = document.getElementById('backButton');
            if (backBtn) {
                backBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    // Attempt to go back in browser history first (if previous page was landing)
                    // If history length > 1, we go back; otherwise we redirect to 'landing.html' (relative path)
                    // This ensures a smooth user experience even when opened directly.
                    if (window.history.length > 1) {
                        window.history.back();
                    } else {
                        // If no history, navigate to landing page (can be index or landing.html)
                        // Using a try-catch safe approach.
                        window.location.href = 'landing.html';
                    }
                });
            }
        })();
    </script>
</body>
</html>