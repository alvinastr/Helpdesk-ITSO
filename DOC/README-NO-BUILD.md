# ITSO Helpdesk - Pure Laravel (No Build Tools)

This Laravel application has been configured to run without any build tools like Vite, Webpack, or Node.js. All assets are served via CDN or static files.

## ✅ What's Changed

### Removed Dependencies
- ❌ Node.js and npm
- ❌ Vite build system  
- ❌ package.json and vite.config.js
- ❌ All node_modules

### Asset Management
- ✅ **Bootstrap 5.3.2** - CDN from Bootstrap CDN
- ✅ **Font Awesome 6.4.0** - CDN from cdnjs
- ✅ **Axios** - CDN for AJAX requests
- ✅ **Custom CSS** - `/public/css/app.css`
- ✅ **Custom JS** - `/public/js/app.js`

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/alvinastr/Helpdesk-ITSO.git
   cd Helpdesk-ITSO
   ```

2. **Install PHP dependencies only**
   ```bash
   composer install
   ```

3. **Setup environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure database and run migrations**
   ```bash
   php artisan migrate --seed
   ```

5. **Start the development server**
   ```bash
   php artisan serve
   ```

## 📁 Asset Structure

```
public/
├── css/
│   └── app.css          # Custom CSS styles
├── js/
│   └── app.js           # Custom JavaScript
└── images/              # Static images

resources/views/layouts/
└── app-production.blade.php  # Main layout with CDN assets
```

## 🎨 Styling

All styling is handled through:

1. **Bootstrap 5.3.2 CDN** - Complete UI framework
2. **Font Awesome 6.4.0 CDN** - Icons
3. **Custom CSS** (`public/css/app.css`) - Application-specific styles

## 🔧 JavaScript

JavaScript functionality includes:

1. **Bootstrap Bundle CDN** - Bootstrap components
2. **Axios CDN** - HTTP requests
3. **Custom JS** (`public/js/app.js`) - Application-specific functionality

### Available JavaScript Utilities

```javascript
// Show toast notification
ITSO.showToast('Success message', 'success');

// Show loading state
ITSO.showLoading(buttonElement);

// Hide loading state
ITSO.hideLoading(buttonElement, 'Original Text');
```

## 🌐 Production Deployment

The application is ready for production without any build step:

```bash
# Run the deployment script
./deploy-production.sh
```

Or manually:

```bash
# 1. Upload files to server
# 2. Install composer dependencies
composer install --no-dev --optimize-autoloader

# 3. Setup environment
cp .env.production .env
php artisan key:generate --force

# 4. Run migrations
php artisan migrate --force

# 5. Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📝 File Changes Made

### Removed Files
- `vite.config.js`
- `package.json` 
- `package-lock.json`
- `node_modules/` directory
- `app/Helpers/AssetHelper.php`

### Modified Files

**Layout File:** `resources/views/layouts/app-production.blade.php`
- Removed `@vite` directives
- Added Bootstrap 5.3.2 CDN
- Added Font Awesome CDN
- Added Axios CDN
- Added custom asset links

**Environment:** `.env`
- Removed `VITE_ENABLED` configuration

**Config:** `config/app.php` 
- Removed `vite_enabled` configuration

## 🔄 Adding New Styles/Scripts

### Adding CSS
Edit `public/css/app.css` directly. Changes are immediately available.

### Adding JavaScript  
Edit `public/js/app.js` directly. Changes are immediately available.

### Using External Libraries
Add CDN links to `resources/views/layouts/app-production.blade.php`:

```html
<!-- In the <head> section -->
<link href="https://cdn.jsdelivr.net/npm/library@version/dist/library.min.css" rel="stylesheet">

<!-- Before closing </body> -->
<script src="https://cdn.jsdelivr.net/npm/library@version/dist/library.min.js"></script>
```

## 🎯 Benefits

- ✅ **Zero build time** - No compilation needed
- ✅ **No Node.js dependency** - Pure PHP environment
- ✅ **Fast deployment** - No build step required  
- ✅ **Simple hosting** - Works on any PHP hosting
- ✅ **Easy maintenance** - Direct file editing
- ✅ **CDN reliability** - Fast global asset delivery

## 🔧 Development Workflow

1. Edit PHP files directly
2. Edit CSS in `public/css/app.css`
3. Edit JS in `public/js/app.js`
4. Refresh browser to see changes
5. Deploy by uploading files

No build commands, no watching, no compilation!

## 🤝 Contributing

Since there's no build system:
1. Fork the repository
2. Make your changes directly to CSS/JS files
3. Test in browser
4. Submit pull request

## 📞 Support

For issues or questions about this setup, please create an issue in the repository.

---

**Helpdesk ITSO** - Pure Laravel, Zero Build Tools! 🚀