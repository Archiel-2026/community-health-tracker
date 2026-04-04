# Activity Logging System Implementation

## Summary

I have successfully implemented a comprehensive activity logging system for the Community Health Tracker that tracks actions performed by both residents and staff members. The system now displays detailed activity logs in the admin dashboard showing exactly what actions each user performed.

## Components Implemented

### 1. Centralized Activity Logger (`includes/activity_logger.php`)

This is the core logging helper file that provides:

- **`logActivity()` function**: Universal logging function that handles logging for residents, staff, and admin users
- **`ensureActivityLogTable()` function**: Creates necessary logging tables if they don't exist
- **`ensureResidentActivityLogTable()` function**: Creates dedicated resident activity logging table
- **`getActivityLogs()` function**: Retrieves logs for display with filtering by user type
- **`formatActionType()` function**: Formats action types with appropriate labels and icons

#### Database Tables Used:
- `user_activity_log`: Tracks resident authentication and actions
- `staff_activity_log`: Tracks staff member actions
- `resident_activity_log`: NEW - Tracks detailed resident actions (announcements, etc)
- `sitio1_activity_log`: Tracks admin actions

---

## Logged Activities by User Type

### **Resident Actions**
- ✅ **Login** - When resident logs into the system
- ✅ **Logout** - When resident logs out
- ✅ **Accept Announcement** - When resident accepts a posted announcement
- ✅ **Dismiss Announcement** - When resident dismisses an announcement
- ✅ **View Announcement** - When resident views announcement details

### **Staff Actions**
- ✅ **Staff Login** - When staff member logs in
- ✅ **Staff Logout** - When staff member logs out
- ✅ **Add Patient** - When staff adds a new patient record
- ✅ **Edit Patient** - When staff edits patient information
- ✅ **Archive Patient** - When staff archives a patient record
- ✅ **Print Patient Record** - When staff prints a patient record
- ✅ **Export to PDF** - When staff exports patient records to PDF
- ✅ **Export to Excel** - When staff exports patient records to Excel
- ✅ **Send Announcement** - When staff sends announcement to residents
- ✅ **Edit Announcement** - When staff edits an announcement
- ✅ **Delete/Archive Announcement** - When staff archives an announcement
- ✅ **Add Consultation Note** - When staff adds consultation notes
- ⏳ **Generate Report** - (Already partially implemented in `city_health_report_logs`)

---

## Files Modified

### 1. **includes/activity_logger.php** (NEW)
- Created centralized logging helper with all logging functions

### 2. **user/announcements.php**
- Added import: `require_once __DIR__ . '/../includes/activity_logger.php'`
- Added logging for announcement acceptance/dismissal:
  ```php
  logActivity($pdo, $userId, $actionType, 'user', $announcementId, [...])
  ```

### 3. **api/announcements.php**
- Added import for activity logger
- Added logging for API-based announcement responses

### 4. **staff/announcements.php**
- Added import: `require_once __DIR__ . '/../includes/activity_logger.php'`
- Added logging for:
  - `send_announcement` - When creating and sending announcements
  - `edit_announcement` - When modifying announcements
  - `delete_announcement` - When archiving announcements
  - `send_announcement` (repost) - When reposting archived announcements

### 5. **admin/dashboard.php**
- Added import: `require_once __DIR__ . '/../includes/activity_logger.php'`
- Enhanced resident logs retrieval to include `resident_activity_log` table
- Updated log display sections to use `formatActionType()` for consistent styling
- Added color-coded badges for different action types

---

## How the Activity Logs Display Works

### **In Admin Dashboard > Activity Logs Modal**

#### **Resident Log Tab**
Shows all resident activities including:
- Login/Logout times
- Announcement interactions (accept/dismiss/view)
- User name and timestamp
- Action badges with color coding

#### **Admin Log Tab** (renamed from Staff Log)
Shows all staff member activities including:
- Login/Logout times
- Patient record operations
- Announcement sending
- Export/Print operations
- Names and timestamps

#### **Features**
- Pagination (10 records per page)
- Color-coded action badges:
  - 🔵 **Blue** - Authentication, Viewing
  - 🟢 **Green** - System approve, Add Record
  - 🔴 **Red** - Logout, Decline, Delete
  - 🟠 **Orange** - Edit, Update, Priority
  - 🟣 **Purple** - Archive, Announcements
  - 🔷 **Cyan** - View, Watch
  - ⚫ **Gray** - System, Other
- Icons for visual identification of actions
- Sortable and paginated results

---

## How to Use the Activity Logger

### For Developers: To log a new action

Simply call the `logActivity()` function anywhere in your code:

```php
require_once __DIR__ . '/../includes/activity_logger.php';

logActivity(
    $pdo,                    // Database connection
    $userId,                 // User/Staff ID
    'action_type',           // Action name (e.g., 'view_announcement')
    'user',                  // User type: 'user', 'staff', or 'admin'
    $relatedId,              // Related record ID (optional)
    [                        // Details as array
        'key' => 'value',
        'related_entity' => 'example'
    ]
);
```

### Example Usage in Code:

```php
// When a resident accepts an announcement
logActivity($pdo, $userId, 'accept_announcement', 'user', $announcementId, 
    ['announcement_title' => $title, 'status' => 'accepted']);

// When staff adds a patient
logActivity($pdo, $staffId, 'add_patient', 'staff', $patientId, 
    ['patient_name' => $patientName]);

// When staff sends announcement
logActivity($pdo, $staffId, 'send_announcement', 'staff', $announcementId, 
    ['title' => $title, 'audience_type' => 'public', 'target_count' => 150]);
```

---

## Action Type Reference

The system supports the following action types with automatic icon and color assignment:

### Authentication
- `login` / `logout`
- `staff_login` / `staff_logout`

### Announcements (Resident)
- `accept_announcement` (✅ icon, green)
- `dismiss_announcement` (✗ icon, red)
- `view_announcement` (👁 icon, cyan)

### Announcements (Staff)
- `send_announcement` (📢 icon, purple)
- `edit_announcement` (✏️ icon, orange)
- `delete_announcement` (🗑️ icon, red)

### Patient Records
- `add_patient` (➕ icon, green)
- `edit_patient` / `update_patient` (✏️ icon, orange)
- `archive_patient` (📦 icon, purple)
- `delete_patient` (🗑️ icon, red)
- `print_patient` / `print_record` (🖨️ icon, cyan)

### Reports & Exports
- `generate_report` (📊 icon, indigo)
- `export_pdf` (📄 icon, red)
- `export_excel` (📊 icon, green)
- `export_bulk_pdf` / `export_bulk_excel` (⬇️ icon, blue)

---

## Database Schema

### Tables Created/Used:

#### `resident_activity_log` (NEW)
```sql
CREATE TABLE resident_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    related_id INT,
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES sitio1_users(id) ON DELETE CASCADE,
    INDEX idx_resident_id (resident_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
```

#### `staff_activity_log` (Updated with Indexes)
- Stores all staff member actions
- Contains details in JSON format
- Indexed for fast retrieval

#### `user_activity_log` (Updated with Indexes)
- Stores resident login/logout events
- Used for security audit trails

---

## Testing Checklist

To verify the activity logging is working:

1. ✅ **Resident Login/Logout**
   - Log in as a resident
   - Log out
   - Check admin dashboard Activity Logs > Resident Log

2. ✅ **Announcement Actions**
   - As resident: Accept and dismiss announcements
   - Check logs show "Accepted Announcement", "Dismissed Announcement"

3. ✅ **Staff Announcements**
   - As staff: Create and send announcements
   - Check logs show "Sent Announcement" with target count

4. ✅ **Staff Patient Operations**
   - As staff: Add/edit/archive patients
   - Verify logs show corresponding actions

5. ✅ **Color Coding**
   - Verify different action types show different colored badges
   - Icons appear correctly

6. ✅ **Pagination**
   - Generate many actions
   - Verify pagination works (10 per page)
   - Test Previous/Next buttons

---

## Future Enhancements

You can easily extend this system to log additional actions:

1. **Export Functionality Logging** - Add logging to export Excel/PDF operations
2. **Report Generation Logging** - Enhanced report tracking
3. **System Configuration Changes** - Admin settings modifications
4. **Backup/Restore Operations** - Data management actions
5. **Search & Export Logs** - Ability to filter and export activity logs
6. **User Activity Analytics** - Dashboard showing user engagement metrics
7. **Real-time Log Notifications** - Alert admins of suspicious activities

---

## Important Notes

- **IP Address Tracking**: All logs capture the user's IP address for security purposes
- **User Agent Tracking**: Browser/device information is captured for troubleshooting
- **JSON Details**: Complex actions store additional details in JSON format
- **Auto-cleanup**: You may want to add a scheduled task to archive old logs (>90 days)
- **Performance**: Logs are indexed for fast retrieval. Consider archiving old logs to maintain performance

---

## Support

For each logged action, the system automatically:
- Records timestamp of the action
- Captures user/staff ID
- Stores IP address and user agent for security
- Preserves any related record IDs
- Stores additional context in JSON format

This comprehensive activity tracking system ensures full transparency and accountability for all actions in the Community Health Tracker system.
