# Tailwind CSS Offline Setup Guide

## Overview
This project now uses Tailwind CSS with a fully offline-capable setup. All CSS is pre-built and stored locally, so the application works without internet connectivity.

## What Was Changed

### 1. Added Configuration Files
- **package.json** - Node.js package configuration with Tailwind CSS
- **tailwind.config.js** - Tailwind CSS configuration for your project
- **asssets/css/input.css** - Tailwind input file with custom styles

### 2. Generated Files
- **asssets/css/tailwind.css** - Pre-built Tailwind CSS (81KB) - This is your offline CSS file
  
### 3. Updated Headers
- Removed CDN dependencies (tailwindcss CDN, Font Awesome CDN)
- Updated all PHP header files to use local CSS files
- Files updated:
  - `includes/header.php`
  - `index.php`

## Installation & Setup

### First Time Setup (Development Machine)
```bash
# 1. Navigate to project directory
cd c:\xampp\htdocs\community-health-tracker

# 2. Install Node.js dependencies (if not already done)
npm install

# 3. Build the Tailwind CSS
npm run build:css
```

### Development Workflow
If you're making changes and want to rebuild CSS automatically:
```bash
npm run watch:css
```
This watches for changes and automatically rebuilds the CSS.

## Files Structure

```
community-health-tracker/
├── package.json                 (Node.js config)
├── tailwind.config.js           (Tailwind configuration)
├── asssets/
│   └── css/
│       ├── input.css            (Tailwind input - custom styles)
│       ├── tailwind.css         (Generated - offline CSS file)
│       ├── style.css            (Additional custom styles)
│       └── font-awesome.min.css (Local Font Awesome - needed)
└── [rest of project files]
```

## Important Notes

### Font Awesome Icons
You need to add Font Awesome locally for offline support. The system references:
- `<link rel="stylesheet" href="/community-health-tracker/asssets/css/font-awesome.min.css">`

### Production Deployment
When deploying to production:
1. The `tailwind.css` file is already built and ready
2. You only need the `asssets/css/tailwind.css` file (not the build tools)
3. You **do not need** to deploy `package.json`, `node_modules/`, or `tailwind.config.js` to the server
4. These build files are only needed for development

### CSS File Sizes
- **tailwind.css**: ~81KB (pre-built, includes all Tailwind utilities)
- This includes all Tailwind utilities, animations, and custom components

## How It Works

1. **Local Build**: Tailwind CSS is built locally from `input.css`
2. **Offline First**: All CSS is in a single local file
3. **No CDN**: No internet required for styling after deployment
4. **Custom Styles**: Add custom CSS in `asssets/css/input.css`

## To Add New Custom Classes

Edit `asssets/css/input.css` and add your custom styles:
```css
@layer components {
    .my-custom-class {
        @apply px-4 py-2 bg-blue-500 text-white rounded;
    }
}
```

Then rebuild:
```bash
npm run build:css
```

## Troubleshooting

### CSS Not Loading
- Check browser console for 404 errors
- Verify `/community-health-tracker/asssets/css/tailwind.css` exists
- Clear browser cache (Ctrl+Shift+Delete)

### Icons Not Showing
- Font Awesome CSS needs to be added to `asssets/css/`
- Request the Font Awesome local files setup

### Changes Not Appearing
- Run `npm run build:css` to rebuild
- Or use `npm run watch:css` for automatic rebuilding during development

## Next Steps

1. ✅ Tailwind CSS is set up for offline use
2. ⚠️ TODO: Add Font Awesome local files to `asssets/css/font-awesome.min.css`
3. ⚠️ TODO: Review and migrate remaining inline styles to Tailwind classes
4. ⚠️ TODO: Test all pages without internet connection

## Support

For more information on Tailwind CSS:
- Official Docs: https://tailwindcss.com/docs
- Configuration: https://tailwindcss.com/docs/configuration
