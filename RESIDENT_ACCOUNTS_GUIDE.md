# Resident Accounts - Quick Reference Guide

## Overview
The Resident Accounts tab displays all approved resident/patient accounts with search, filter, and view capabilities.

## Features at a Glance

### 🔍 Search
Search across:
- Full Name
- Username  
- Email
- Patient ID

**How to use:**
1. Enter search term in the search box
2. Click "Apply" button
3. Click "Reset" to clear search

### 📊 Sort/Filter
- **Newest First**: Shows most recently registered residents first (default)
- **Oldest First**: Shows earliest registered residents first

**How to use:**
1. Select sort order from dropdown
2. Click "Apply" button

### 👁️ View Resident Details
Click the "View" button next to any resident to see:
- Personal information (name, contact, age, etc.)
- Address details
- ID verification status
- Account timeline
- ID document preview (if available)

**Note:** The modal is read-only - no edits can be made

### 📄 Pagination
- Shows 10 residents per page
- Navigate using page numbers or arrow buttons
- Search and sort settings are maintained across pages

## Button Guide

| Button | Icon | Action |
|--------|------|--------|
| Apply | 🔽 | Applies search and filter |
| Reset | 🔄 | Clears all filters |
| View | 👁️ | Opens resident details modal |
| Close | ❌ | Closes the modal |
| View Original | 🔗 | Opens ID image in new tab |
| Zoom | 🔍 | Opens larger view of ID |

## Status Indicators

| Badge | Meaning |
|-------|---------|
| 🟢 Active | Approved and active account |
| Patient ID | Unique identifier (e.g., CHT123456) |

## Field Descriptions

### Personal Information
- **Full Name**: Legal name of the resident
- **Username**: Login username
- **Email**: Contact email address
- **Contact Number**: Phone number
- **Gender**: Male/Female
- **Age**: Current age
- **Date of Birth**: Birth date
- **Civil Status**: Marital status
- **Occupation**: Current job/profession

### Address Information
- **Complete Address**: Full street address
- **Sitio**: Area subdivision within the barangay

### ID Verification
- **ID Type**: Type of ID submitted
- **ID Verification**: Verified/Not Verified status
- **Verification Method**: How the account was verified
- **Verified At**: Date and time of verification

### Account Timeline
- **Account Created**: When the account was registered
- **Last Updated**: Most recent account update
- **Account Role**: User role (Patient)
- **Account Status**: Current status (Active)

## Tips for Staff Users

1. **Quick Search**: Use the Patient ID for fastest results
2. **Name Search**: Partial names work (e.g., "Juan" finds "Juan Dela Cruz")
3. **Date Sorting**: Use "Newest First" to see recent registrations
4. **ID Verification**: Green badge means verified, gray means not verified
5. **Image Quality**: Use "Zoom" button for better ID document visibility

## Common Tasks

### Find a specific resident
1. Go to "Resident Accounts" tab
2. Enter name or Patient ID in search box
3. Click "Apply"
4. Click "View" on the matching result

### View all recent registrations
1. Go to "Resident Accounts" tab
2. Select "Newest First" from sort dropdown
3. Click "Apply"
4. Browse the first page

### Check resident's contact information
1. Find the resident (using search or browse)
2. Click "View" button
3. Look under "Personal Information" section
4. Contact details are listed there

### Verify resident's ID status
1. Find the resident
2. Click "View" button
3. Check "ID Verification" section
4. View ID document if available

## Troubleshooting

**Q: Search returns no results**
- A: Check spelling, try partial name, or use Patient ID

**Q: ID image won't load**
- A: Image may not be uploaded or path may be incorrect

**Q: Can't edit resident information**
- A: This is read-only view by design - contact admin for edits

**Q: Pagination shows wrong page**
- A: Click "Reset" and navigate again

**Q: Sort not working**
- A: Make sure to click "Apply" after selecting sort option

## Access Requirements
- Must be logged in as **Staff** user
- Available at: `/staff/dashboard.php?tab=account-management`

## Related Features
- Analytics Dashboard (overview stats)
- Patient Records Management
- Account Approvals (for pending users)

---
**Last Updated**: February 2, 2026
**Version**: 1.0
