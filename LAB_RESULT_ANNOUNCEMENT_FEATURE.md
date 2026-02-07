# Laboratory Result Announcement Feature

## Overview
This feature adds the ability for staff to categorize announcements as either "Laboratory Result" or "Basic Announcement" when posting to specific users. Lab results are displayed separately with visual highlighting for easy identification.

## Implementation Steps

### 1. Database Schema Update
**File**: `Sql/add_announcement_category.sql`

Run this SQL script to add the `announcement_category` field to the database:
```sql
ALTER TABLE `sitio1_announcements` 
ADD COLUMN `announcement_category` ENUM('basic', 'lab_result') NOT NULL DEFAULT 'basic' 
AFTER `audience_type`;
```

### 2. Staff Announcement Form
**File**: `staff/announcements.php`

#### Changes Made:
- Added announcement category selection (Lab Result / Basic Announcement)
- Category selection appears only when "Specific Users" is selected as audience
- Two radio options with icons:
  - **Basic Announcement** (blue bullhorn icon) - For general updates
  - **Laboratory Result** (green flask icon) - For medical test results

#### Visual Features:
- Category selection appears below audience type
- Only visible when posting to specific users
- Clear icons and descriptions for each type

### 3. Save Logic Updates
**File**: `staff/announcements.php` (INSERT query)

#### Changes Made:
- Added `announcement_category` field to INSERT statement
- Captures category from POST data (defaults to 'basic')
- Success message indicates category type ("Laboratory Result sent..." or "Announcement sent...")

### 4. User Dashboard Display
**File**: `user/announcements.php`

#### Major Changes:

**Separated Queries:**
```php
// Announcements are now separated into two arrays:
$labResults = [];           // Laboratory results only
$basicAnnouncements = [];   // Basic announcements only
```

**Updated Statistics:**
- Total announcements count
- Lab Results count with pending count
- Basic Announcements count with pending count
- All pending count

**Separate Display Sections:**
1. **Laboratory Results Section**
   - Displayed first with green flask icon
   - Shows count in header
   - Visual highlighting (green gradient background, green border)
   - Enhanced shadow on hover
   - "View Result" button instead of generic "View"

2. **General Announcements Section**
   - Displayed second with blue bullhorn icon
   - Shows count in header
   - Standard white background
   - "View" button

**No Announcements State:**
- Shows empty state only when both sections are empty

### 5. Visual Highlighting

#### Lab Result Cards:
```css
.announcement-item.lab-result-highlight {
    background: linear-gradient(to right, #F0FDF4, #FFFFFF);
    border: 2px solid #10B981;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.15);
}

.announcement-item.lab-result-highlight:hover {
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
    transform: translateY(-2px);
}
```

**Visual Features:**
- Light green gradient background (#F0FDF4 to white)
- Green border (#10B981)
- Green shadow effect
- Enhanced shadow on hover
- Smooth transitions

### 6. Badge Display

**Lab Result Badge:**
```php
<span class="badge" style="background: #D1FAE5; color: #10B981; border: 1px solid #10B981;">
    <i class="fas fa-flask"></i>
    Laboratory Result
</span>
```

**Features:**
- Light green background
- Green text and icon
- Flask icon
- Border for emphasis

## User Experience Flow

### For Staff:
1. Create new announcement
2. Select "Specific Users" as audience
3. **New**: Choose announcement type (Lab Result or Basic)
4. Select target users
5. Post announcement
6. Receive confirmation with category type

### For Residents:
1. Open Announcements page
2. See updated statistics showing:
   - Total announcements
   - Lab Results count (with pending)
   - Basic Announcements count (with pending)
   - All pending count
3. **Lab Results Section** (if any):
   - Displayed first
   - **Highlighted in green** for easy identification
   - Green flask badge
   - "View Result" button
4. **General Announcements Section** (if any):
   - Displayed second
   - Standard white appearance
   - Blue bullhorn icon
   - "View" button

## Key Benefits

1. **Easy Identification**: Lab results stand out with green highlighting
2. **Organized Display**: Separate sections prevent confusion
3. **Clear Counts**: Users see exactly how many lab results vs announcements they have
4. **Priority Visibility**: Lab results displayed first as they're typically more urgent
5. **Consistent Design**: Matches existing health tracker design language
6. **Responsive**: Works on all screen sizes

## Files Modified

1. `Sql/add_announcement_category.sql` - New database migration
2. `staff/announcements.php` - Added category selection and save logic
3. `user/announcements.php` - Separated display with highlighting

## Database Changes

**Table**: `sitio1_announcements`
**New Column**: `announcement_category`
- Type: ENUM('basic', 'lab_result')
- Default: 'basic'
- Position: After `audience_type`

## Testing Checklist

- [ ] Run the SQL migration script
- [ ] Staff can see category selection when "Specific Users" is selected
- [ ] Category selection is hidden for other audience types
- [ ] Lab result announcements save with correct category
- [ ] Basic announcements save with correct category
- [ ] User dashboard separates lab results from basic announcements
- [ ] Lab results display with green highlighting
- [ ] Lab results show flask icon badge
- [ ] Counts display correctly for both types
- [ ] Empty state shows when no announcements exist
- [ ] Mobile responsive design works correctly

## Future Enhancements (Optional)

- Add notification badges for new lab results
- Email notifications specifically for lab results
- Ability to mark lab results as "viewed" separately
- Filter/search within each category
- Export lab results to PDF
- Archive read lab results separately

## Support

For issues or questions about this feature:
1. Check that the SQL migration was run successfully
2. Verify staff has permissions to create announcements
3. Check browser console for JavaScript errors
4. Verify database connection is working
