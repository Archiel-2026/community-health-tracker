<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Luz | Learn More</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --warm-blue: #3a7bd5;
            --warm-blue-deep: #1e4a6b;
            --ink: #25364a;
            --muted: #5f7288;
            --panel: #ffffff;
            --line: rgba(58, 123, 213, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            color: var(--ink);
            background: #eef4fb;
        }

        .split-layout {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr;
        }

        .visual-panel {
            position: relative;
            overflow: hidden;
            background:
                linear-gradient(135deg, rgba(30, 74, 107, 0.88), rgba(58, 123, 213, 0.78)),
                url('./asssets/images/brgyluz.jpg') center/cover no-repeat;
            color: #ffffff;
        }

        .visual-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at top left, rgba(255, 255, 255, 0.22), transparent 42%),
                linear-gradient(180deg, rgba(255, 255, 255, 0.06), transparent 55%);
        }

        .content-panel {
            background: var(--panel);
            position: relative;
        }

        .section-label {
            letter-spacing: 0.18em;
            text-transform: uppercase;
            font-size: 0.72rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.82);
        }

        .accent-line {
            width: 72px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, #ffffff, rgba(255, 255, 255, 0.35));
        }

        .info-card {
            border: 1px solid var(--line);
            border-radius: 24px;
            background: #ffffff;
            box-shadow: 0 18px 40px rgba(31, 71, 113, 0.08);
        }

        .mini-card {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: linear-gradient(180deg, #ffffff, #f8fbff);
        }

        .right-title {
            position: relative;
            padding-bottom: 0.9rem;
        }

        .right-title::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 58px;
            height: 4px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--warm-blue), rgba(58, 123, 213, 0.2));
        }

        .back-btn {
            backdrop-filter: blur(10px);
            transition: transform 0.2s ease, background-color 0.2s ease;
        }

        .back-btn:hover {
            transform: translateX(-2px);
            background-color: rgba(255, 255, 255, 0.2);
        }

        .flow-item {
            position: relative;
            padding-left: 1.2rem;
        }

        .flow-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.55rem;
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: var(--warm-blue);
        }

        @media (min-width: 1024px) {
            .split-layout {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            }

            .visual-panel,
            .content-panel {
                min-height: 100vh;
            }

            .content-scroll {
                max-height: 100vh;
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <main class="split-layout">
        <section class="visual-panel">
            <div class="relative z-10 flex min-h-screen flex-col px-6 py-6 sm:px-10 lg:px-14 lg:py-10">
                <div>
                    <a href="index.php" id="backButton" class="back-btn inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-4 py-2 text-sm font-medium text-white">
                        <i class="fas fa-arrow-left text-sm"></i>
                        <span data-i18n="back">Back</span>
                    </a>
                </div>

                <div class="my-auto max-w-xl py-12">
                    <p class="section-label" data-i18n="heroLabel">Barangay Luz Health Monitoring and Tracking System</p>
                    <h1 class="mt-4 text-4xl font-semibold leading-tight sm:text-5xl">
                        <span data-i18n="heroTitle">Community information presented in a clearer and more focused layout.</span>
                    </h1>
                    <div class="accent-line mt-6"></div>
                    <p class="mt-6 max-w-lg text-base leading-8 text-white/88 sm:text-lg" data-i18n="heroDescription">
                        This page is dedicated to public information only. It explains what the platform does, who it supports, and how it helps the barangay manage health, records, and service coordination.
                    </p>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-3xl border border-white/16 bg-white/10 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/70" data-i18n="purposeTitle">Purpose</p>
                            <p class="mt-3 text-sm leading-7 text-white/88" data-i18n="purposeDescription">
                                Organize health-related records and community updates in one reliable system.
                            </p>
                        </div>
                        <div class="rounded-3xl border border-white/16 bg-white/10 p-5">
                            <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/70" data-i18n="focusTitle">Focus</p>
                            <p class="mt-3 text-sm leading-7 text-white/88" data-i18n="focusDescription">
                                Fast access to accurate information for barangay staff, health workers, and residents.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="max-w-xl rounded-[28px] border border-white/14 bg-white/10 p-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.18em] text-white/70" data-i18n="supportsTitle">What this system supports</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/88" data-i18n="supportItem1">Resident profiles and household information</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/88" data-i18n="supportItem2">Health monitoring and follow-up records</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/88" data-i18n="supportItem3">Incident documentation and response support</div>
                        <div class="rounded-2xl bg-white/10 px-4 py-3 text-sm text-white/88" data-i18n="supportItem4">Program planning and service coordination</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="content-panel">
            <div class="content-scroll px-6 py-10 sm:px-10 lg:px-14 lg:py-14">
                <div class="mx-auto max-w-3xl space-y-8">
                    <div class="info-card p-7 sm:p-9">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-[#3a7bd5]" data-i18n="overviewLabel">System overview</p>
                        <h2 class="right-title mt-3 text-3xl font-semibold text-[var(--warm-blue-deep)]" data-i18n="overviewTitle">Informative display for public viewing</h2>
                        <p class="mt-6 text-[15px] leading-8 text-[var(--muted)]" data-i18n="overviewParagraph1">
                            The Barangay Luz Health Monitoring and Tracking System is a digital platform that keeps important community information organized and accessible. It helps staff manage records more efficiently while giving residents a clearer understanding of how local health and barangay services are supported.
                        </p>
                        <p class="mt-4 text-[15px] leading-8 text-[var(--muted)]" data-i18n="overviewParagraph2">
                            This display focuses on information presentation only. It avoids clutter and places readable content on a white panel so the details remain visible against the page's background image treatment.
                        </p>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <article class="mini-card p-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#3a7bd5]">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-[var(--warm-blue-deep)]" data-i18n="card1Title">Resident information</h3>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-[var(--muted)]" data-i18n="card1Description">
                                Stores household and resident details that can support verification, profiling, and community-level planning.
                            </p>
                        </article>

                        <article class="mini-card p-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#3a7bd5]">
                                    <i class="fas fa-heart-pulse"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-[var(--warm-blue-deep)]" data-i18n="card2Title">Health monitoring</h3>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-[var(--muted)]" data-i18n="card2Description">
                                Supports follow-up on consultations, health programs, and other records needed by local health personnel.
                            </p>
                        </article>

                        <article class="mini-card p-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#3a7bd5]">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-[var(--warm-blue-deep)]" data-i18n="card3Title">Incident documentation</h3>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-[var(--muted)]" data-i18n="card3Description">
                                Records safety-related events in a structured format for easier review, coordination, and reporting.
                            </p>
                        </article>

                        <article class="mini-card p-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-[#3a7bd5]">
                                    <i class="fas fa-chart-column"></i>
                                </div>
                                <h3 class="text-lg font-semibold text-[var(--warm-blue-deep)]" data-i18n="card4Title">Decision support</h3>
                            </div>
                            <p class="mt-4 text-sm leading-7 text-[var(--muted)]" data-i18n="card4Description">
                                Helps officials review collected information and prepare service responses based on actual community needs.
                            </p>
                        </article>
                    </div>

                    <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="info-card p-7 sm:p-8">
                            <h3 class="right-title text-2xl font-semibold text-[var(--warm-blue-deep)]" data-i18n="flowTitle">How information flows</h3>
                            <div class="mt-6 space-y-5">
                                <div class="flow-item">
                                    <h4 class="text-base font-semibold text-[var(--ink)]" data-i18n="flow1Title">1. Information is recorded</h4>
                                    <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="flow1Description">Authorized personnel enter resident, health, or incident details into the system.</p>
                                </div>
                                <div class="flow-item">
                                    <h4 class="text-base font-semibold text-[var(--ink)]" data-i18n="flow2Title">2. Records are organized</h4>
                                    <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="flow2Description">The platform keeps data in one place for easier tracking, reference, and reporting.</p>
                                </div>
                                <div class="flow-item">
                                    <h4 class="text-base font-semibold text-[var(--ink)]" data-i18n="flow3Title">3. Officials review updates</h4>
                                    <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="flow3Description">Summaries and records can be checked to support follow-up actions and barangay planning.</p>
                                </div>
                                <div class="flow-item">
                                    <h4 class="text-base font-semibold text-[var(--ink)]" data-i18n="flow4Title">4. Community services improve</h4>
                                    <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="flow4Description">Better access to information helps the barangay respond more clearly and consistently.</p>
                                </div>
                            </div>
                        </div>

                        <div class="info-card p-7 sm:p-8">
                            <h3 class="right-title text-2xl font-semibold text-[var(--warm-blue-deep)]" data-i18n="benefitsTitle">Who benefits</h3>
                            <div class="mt-6 space-y-4 text-sm leading-7 text-[var(--muted)]">
                                <div>
                                    <p class="font-semibold text-[var(--ink)]" data-i18n="benefit1Title">Barangay officials</p>
                                    <p data-i18n="benefit1Description">Use records and summaries for planning, review, and service direction.</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--ink)]" data-i18n="benefit2Title">Health workers</p>
                                    <p data-i18n="benefit2Description">Monitor patient-related information and community health activities more efficiently.</p>
                                </div>
                                <div>
                                    <p class="font-semibold text-[var(--ink)]" data-i18n="benefit3Title">Residents</p>
                                    <p data-i18n="benefit3Description">Benefit from more organized records, clearer coordination, and better service support.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="info-card p-7 sm:p-9">
                        <h3 class="right-title text-2xl font-semibold text-[var(--warm-blue-deep)]" data-i18n="importanceTitle">Why this display matters</h3>
                        <div class="mt-6 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-2xl bg-[#f8fbff] p-5">
                                <p class="text-base font-semibold text-[var(--ink)]" data-i18n="importance1Title">Clear presentation</p>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="importance1Description">The white content side improves readability and keeps the page focused on useful information.</p>
                            </div>
                            <div class="rounded-2xl bg-[#f8fbff] p-5">
                                <p class="text-base font-semibold text-[var(--ink)]" data-i18n="importance2Title">Landing-page consistency</p>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="importance2Description">The visual panel uses the same barangay image and blue tone direction while keeping a different layout.</p>
                            </div>
                            <div class="rounded-2xl bg-[#f8fbff] p-5">
                                <p class="text-base font-semibold text-[var(--ink)]" data-i18n="importance3Title">Informative only</p>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="importance3Description">The page now presents content without unnecessary dashboard-like numbers or interactive clutter.</p>
                            </div>
                            <div class="rounded-2xl bg-[#f8fbff] p-5">
                                <p class="text-base font-semibold text-[var(--ink)]" data-i18n="importance4Title">Visible text hierarchy</p>
                                <p class="mt-2 text-sm leading-7 text-[var(--muted)]" data-i18n="importance4Description">Sections are grouped with headings, spacing, and soft cards so the content is easier to scan.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script>
        (function() {
            const translations = {
                en: {
                    pageTitle: 'Barangay Luz | Learn More',
                    back: 'Back',
                    heroLabel: 'Barangay Luz Health Monitoring and Tracking System',
                    heroTitle: 'Community information presented in a clearer and more focused layout.',
                    heroDescription: 'This page is dedicated to public information only. It explains what the platform does, who it supports, and how it helps the barangay manage health, records, and service coordination.',
                    purposeTitle: 'Purpose',
                    purposeDescription: 'Organize health-related records and community updates in one reliable system.',
                    focusTitle: 'Focus',
                    focusDescription: 'Fast access to accurate information for barangay staff, health workers, and residents.',
                    supportsTitle: 'What this system supports',
                    supportItem1: 'Resident profiles and household information',
                    supportItem2: 'Health monitoring and follow-up records',
                    supportItem3: 'Incident documentation and response support',
                    supportItem4: 'Program planning and service coordination',
                    overviewLabel: 'System overview',
                    overviewTitle: 'Informative display for public viewing',
                    overviewParagraph1: 'The Barangay Luz Health Monitoring and Tracking System is a digital platform that keeps important community information organized and accessible. It helps staff manage records more efficiently while giving residents a clearer understanding of how local health and barangay services are supported.',
                    overviewParagraph2: 'This display focuses on information presentation only. It avoids clutter and places readable content on a white panel so the details remain visible against the page\'s background image treatment.',
                    card1Title: 'Resident information',
                    card1Description: 'Stores household and resident details that can support verification, profiling, and community-level planning.',
                    card2Title: 'Health monitoring',
                    card2Description: 'Supports follow-up on consultations, health programs, and other records needed by local health personnel.',
                    card3Title: 'Incident documentation',
                    card3Description: 'Records safety-related events in a structured format for easier review, coordination, and reporting.',
                    card4Title: 'Decision support',
                    card4Description: 'Helps officials review collected information and prepare service responses based on actual community needs.',
                    flowTitle: 'How information flows',
                    flow1Title: '1. Information is recorded',
                    flow1Description: 'Authorized personnel enter resident, health, or incident details into the system.',
                    flow2Title: '2. Records are organized',
                    flow2Description: 'The platform keeps data in one place for easier tracking, reference, and reporting.',
                    flow3Title: '3. Officials review updates',
                    flow3Description: 'Summaries and records can be checked to support follow-up actions and barangay planning.',
                    flow4Title: '4. Community services improve',
                    flow4Description: 'Better access to information helps the barangay respond more clearly and consistently.',
                    benefitsTitle: 'Who benefits',
                    benefit1Title: 'Barangay officials',
                    benefit1Description: 'Use records and summaries for planning, review, and service direction.',
                    benefit2Title: 'Health workers',
                    benefit2Description: 'Monitor patient-related information and community health activities more efficiently.',
                    benefit3Title: 'Residents',
                    benefit3Description: 'Benefit from more organized records, clearer coordination, and better service support.',
                    importanceTitle: 'Why this display matters',
                    importance1Title: 'Clear presentation',
                    importance1Description: 'The white content side improves readability and keeps the page focused on useful information.',
                    importance2Title: 'Landing-page consistency',
                    importance2Description: 'The visual panel uses the same barangay image and blue tone direction while keeping a different layout.',
                    importance3Title: 'Informative only',
                    importance3Description: 'The page now presents content without unnecessary dashboard-like numbers or interactive clutter.',
                    importance4Title: 'Visible text hierarchy',
                    importance4Description: 'Sections are grouped with headings, spacing, and soft cards so the content is easier to scan.'
                },
                ceb: {
                    pageTitle: 'Barangay Luz | Dugang Impormasyon',
                    back: 'Balik',
                    heroLabel: 'Barangay Luz Health Monitoring and Tracking System',
                    heroTitle: 'Impormasyon sa komunidad nga gipakita sa mas klaro ug mas nakatutok nga layout.',
                    heroDescription: 'Kini nga panid para ra sa pampublikong impormasyon. Gipatin-aw niini kung unsa ang gibuhat sa platform, kinsa ang gisuportahan niini, ug giunsa niini pagtabang ang barangay sa pagdumala sa panglawas, rekord, ug koordinasyon sa serbisyo.',
                    purposeTitle: 'Tumong',
                    purposeDescription: 'I-organisa ang mga rekord nga may kalabotan sa panglawas ug mga update sa komunidad sa usa ka kasaligang sistema.',
                    focusTitle: 'Pokos',
                    focusDescription: 'Paspas nga access sa tukmang impormasyon para sa barangay staff, health workers, ug mga residente.',
                    supportsTitle: 'Unsa ang gisuportahan sa sistema',
                    supportItem1: 'Mga profile sa residente ug impormasyon sa panimalay',
                    supportItem2: 'Pagmonitor sa panglawas ug follow-up records',
                    supportItem3: 'Dokumentasyon sa insidente ug suporta sa pagtubag',
                    supportItem4: 'Pagplano sa programa ug koordinasyon sa serbisyo',
                    overviewLabel: 'Kinatibuk-ang tan-aw sa sistema',
                    overviewTitle: 'Informative display para sa public viewing',
                    overviewParagraph1: 'Ang Barangay Luz Health Monitoring and Tracking System usa ka digital platform nga nagtipig sa importanteng impormasyon sa komunidad sa organisado ug dali ma-access nga paagi. Makatabang kini sa staff sa mas episyenteng pagdumala sa records samtang naghatag sa mga residente og mas klarong pagsabot sa suporta sa lokal nga health ug barangay services.',
                    overviewParagraph2: 'Kini nga display nakapokus lang sa presentasyon sa impormasyon. Naglikay kini sa kalat ug gibutang ang mabasang sulod sa puti nga panel aron klaro gihapon ang detalye bisan pa sa background image sa panid.',
                    card1Title: 'Impormasyon sa residente',
                    card1Description: 'Nagtipig sa detalye sa panimalay ug residente nga makatabang sa verification, profiling, ug community-level planning.',
                    card2Title: 'Pagmonitor sa panglawas',
                    card2Description: 'Nagsuporta sa follow-up sa consultations, health programs, ug ubang records nga kinahanglan sa local health personnel.',
                    card3Title: 'Dokumentasyon sa insidente',
                    card3Description: 'Nagre-record sa safety-related events sa organisadong pormat para mas sayon ang review, coordination, ug reporting.',
                    card4Title: 'Suporta sa desisyon',
                    card4Description: 'Makatabang sa mga opisyales sa pagreview sa nakolektang impormasyon ug pag-andam sa service responses base sa tinuod nga panginahanglan sa komunidad.',
                    flowTitle: 'Giunsa pag-agos sa impormasyon',
                    flow1Title: '1. Girekord ang impormasyon',
                    flow1Description: 'Ang awtorisadong personnel mosulod sa detalye sa residente, panglawas, o insidente sa sistema.',
                    flow2Title: '2. Giorganisa ang records',
                    flow2Description: 'Ang platform nagtipig sa datos sa usa ka lugar para mas sayon ang tracking, reference, ug reporting.',
                    flow3Title: '3. Gi-review sa mga opisyales ang updates',
                    flow3Description: 'Ang summaries ug records mahimong susihon aron masuportahan ang follow-up actions ug barangay planning.',
                    flow4Title: '4. Mokaayo ang serbisyo sa komunidad',
                    flow4Description: 'Ang mas maayong access sa impormasyon makatabang sa barangay sa mas klaro ug mas makanunayon nga pagtubag.',
                    benefitsTitle: 'Kinsa ang makabenepisyo',
                    benefit1Title: 'Mga opisyales sa barangay',
                    benefit1Description: 'Gigamit ang records ug summaries para sa planning, review, ug direksyon sa serbisyo.',
                    benefit2Title: 'Mga health worker',
                    benefit2Description: 'Mas episyente nga ma-monitor ang patient-related information ug community health activities.',
                    benefit3Title: 'Mga residente',
                    benefit3Description: 'Makabenepisyo sa mas organisadong records, mas klarong koordinasyon, ug mas maayong suporta sa serbisyo.',
                    importanceTitle: 'Ngano nga importante kini nga display',
                    importance1Title: 'Klarong presentasyon',
                    importance1Description: 'Ang puti nga content side nagpalambo sa readability ug nagpabilin nga nakapokus ang panid sa kapuslanan nga impormasyon.',
                    importance2Title: 'Pagkakontinyo sa landing page',
                    importance2Description: 'Ang visual panel migamit sa parehas nga barangay image ug blue tone direction samtang lahi ang layout.',
                    importance3Title: 'Impormatibo lang',
                    importance3Description: 'Ang panid nagpresentar na karon og sulod nga walay dili kinahanglang dashboard-like numbers o interactive clutter.',
                    importance4Title: 'Klarong hierarchy sa teksto',
                    importance4Description: 'Ang mga seksyon gigrupo pinaagi sa headings, spacing, ug soft cards aron mas sayon ma-scan ang sulod.'
                },
                tl: {
                    pageTitle: 'Barangay Luz | Alamin Pa',
                    back: 'Bumalik',
                    heroLabel: 'Barangay Luz Health Monitoring and Tracking System',
                    heroTitle: 'Impormasyon ng komunidad na ipinapakita sa mas malinaw at mas nakatuong layout.',
                    heroDescription: 'Ang pahinang ito ay para lamang sa pampublikong impormasyon. Ipinapaliwanag nito kung ano ang ginagawa ng platform, sino ang sinusuportahan nito, at paano nito tinutulungan ang barangay sa pamamahala ng kalusugan, mga rekord, at koordinasyon ng serbisyo.',
                    purposeTitle: 'Layunin',
                    purposeDescription: 'Ayusin ang mga rekord na may kaugnayan sa kalusugan at mga update ng komunidad sa isang maaasahang sistema.',
                    focusTitle: 'Pokús',
                    focusDescription: 'Mabilis na access sa tamang impormasyon para sa barangay staff, health workers, at mga residente.',
                    supportsTitle: 'Ano ang sinusuportahan ng sistemang ito',
                    supportItem1: 'Mga profile ng residente at impormasyon ng sambahayan',
                    supportItem2: 'Pagsubaybay sa kalusugan at follow-up records',
                    supportItem3: 'Dokumentasyon ng insidente at suporta sa pagtugon',
                    supportItem4: 'Pagpaplano ng programa at koordinasyon ng serbisyo',
                    overviewLabel: 'Pangkalahatang-ideya ng sistema',
                    overviewTitle: 'Impormatibong display para sa public viewing',
                    overviewParagraph1: 'Ang Barangay Luz Health Monitoring and Tracking System ay isang digital platform na nagpapanatiling organisado at madaling ma-access ang mahahalagang impormasyon ng komunidad. Tinutulungan nito ang staff na mas mahusay na pamahalaan ang records habang binibigyan ang mga residente ng mas malinaw na pag-unawa kung paano sinusuportahan ang lokal na health at barangay services.',
                    overviewParagraph2: 'Nakatuon lamang ang display na ito sa presentasyon ng impormasyon. Iniiwasan nito ang kalat at inilalagay ang madaling basahing content sa puting panel upang manatiling malinaw ang detalye laban sa background image ng pahina.',
                    card1Title: 'Impormasyon ng residente',
                    card1Description: 'Nag-iimbak ng detalye ng sambahayan at residente na maaaring makatulong sa verification, profiling, at community-level planning.',
                    card2Title: 'Pagsubaybay sa kalusugan',
                    card2Description: 'Sumusuporta sa follow-up ng consultations, health programs, at iba pang records na kailangan ng local health personnel.',
                    card3Title: 'Dokumentasyon ng insidente',
                    card3Description: 'Nagtatala ng safety-related events sa organisadong format para sa mas madaling review, coordination, at reporting.',
                    card4Title: 'Suporta sa pagdedesisyon',
                    card4Description: 'Tinutulungan ang mga opisyal na suriin ang nakolektang impormasyon at maghanda ng service responses batay sa aktuwal na pangangailangan ng komunidad.',
                    flowTitle: 'Paano dumadaloy ang impormasyon',
                    flow1Title: '1. Itinatala ang impormasyon',
                    flow1Description: 'Ang awtorisadong personnel ay naglalagay ng detalye ng residente, kalusugan, o insidente sa sistema.',
                    flow2Title: '2. Inaayos ang mga rekord',
                    flow2Description: 'Pinananatili ng platform ang data sa iisang lugar para mas madali ang tracking, reference, at reporting.',
                    flow3Title: '3. Sinusuri ng mga opisyal ang mga update',
                    flow3Description: 'Maaaring suriin ang summaries at records upang masuportahan ang follow-up actions at barangay planning.',
                    flow4Title: '4. Gumaganda ang serbisyo sa komunidad',
                    flow4Description: 'Ang mas maayos na access sa impormasyon ay tumutulong sa barangay na tumugon nang mas malinaw at mas pare-pareho.',
                    benefitsTitle: 'Sino ang nakikinabang',
                    benefit1Title: 'Mga opisyal ng barangay',
                    benefit1Description: 'Ginagamit ang records at summaries para sa planning, review, at direksyon ng serbisyo.',
                    benefit2Title: 'Mga health worker',
                    benefit2Description: 'Mas mahusay na nasusubaybayan ang patient-related information at community health activities.',
                    benefit3Title: 'Mga residente',
                    benefit3Description: 'Nakikinabang sa mas organisadong records, mas malinaw na koordinasyon, at mas magandang service support.',
                    importanceTitle: 'Bakit mahalaga ang display na ito',
                    importance1Title: 'Malinaw na presentasyon',
                    importance1Description: 'Pinapaganda ng puting content side ang readability at pinananatiling nakatuon ang pahina sa kapaki-pakinabang na impormasyon.',
                    importance2Title: 'Pagkakatugma sa landing page',
                    importance2Description: 'Gumagamit ang visual panel ng kaparehong barangay image at blue tone direction habang may ibang layout.',
                    importance3Title: 'Impormatibo lamang',
                    importance3Description: 'Ipinapakita ngayon ng pahina ang content nang walang hindi kailangang dashboard-like numbers o interactive clutter.',
                    importance4Title: 'Malinaw na hierarchy ng teksto',
                    importance4Description: 'Pinaggrupo ang mga seksyon gamit ang headings, spacing, at soft cards para mas madaling i-scan ang content.'
                }
            };

            function applyTranslations(language) {
                const currentLanguage = translations[language] ? language : 'en';
                const dictionary = translations[currentLanguage];

                document.documentElement.lang = currentLanguage === 'ceb' ? 'ceb' : currentLanguage;
                document.title = dictionary.pageTitle;

                document.querySelectorAll('[data-i18n]').forEach((element) => {
                    const key = element.dataset.i18n;
                    if (dictionary[key]) {
                        element.textContent = dictionary[key];
                    }
                });
            }

            applyTranslations(localStorage.getItem('preferredLanguage') || 'en');

            const backBtn = document.getElementById('backButton');

            if (!backBtn) {
                return;
            }

            backBtn.addEventListener('click', function(event) {
                event.preventDefault();

                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                window.location.href = 'index.php';
            });
        })();
    </script>
</body>
</html>
