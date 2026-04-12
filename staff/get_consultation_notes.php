<?php
// get_consultation_notes.php
session_start();
require_once __DIR__ . '/../includes/auth.php';


redirectIfNotLoggedIn();

if (!staffCanAccessConsultationNotes()) {
    http_response_code(403);
    die('Only Assistant Doctor accounts can access consultation notes');
}

if (!isset($_GET['patient_id'])) {
    die('Patient ID is required');
}

$patientId = $_GET['patient_id'];
$staffId = $_SESSION['user']['id'];
$inline = isset($_GET['inline']) && $_GET['inline'] == 'true';

try {
    require_once __DIR__ . '/../includes/functions.php';
    // Verify patient belongs to staff or sharing enabled
    if (staff_can_view_all()) {
        $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ?");
        $stmt->execute([$patientId]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM sitio1_patients WHERE id = ? AND added_by = ?");
        $stmt->execute([$patientId, $staffId]);
    }

    if (!$stmt->fetch()) {
        die('Access denied');
    }

    // Get consultation notes
    $stmt = $pdo->prepare("
        SELECT cn.*, su.full_name as created_by_name 
        FROM consultation_notes cn
        LEFT JOIN sitio1_users su ON cn.created_by = su.id
        WHERE cn.patient_id = ? 
        ORDER BY cn.consultation_date DESC, cn.created_at DESC
    ");
    $stmt->execute([$patientId]);
    $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($notes)) {
        if ($inline) {
            echo '<div class="empty-notes ">
                    <div class="flex justify-center w-full mb-6">
                        <svg width="55" height="55" viewBox="0 0 55 55" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M45.2849 8.43094L17.3015 3.48953C16.4038 3.33156 15.4801 3.53659 14.7335 4.05955C13.987 4.5825 13.4787 5.38055 13.3204 6.27821L6.92884 42.5868C6.85067 43.0316 6.86091 43.4875 6.95898 43.9283C7.05706 44.3691 7.24104 44.7864 7.50041 45.1561C7.75979 45.5258 8.08948 45.8407 8.47064 46.083C8.8518 46.3252 9.27695 46.49 9.72181 46.5679L37.7052 51.5093C38.1502 51.5877 38.6062 51.5777 39.0473 51.4798C39.4884 51.3819 39.9059 51.198 40.2758 50.9386C40.6458 50.6792 40.961 50.3494 41.2034 49.968C41.4457 49.5867 41.6106 49.1614 41.6884 48.7163L48.08 12.4077C48.2366 11.5097 48.0301 10.5863 47.5059 9.84053C46.9818 9.09477 46.1829 8.58774 45.2849 8.43094ZM38.2982 48.1233L10.3126 43.1819L16.7042 6.87332L44.6876 11.8147L38.2982 48.1233ZM19.1943 12.5495C19.2739 12.1008 19.5284 11.7022 19.9019 11.4411C20.2753 11.18 20.7372 11.078 21.1859 11.1573L39.0179 14.3048C39.4417 14.379 39.8222 14.6095 40.0844 14.9507C40.3465 15.2919 40.4711 15.719 40.4335 16.1476C40.396 16.5762 40.1991 16.9752 39.8817 17.2657C39.5643 17.5561 39.1495 17.717 38.7193 17.7165C38.6185 17.7163 38.5179 17.7077 38.4185 17.6907L20.5865 14.5411C20.1378 14.4615 19.7391 14.207 19.4781 13.8335C19.217 13.46 19.1149 12.9982 19.1943 12.5495ZM18.004 19.3214C18.0432 19.099 18.1258 18.8866 18.2471 18.6962C18.3684 18.5057 18.526 18.3411 18.711 18.2116C18.8959 18.0821 19.1046 17.9903 19.325 17.9415C19.5454 17.8927 19.7733 17.8878 19.9956 17.927L37.8277 21.0766C38.2545 21.148 38.6386 21.3777 38.9034 21.72C39.1682 22.0622 39.2942 22.4917 39.2563 22.9227C39.2183 23.3537 39.0191 23.7546 38.6986 24.0452C38.378 24.3358 37.9596 24.4949 37.5269 24.4905C37.4253 24.4907 37.3238 24.4814 37.224 24.4626L19.3919 21.3151C18.9436 21.2345 18.5456 20.9793 18.2854 20.6054C18.0252 20.2316 17.924 19.7698 18.004 19.3214ZM16.8117 26.0911C16.8928 25.6436 17.1479 25.2465 17.5212 24.9868C17.8945 24.7271 18.3555 24.6259 18.8033 24.7054L27.715 26.2716C28.1386 26.3458 28.519 26.5761 28.7811 26.9171C29.0432 27.258 29.1679 27.6849 29.1307 28.1133C29.0935 28.5418 28.897 28.9407 28.58 29.2314C28.263 29.522 27.8486 29.6833 27.4185 29.6833C27.3177 29.6832 27.2171 29.6746 27.1177 29.6575L18.2017 28.0827C17.7534 28.0026 17.3553 27.7479 17.0947 27.3745C16.8341 27.0011 16.7323 26.5395 16.8117 26.0911Z" fill="black" fill-opacity="0.3"/>
                        </svg>
                    </div>
                    <p class="text-lg font-semibold">No consultation notes found.</p>
                    <p class="text-sm text-gray-500 mt-2">Add your first consultation note for this patient.</p>
                  </div>';
        } else {
            echo '<tr><td colspan="4" class="text-center py-8 text-gray-500">No consultation notes found.</td></tr>';
        }
        exit;
    }

    if ($inline) {
        echo '<div class="horizontal-notes-container">';
        foreach ($notes as $note) {
            $noteDate = date('M d, Y', strtotime($note['consultation_date']));
            $createdAt = date('M d, Y h:i A', strtotime($note['created_at']));
            $notePreview = htmlspecialchars(substr($note['note'], 0, 200)) . (strlen($note['note']) > 200 ? '...' : '');
            
            // Get current date for comparison
            $currentDateOnly = new DateTime();
            $currentDateOnly->setTime(0, 0, 0);
            
            $dbStatus = $note['status'] ?? 'pending';
            $nextConsultationDate = !empty($note['next_consultation_date']) ? $note['next_consultation_date'] : null;
            
            // STATUS LOGIC - SAME AS RESIDENT USER
            if ($dbStatus === 'completed') {
                $badgeHtml = '<span class="inline-flex items-center justify-center gap-2 px-3 py-1 rounded-md bg-green-100 text-green-700 text-sm font-medium">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg>
    <span class="leading-none">Completed</span>
</span>';
                $showCompleteButton = false;
                $buttonDisabled = true;
                $buttonTitle = 'Already completed';
            } 
            elseif (!empty($nextConsultationDate)) {
                $nextDateObj = new DateTime($nextConsultationDate);
                $nextDateObj->setTime(0, 0, 0);
                
                // Check if next consultation date has passed (for Missed status)
                if ($nextDateObj < $currentDateOnly) {
                    // Missed - next consultation date is in the past
                    $badgeHtml = '<span class="inline-flex items-center justify-center gap-2 px-3 py-1 rounded-md bg-red-100 text-red-700 text-sm font-medium">
    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M16.6508 13.2251L10.5019 2.54677C10.3483 2.28515 10.1289 2.06823 9.8656 1.91751C9.60229 1.76679 9.30415 1.6875 9.00075 1.6875C8.69735 1.6875 8.39922 1.76679 8.13591 1.91751C7.87259 2.06823 7.65324 2.28515 7.49958 2.54677L1.35075 13.2251C1.20291 13.4782 1.125 13.766 1.125 14.059C1.125 14.3521 1.20291 14.6399 1.35075 14.8929C1.50244 15.1561 1.72142 15.3742 1.98523 15.5249C2.24903 15.6755 2.54816 15.7532 2.85193 15.75H15.1496C15.4531 15.753 15.7519 15.6751 16.0155 15.5245C16.279 15.3739 16.4978 15.1559 16.6493 14.8929C16.7974 14.64 16.8756 14.3523 16.8758 14.0592C16.8761 13.7662 16.7984 13.4783 16.6508 13.2251ZM15.6755 14.3297C15.6219 14.4212 15.545 14.4967 15.4525 14.5486C15.3601 14.6005 15.2556 14.6269 15.1496 14.625H2.85193C2.74595 14.6269 2.64139 14.6005 2.54897 14.5486C2.45655 14.4967 2.37959 14.4212 2.32599 14.3297C2.27743 14.2475 2.25182 14.1538 2.25182 14.0583C2.25182 13.9629 2.27743 13.8691 2.32599 13.7869L8.47482 3.10856C8.5295 3.01756 8.60681 2.94226 8.69922 2.88998C8.79162 2.8377 8.89599 2.81022 9.00216 2.81022C9.10833 2.81022 9.2127 2.8377 9.3051 2.88998C9.39751 2.94226 9.47482 3.01756 9.5295 3.10856L15.6783 13.7869C15.7265 13.8694 15.7516 13.9632 15.7511 14.0587C15.7506 14.1542 15.7245 14.2478 15.6755 14.3297ZM8.43825 10.125V7.31255C8.43825 7.16336 8.49752 7.02029 8.60301 6.9148C8.7085 6.80931 8.85157 6.75005 9.00075 6.75005C9.14994 6.75005 9.29301 6.80931 9.3985 6.9148C9.50399 7.02029 9.56325 7.16336 9.56325 7.31255V10.125C9.56325 10.2742 9.50399 10.4173 9.3985 10.5228C9.29301 10.6283 9.14994 10.6875 9.00075 10.6875C8.85157 10.6875 8.7085 10.6283 8.60301 10.5228C8.49752 10.4173 8.43825 10.2742 8.43825 10.125ZM9.8445 12.6563C9.8445 12.8232 9.79502 12.9863 9.70231 13.1251C9.60959 13.2638 9.47782 13.372 9.32364 13.4358C9.16947 13.4997 8.99982 13.5164 8.83615 13.4838C8.67248 13.4513 8.52213 13.3709 8.40413 13.2529C8.28613 13.1349 8.20577 12.9846 8.17322 12.8209C8.14066 12.6572 8.15737 12.4876 8.22123 12.3334C8.28509 12.1792 8.39324 12.0475 8.53199 11.9547C8.67075 11.862 8.83388 11.8125 9.00075 11.8125C9.22453 11.8125 9.43914 11.9014 9.59738 12.0597C9.75561 12.2179 9.8445 12.4325 9.8445 12.6563Z" fill="#B30000"/>
</svg>
    <span class="leading-none">Missed</span>
</span>';
                    $showCompleteButton = false;
                    $buttonDisabled = true;
                    $buttonTitle = 'Consultation has been missed';
                } 
                // Check if next consultation date is today or in the future
                elseif ($nextDateObj == $currentDateOnly) {
                    // TODAY - Button should be enabled (can mark as completed today)
                    // Add (Today) indicator to the status badge ONLY
                    $badgeHtml = '<span class="inline-flex items-center justify-center gap-2 px-3 py-1 rounded-md bg-yellow-100 text-yellow-700 text-sm font-medium">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM17.25 20.25H6.75V16.875L12 12.9375L17.25 16.9078V20.25ZM17.25 7.09125L12 11.0625L6.75 7.125V3.75H17.25V7.09125Z" fill="#976200"/>
</svg>
    <span class="leading-none">Pending</span>
    <span class="text-xs ml-1 text-yellow-600">(Today)</span>
</span>';
                    $showCompleteButton = true;
                    $buttonDisabled = false;
                    $buttonTitle = 'Mark consultation as completed for today';
                } 
                else {
                    // Future date - Button should be DISABLED (can't complete before the date)
                    // Add (Upcoming) indicator to the status badge ONLY
                    $badgeHtml = '<span class="inline-flex items-center justify-center gap-2 px-3 py-1 rounded-md bg-yellow-100 text-yellow-700 text-sm font-medium">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM17.25 20.25H6.75V16.875L12 12.9375L17.25 16.9078V20.25ZM17.25 7.09125L12 11.0625L6.75 7.125V3.75H17.25V7.09125Z" fill="#976200"/>
</svg>
    <span class="leading-none">Pending</span>
    <span class="text-xs ml-1 text-yellow-600">(Upcoming)</span>
</span>';
                    $showCompleteButton = true;
                    $buttonDisabled = true; // DISABLED - cannot complete before the date
                    $buttonTitle = 'Cannot complete before the scheduled date';
                }
            } 
            else {
                // No next consultation date set - No indicator
                $badgeHtml = '<span class="inline-flex items-center justify-center gap-2 px-3 py-1 rounded-md bg-yellow-100 text-yellow-700 text-sm font-medium">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M18.75 7.09125V3.75C18.75 3.35218 18.592 2.97064 18.3107 2.68934C18.0294 2.40804 17.6478 2.25 17.25 2.25H6.75C6.35218 2.25 5.97064 2.40804 5.68934 2.68934C5.40804 2.97064 5.25 3.35218 5.25 3.75V7.125C5.25051 7.35778 5.30495 7.58727 5.40905 7.79548C5.51315 8.00368 5.66408 8.18493 5.85 8.325L10.7503 12L5.85 15.675C5.66408 15.8151 5.51315 15.9963 5.40905 16.2045C5.30495 16.4127 5.25051 16.6422 5.25 16.875V20.25C5.25 20.6478 5.40804 21.0294 5.68934 21.3107C5.97064 21.592 6.35218 21.75 6.75 21.75H17.25C17.6478 21.75 18.0294 21.592 18.3107 21.3107C18.592 21.0294 18.75 20.6478 18.75 20.25V16.9088C18.7495 16.6769 18.6955 16.4482 18.5922 16.2406C18.489 16.033 18.3393 15.8519 18.1547 15.7116L13.2441 12L18.1547 8.2875C18.3393 8.14742 18.4891 7.96658 18.5924 7.75908C18.6957 7.55158 18.7496 7.32303 18.75 7.09125ZM17.25 20.25H6.75V16.875L12 12.9375L17.25 16.9078V20.25ZM17.25 7.09125L12 11.0625L6.75 7.125V3.75H17.25V7.09125Z" fill="#976200"/>
</svg>
    <span class="leading-none">Pending</span>
</span>';
                $showCompleteButton = true;
                $buttonDisabled = false;
                $buttonTitle = 'Mark consultation as completed';
            }

            echo '<div class="note-card">
                    <div class="note-header">
                        <div>
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-400 mb-1">Date Created:</span>
                                <span class="text-lg font-medium" style="color: #387EC3;">' . $noteDate . '</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">';

            echo '</div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span style="background-color: #007BFF4D; color: #007BFF; padding: 4px 10px; border-radius: 4px;">Noted</span>
                            ' . $badgeHtml . '
                        </div>
                    </div>
                    
                    <div class="note-content">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-400 mb-1">Physician Assign:</span>
                            <span class="text-md font-medium" style="color: #387EC3;">' . htmlspecialchars($note['doctor_name']) . '</span>
                        </div>
                    </div>';

            if (!empty($note['next_consultation_date'])) {
                $nextDate = date('M d, Y', strtotime($note['next_consultation_date']));
                // Check if next consultation date is past for styling
                $nextDateObj = new DateTime($note['next_consultation_date']);
                $nextDateObj->setTime(0, 0, 0);
                $currentDateOnly = new DateTime();
                $currentDateOnly->setTime(0, 0, 0);
                $isPastDue = ($nextDateObj < $currentDateOnly) && ($dbStatus !== 'completed');
                $isToday = ($nextDateObj == $currentDateOnly) && ($dbStatus !== 'completed');
                
                if ($isPastDue) {
                    $dateStyle = 'color: #DC2626; background-color: #FEE2E2;';
                } elseif ($isToday) {
                    $dateStyle = 'color: #D97706; background-color: #FEF3C7;';
                } else {
                    $dateStyle = 'color: #007BFF; background-color: #007BFF4D;';
                }
                
                // NO indicator here - just the date
                echo '<div class="text-sm font-medium mb-6 py-2 px-4 rounded-md" style="' . $dateStyle . ' width: fit-content;">
                            Next Consultation: ' . $nextDate . '
                        </div>';
            }

            echo '<div class="note-actions">';
            
            // Only show Complete Visit button if applicable
            if ($showCompleteButton) {
                $disabledAttr = $buttonDisabled ? 'disabled' : '';
                $disabledClass = $buttonDisabled ? 'opacity-50 cursor-not-allowed' : '';
                $titleAttr = $buttonDisabled ? 'title="' . $buttonTitle . '"' : '';
                
                echo '<form method="POST" action="" style="display: inline;" onsubmit="return false;">
                        <input type="hidden" name="complete_note_id" value="' . $note['id'] . '">
                        <button type="button" ' . $disabledAttr . ' ' . $titleAttr . ' 
                                onclick="' . ($buttonDisabled ? 'return false;' : 'markConsultationComplete(' . $note['id'] . ', this)') . '" 
                                class="btn-complete-visit flex flex-row items-center ' . $disabledClass . '">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" class="mr-1">
                                <path d="M17.9425 6.06754L7.94254 16.0675C7.88449 16.1256 7.81556 16.1717 7.73969 16.2032C7.66381 16.2347 7.58248 16.2508 7.50035 16.2508C7.41821 16.2508 7.33688 16.2347 7.26101 16.2032C7.18514 16.1717 7.11621 16.1256 7.05816 16.0675L2.68316 11.6925C2.56588 11.5753 2.5 11.4162 2.5 11.2503C2.5 11.0845 2.56588 10.9254 2.68316 10.8082C2.80044 10.6909 2.9595 10.625 3.12535 10.625C3.2912 10.625 3.45026 10.6909 3.56753 10.8082L7.50035 14.7418L17.0582 5.18316C17.1754 5.06588 17.3345 5 17.5003 5C17.6662 5 17.8253 5.06588 17.9425 5.18316C18.0598 5.30044 18.1257 5.4595 18.1257 5.62535C18.1257 5.7912 18.0598 5.95026 17.9425 6.06754Z" fill="white"/>
                            </svg>  
                            Complete Visit
                        </button>
                      </form>';
            }
            
            // Always show View button
            echo '<button onclick="viewNoteDetails(' . $note['id'] . ')" class="btn-view-note flex flex-row items-center">
                        <svg width="20" height="20" class="mr-1" viewBox="0 0 15 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.49609 7.625C8.49609 6.93464 7.93645 6.375 7.24609 6.375C6.55574 6.375 5.99609 6.93464 5.99609 7.625C5.99609 8.31536 6.55574 8.875 7.24609 8.875C7.93645 8.875 8.49609 8.31536 8.49609 7.625ZM9.74609 7.625C9.74609 9.00571 8.62681 10.125 7.24609 10.125C5.86538 10.125 4.74609 9.00571 4.74609 7.625C4.74609 6.24429 5.86538 5.125 7.24609 5.125C8.62681 5.125 9.74609 6.24429 9.74609 7.625Z" fill="white"/>
                            <path d="M7.24512 2C10.0021 2 11.5909 3.39922 12.4624 4.79358C12.8914 5.47995 13.1464 6.1615 13.2943 6.66858C13.3686 6.92322 13.4169 7.13734 13.4469 7.28992C13.4619 7.36618 13.4723 7.4275 13.4792 7.47119C13.4827 7.4929 13.4854 7.51028 13.4872 7.52307C13.4881 7.52943 13.4885 7.53491 13.489 7.53894C13.4893 7.54096 13.4894 7.54302 13.4896 7.54443L13.4902 7.54626V7.54688L12.25 7.70312V7.70374C12.2499 7.70298 12.2498 7.70113 12.2494 7.69824C12.2485 7.69215 12.2468 7.68125 12.2445 7.6665C12.2398 7.637 12.2319 7.59068 12.2201 7.5304C12.1964 7.40957 12.1567 7.23281 12.0944 7.01892C11.9688 6.58858 11.755 6.0199 11.4028 5.45642C10.7119 4.35085 9.48802 3.25 7.24512 3.25C5.00222 3.25 3.77838 4.35085 3.0874 5.45642C2.73526 6.0199 2.52139 6.58858 2.39587 7.01892C2.3335 7.23281 2.29388 7.40957 2.27014 7.5304C2.2583 7.59068 2.2504 7.637 2.24573 7.6665C2.24339 7.68125 2.2417 7.69215 2.24084 7.69824L2.24023 7.70374V7.70312L1 7.54688V7.54626L1.00061 7.54443C1.00079 7.54302 1.00095 7.54096 1.00122 7.53894C1.00176 7.53491 1.00215 7.52943 1.00305 7.52307C1.00486 7.51028 1.00755 7.4929 1.01099 7.47119C1.0179 7.4275 1.02836 7.36618 1.04333 7.28992C1.07331 7.13734 1.12165 6.92322 1.19592 6.66858C1.34383 6.1615 1.59885 5.47995 2.02783 4.79358C2.89938 3.39922 4.48816 2 7.24512 2Z" fill="white"/>
                        </svg>
                        View
                    </button>
                  </div>
                </div>';
        }
        echo '</div>';
    
    
    } else {
        // Return JSON for other uses
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'notes' => $notes]);
    }
} catch (PDOException $e) {
    if ($inline) {
        echo '<div class="empty-notes">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Error loading consultation notes.</p>
                <p class="text-sm text-gray-500 mt-2">' . htmlspecialchars($e->getMessage()) . '</p>
              </div>';
    } else {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
