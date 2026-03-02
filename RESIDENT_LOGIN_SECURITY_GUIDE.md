# Resident Login Process with Account Lockout & Admin Password Reset

## Overview
This document describes the resident login security system that includes:
1. **Account Lockout Protection** - Automatic account lockout after multiple failed login attempts
2. **Admin Password Reset** - Only super admin can reset resident passwords and automatically unlock accounts

---

## 1. ACCOUNT LOCKOUT MECHANISM

### How It Works

#### Failed Login Attempts
- **Tracking**: System tracks failed login attempts per resident
- **Duration**: Failed attempts reset if no login attempts occur for 15 minutes
- **Lockout Threshold**: Account locks after 5 consecutive failed attempts
- **Lockout Duration**: Account remains locked for 20 minutes

#### Database Fields
The `sitio1_users` table stores:
- `failed_login_attempts` (INT) - Count of consecutive failed login attempts
- `last_failed_login` (DATETIME) - Timestamp of the last failed login attempt
- `account_locked_until` (DATETIME) - Timestamp when the account lock expires

### Login Flow with Lockout

```
Resident Attempts Login
        ↓
Is Account Locked?
    ├─ YES: Check if lock period expired
    │   ├─ NOT YET: Display error with remaining minutes
    │   │   └─ Resident must wait or contact admin
    │   └─ YES: Proceed with login attempt
    └─ NO: Proceed with login attempt
        ↓
Password Verification
    ├─ CORRECT: 
    │   ├─ Reset failed attempt counter to 0
    │   ├─ Clear lock timestamp
    │   └─ Login successful → Dashboard
    └─ INCORRECT:
        ├─ Increment failed_login_attempts
        ├─ Update last_failed_login timestamp
        └─ If failed_login_attempts >= 5:
            └─ Set account_locked_until to NOW + 20 minutes
            └─ Display lockout message with remaining time
```

### User Experience - Account Lockout

When an account is locked, the resident sees:
- **Error Message**: "Account Locked - Your account has been temporarily locked for X minutes due to multiple failed login attempts."
- **Guidance**: "Please try again later or contact your barangay health center administrator."
- **Option**: "Back to Home" button

### Access Code Reference
- **File**: `includes/auth.php` - `loginUser()` function
- **Implementation**: Lines 107-220
- **Key Logic**:
  - Minutes remaining = (account_locked_until - current_time) / 60
  - Lockout triggered when `failed_login_attempts >= 5`
  - Lock duration: 20 minutes

---

## 2. ADMIN PASSWORD RESET (ONLY METHOD TO UNLOCK)

### Overview
Only the Super Admin can reset resident passwords. This is the exclusive method for:
- Recovering a locked account without waiting
- Helping residents who forgot their password
- Resetting passwords for any resident account

### How It Works

#### Admin Access
1. Super admin goes to **Admin Dashboard** → **Manage Accounts**
2. Locates the resident account
3. Clicks **"Reset Password"** button

#### Password Reset Process
1. Admin enters new password
2. Admin enters confirmation password
3. System validates:
   - Password is at least 6 characters
   - Passwords match
4. On success:
   - Hash password using `password_hash()`
   - **Automatically unlock account**:
     - `failed_login_attempts = 0`
     - `last_failed_login = NULL`
     - `account_locked_until = NULL`
   - Clear reset tokens:
     - `password_reset_token = NULL`
     - `password_reset_token_expires = NULL`
   - Display success message with new password
   - Log action: `action='password_reset'`

### Key Features
- **No email required**: Admin resets directly
- **Instant action**: Password changes immediately
- **Automatic unlock**: Account is unlocked automatically
- **Controlled process**: Only authorized admin can perform resets

### Database Update
**File**: `/admin/manage_accounts.php` (lines 391-442)
```sql
UPDATE sitio1_users 
SET password = ?,
    failed_login_attempts = 0,
    last_failed_login = NULL,
    account_locked_until = NULL,
    password_reset_token = NULL,
    password_reset_token_expires = NULL,
    updated_at = NOW()
WHERE id = ? AND role = 'patient'
```

### Activity Logging
Admin action is logged in `sitio1_activity_log`:
- **Action**: `password_reset`
- **Details**: "Reset password for resident: [NAME] (Account unlocked automatically)"
- **IP Address**: Recorded for audit trail
- **Timestamp**: When the reset occurred

---

## 3. WORKFLOW SCENARIOS

### Scenario 1: Resident Locked Out, Waits 20 Minutes
```
Time 0:00  → 5th failed login attempt → Account locked until 0:20
Time 0:15  → Resident tries to login → "Account locked for 5 minutes"
Time 0:20  → Account automatically unlocked
Time 0:21  → Resident logs in successfully with correct password
```

### Scenario 2: Resident Locked Out, Contacts Admin
```
Time 0:00  → 5th failed attempt → Account locked, failed_login_attempts=5
Time 0:05  → Resident contacts Barangay Health Center
Time 0:06  → Admin navigates to Manage Accounts
Time 0:07  → Admin clicks "Reset Password" for resident
Time 0:08  → Admin enters new password
Time 0:09  → System updates:
           - password → NEW_HASHED_PASSWORD
           - failed_login_attempts → 0
           - account_locked_until → NULL
Time 0:10  → Resident logs in immediately with new password (WITHOUT waiting)
```

### Scenario 3: Resident Forgot Password
```
Time ANY   → Resident cannot remember their password
           → Contact Barangay Health Center admin
Admin resets password immediately → Resident can login with new password
```

---

## 4. DATABASE SCHEMA

### Existing Lockout Fields in `sitio1_users`

```sql
failed_login_attempts INT DEFAULT 0
last_failed_login DATETIME DEFAULT NULL
account_locked_until DATETIME DEFAULT NULL
```

### Activity Logging Table

```sql
CREATE TABLE IF NOT EXISTS sitio1_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## 5. SECURITY BEST PRACTICES

### 1. Password Security
- Passwords hashed with `PASSWORD_DEFAULT` algorithm (bcrypt)
- Only admin can set/reset passwords
- Passwords minimum 6 characters in system (enforce stronger passwords as needed)
- Passwords never stored in plaintext

### 2. Activity Tracking
- All password resets logged with IP address and admin details
- All failed logins tracked for security monitoring
- Admin actions auditable for compliance

### 3. Account Protection
- Lockout prevents brute force attacks against accounts
- Failed attempts auto-reset after 15 minutes of inactivity
- Admin can immediately unlock accounts when needed
- Only authorized admins have access to password reset

### 4. Access Control
- Password reset only available in admin panel
- Requires admin login and authentication
- No self-service password recovery
- Maintains security and control

---

## 6. TESTING CHECKLIST

### Test Account Lockout
- [ ] 5 failed login attempts → Account locks
- [ ] Error message shows remaining minutes
- [ ] Wait 20 minutes → Account auto-unlocks
- [ ] After 15 minutes of no attempts → Counter resets
- [ ] Successful login → Resets counter and clears lockout

### Test Admin Password Reset
- [ ] Admin accesses Manage Accounts → Password reset form visible
- [ ] Admin enters mismatched passwords → Error message
- [ ] Admin resets password → Resident can login immediately
- [ ] Admin resets locked account → Account unlock confirmed
- [ ] Log entries created → Activity tracked with admin info

---

## 7. USER GUIDES

### For Residents
1. **Locked Account**: 
   - Wait 20 minutes for automatic unlock, OR
   - Contact Barangay Health Center to request password reset
2. **Forgot Password**: 
   - Contact Barangay Health Center admin
   - Admin will reset your password immediately
3. **Contact Information**:
   - Barangay Luz Health Center
   - Phone: (032) 123-4567
   - Mobile: 0917-123-4567
   - Email: healthcenter@barangayluz.gov.ph

### For Super Admin
1. Go to **Admin Dashboard**
2. Click **"Manage Accounts"**
3. Find resident account
4. Click **"Reset Password"** button
5. Enter new password and confirmation
6. Account will be unlocked automatically
7. Provide new password to resident securely

---

## 8. FILES MODIFIED

### Modified Files
- `/config/database.php` - Database schema with lockout fields
- `/admin/manage_accounts.php` - Resident password reset functionality with auto-unlock
- `/index.php` - Login modal (no forgot password link)
- `/includes/auth.php` - Account lockout logic

---

## 9. ERROR HANDLING

### Common Errors Handled
| Scenario | Error Message | User Action |
|----------|--------------|-------------|
| Account locked | "Account locked for X minutes" | Wait 20 min or contact admin |
| Wrong password | "Invalid username or password" | Check credentials or contact admin |
| Account not approved | "Account pending approval" | Wait for admin approval |
| Passwords don't match | "Passwords do not match" | Re-enter matching passwords |
| Password too short | "Password must be 6+ characters" | Enter longer password |

---

## 10. SUPPORT & TROUBLESHOOTING

### Resident Can't Login
1. **Is account locked?**
   - If yes: Wait 20 minutes OR contact admin for immediate reset
2. **Forgot password?**
   - Contact Barangay Health Center admin for password reset
3. **Account not approved?**
   - Contact Barangay Health Center admin

### Admin Password Reset Not Working
1. Verify resident account ID is correct
2. Check password meets minimum requirements (6 characters)
3. Ensure passwords match in form
4. Check admin has permissions (must be super admin)
5. Verify resident account exists and is approved

---

## 11. SUMMARY

| Scenario | Method | Duration | Result |
|----------|--------|----------|--------|
| Account Locked | Wait | 20 minutes | Auto-unlock |
| Account Locked | Admin Reset | Immediate | Instant unlock |
| Forgot Password | Admin Reset | Immediate | Instant reset |

This streamlined system provides a single, secure method for password management and account unlock through the Super Admin, ensuring proper authorization and control over all account access changes.


---

## 1. ACCOUNT LOCKOUT MECHANISM

### How It Works

#### Failed Login Attempts
- **Tracking**: System tracks failed login attempts per resident
- **Duration**: Failed attempts reset if no login attempts occur for 15 minutes
- **Lockout Threshold**: Account locks after 5 consecutive failed attempts
- **Lockout Duration**: Account remains locked for 20 minutes

#### Database Fields
The `sitio1_users` table stores:
- `failed_login_attempts` (INT) - Count of consecutive failed login attempts
- `last_failed_login` (DATETIME) - Timestamp of the last failed login attempt
- `account_locked_until` (DATETIME) - Timestamp when the account lock expires

### Login Flow with Lockout

```
Resident Attempts Login
        ↓
Is Account Locked?
    ├─ YES: Check if lock period expired
    │   ├─ NOT YET: Display error with remaining minutes
    │   │   └─ Resident waits and retries
    │   └─ YES: Proceed with login attempt
    └─ NO: Proceed with login attempt
        ↓
Password Verification
    ├─ CORRECT: 
    │   ├─ Reset failed attempt counter to 0
    │   ├─ Clear lock timestamp
    │   └─ Login successful → Dashboard
    └─ INCORRECT:
        ├─ Increment failed_login_attempts
        ├─ Update last_failed_login timestamp
        └─ If failed_login_attempts >= 5:
            └─ Set account_locked_until to NOW + 20 minutes
            └─ Display lockout message with remaining time
```

### User Experience - Account Lockout

When an account is locked, the resident sees:
- **Error Message**: "Account Locked - Your account has been temporarily locked for X minutes due to multiple failed login attempts."
- **Guidance**: "Please try again later or contact your barangay health center administrator."
- **Option**: "Back to Home" button

### Access Code Reference
- **File**: `includes/auth.php` - `loginUser()` function
- **Implementation**: Lines 107-220
- **Key Logic**:
  - Minutes remaining = (account_locked_until - current_time) / 60
  - Lockout triggered when `failed_login_attempts >= 5`
  - Lock duration: 20 minutes

---

## 2. ADMIN PASSWORD RESET (ONLY METHOD TO UNLOCK)

### Overview
Super admin can reset any resident's password directly from the admin panel. This automatically unlocks their account even if it's currently in lockout.

### How It Works

#### Admin Access
1. Super admin goes to **Admin Dashboard** → **Manage Accounts**
2. Locates the resident account
3. Clicks **"Reset Password"** button

#### Password Reset Process
1. Admin enters new password
2. Admin enters confirmation password
3. System validates:
   - Password is at least 6 characters
   - Passwords match
4. On success:
   - Hash password using `password_hash()`
   - **Automatically unlock account**:
     - `failed_login_attempts = 0`
     - `last_failed_login = NULL`
     - `account_locked_until = NULL`
   - Clear reset tokens:
     - `password_reset_token = NULL`
     - `password_reset_token_expires = NULL`
   - Display success message with new password
   - Log action: `action='password_reset'`

---

## 4. DATABASE SCHEMA