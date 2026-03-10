<?php
// get_consultation_notes.php
session_start();
require_once __DIR__ . '/../includes/auth.php';


redirectIfNotLoggedIn();

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

            echo '<div class="note-card">
                    <div class="note-header">
                        <div>
                            <div class="flex flex-col">
                                <span class="text-sm text-gray-400 mb-1">Date Created:</span>
                                <span class="text-lg font-medium" style="color: #387EC3;">' . $noteDate . '</span>
                            </div>
                            <div class="text-xs text-gray-500 mt-1">';


            // Display doctor name if available
            // if (!empty($note['doctor_name'])) {
            //     echo '<i class="fas fa-user-md mr-1 text-blue-500"></i> ' . htmlspecialchars($note['doctor_name']);
            // } else {
            //     echo 'By: ' . htmlspecialchars($note['created_by_name'] ?? 'Staff');
            // }
            echo '</div>
                        </div>
                        <span style="background-color: #007BFF4D; color: #007BFF; padding: 4px 8px; border-radius: 4px;">Noted</span>
                    </div>
                    
                    <div class="note-content">
                        <div class="flex flex-col">
                            <span class="text-sm text-gray-400 mb-1">Physical Assign:</span>
                            <span class="text-md font-medium" style="color: #387EC3;">' . htmlspecialchars($note['doctor_name']) . '</span>
                        </div>
                    </div>';

            if (!empty($note['next_consultation_date'])) {
                $nextDate = date('M d, Y', strtotime($note['next_consultation_date']));
                echo '<div class="text-sm font-medium mb-6 p-3 rounded-md" style="color: #007BFF; background-color: #007BFF4D; width: fit-content;">
                            Next Consultation: ' . $nextDate . '
                        </div>';
            }

            echo '<div class="note-actions">
                    <button onclick="viewNoteDetails(' . $note['id'] . ')" class="btn-view-note flex flex-row items-center">
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
