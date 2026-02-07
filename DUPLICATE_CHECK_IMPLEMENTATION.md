# Duplicate Patient Record Check - Implementation Summary

## Overview
A comprehensive duplicate check function has been added to prevent adding duplicate patient records to the system. The implementation checks if a patient with the same full name and date of birth already exists before creating a new record.

## Changes Made

### 1. New Function: `checkDuplicatePatient()`

**Location:** [staff/existing_info_patients.php](staff/existing_info_patients.php#L281)

**Purpose:** Checks if a patient record already exists in the database

**Parameters:**
- `$pdo` (PDO): Database connection object
- `$fullName` (string): Patient's full name
- `$dateOfBirth` (string): Patient's date of birth in YYYY-MM-DD format
- `$staffId` (int|null): Current staff member's ID
- `$isStaffViewAll` (bool): Whether staff can view all records across all added_by entries

**Returns:**
- **Array** with existing patient data (id, full_name, date_of_birth, contact) if patient found
- **False** if no duplicate exists

**Logic:**
```
1. If staff_can_view_all() is true:
   - Check ALL patient records regardless of who added them
   
2. If staff_can_view_all() is false:
   - Check only records added by the current staff member
   
3. Query compares:
   - Full name (case-insensitive, trimmed)
   - Date of birth (exact match)
   - Excludes soft-deleted records (deleted_at IS NULL)
```

### 2. Integration with Add Patient Form

**Location:** [staff/existing_info_patients.php](staff/existing_info_patients.php#L336)

**Implementation:**
```php
// Check if patient already exists
$existingPatient = checkDuplicatePatient($pdo, $fullName, $dateOfBirth, $_SESSION['user']['id'], staff_can_view_all());

if ($existingPatient) {
    // Patient already exists - show error with existing patient details
    $error = "This patient record already exists! ...";
} else {
    // Proceed with adding new patient
    // ... transaction code ...
}
```

## User Experience

### When Duplicate Detected:
1. **Error Message Displayed:**
   - Shows patient's full name
   - Displays date of birth (formatted as MMM DD, YYYY)
   - Shows contact information
   - Provides clickable link to view existing patient record

2. **Link to Existing Record:**
   - User can click "Click here to view this patient record"
   - Opens modal with existing patient's complete information
   - Allows staff to update existing record instead of creating duplicate

### When No Duplicate Found:
- Patient record proceeds to be created normally
- Success message displayed
- User redirected to patient records list

## Database Queries

### Query Used (Staff Can View All):
```sql
SELECT id, full_name, date_of_birth, contact FROM sitio1_patients 
WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
AND date_of_birth = ? 
AND deleted_at IS NULL
LIMIT 1
```

### Query Used (Staff View Own Only):
```sql
SELECT id, full_name, date_of_birth, contact FROM sitio1_patients 
WHERE LOWER(TRIM(full_name)) = LOWER(TRIM(?)) 
AND date_of_birth = ? 
AND added_by = ?
AND deleted_at IS NULL
LIMIT 1
```

## Key Features

✅ **Case-Insensitive Matching** - "John Smith" = "john smith"
✅ **Whitespace Trimming** - Handles extra spaces
✅ **Soft Delete Aware** - Excludes archived/deleted records
✅ **Permission Aware** - Respects staff_can_view_all() setting
✅ **Error Handling** - Try-catch block with error logging
✅ **User-Friendly** - Clear error messages with actionable options
✅ **Link to Existing Record** - Direct access to duplicate patient info

## Testing Recommendations

1. **Test Case 1: Exact Duplicate**
   - Add patient: "John Doe" (2000-01-01)
   - Try to add same patient again
   - ✓ Should show error message

2. **Test Case 2: Case-Insensitive**
   - Add patient: "john doe" (2000-01-01)
   - Try to add: "JOHN DOE" (2000-01-01)
   - ✓ Should show error message

3. **Test Case 3: Different DOB**
   - Add patient: "John Doe" (2000-01-01)
   - Try to add: "John Doe" (2000-01-02)
   - ✓ Should allow (different person)

4. **Test Case 4: Name with Extra Spaces**
   - Add patient: "John Doe" (2000-01-01)
   - Try to add: "John  Doe" (2000-01-01) with extra spaces
   - ✓ Should show error message

5. **Test Case 5: Archived Patient**
   - Add patient: "John Doe" (2000-01-01)
   - Archive that patient
   - Try to add: "John Doe" (2000-01-01) again
   - ✓ Should allow (original is archived/deleted)

## Error Handling

- If database query fails, function returns `false`
- Error logged to PHP error log for debugging
- Patient addition proceeds normally if check fails (doesn't block valid entries)
- No SQL injection risks (uses prepared statements)

## Files Modified

- `staff/existing_info_patients.php` - Added duplicate check function and integrated into form submission

## Function Definition Lines

- Function Definition: ~Line 281-310
- Function Integration: ~Line 336-365
- Error Message Display: ~Line 361-365

## Notes

- The duplicate check uses both full name AND date of birth to ensure accuracy
- This prevents accidental duplicate entries while allowing for same names with different DOBs
- The function respects the system's permission structure (staff_can_view_all setting)
- Archived/soft-deleted records are properly excluded from duplicate detection

