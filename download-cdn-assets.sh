#!/bin/bash
# Script untuk Download CDN Assets di macOS/Linux
# Jalankan di terminal dari folder root project

echo "================================================"
echo "   Download CDN Assets - Helpdesk ITSO"
echo "================================================"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ ERROR: File artisan tidak ditemukan!"
    echo "Pastikan Anda menjalankan script ini di folder root project."
    exit 1
fi

echo "✓ Project path: $(pwd)"
echo ""

# Create vendor directories
echo "[1/7] Membuat folder vendor..."
mkdir -p public/vendor/bootstrap
mkdir -p public/vendor/fontawesome/webfonts
mkdir -p public/vendor/chartjs
mkdir -p public/vendor/fonts
echo "  ✓ Folders created"

# Download Bootstrap
echo ""
echo "[2/7] Downloading Bootstrap..."
curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" \
     -o "public/vendor/bootstrap/bootstrap.min.css" 2>/dev/null
echo "  ✓ Bootstrap CSS"

curl -L "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" \
     -o "public/vendor/bootstrap/bootstrap.bundle.min.js" 2>/dev/null
echo "  ✓ Bootstrap JS"

# Download Font Awesome CSS
echo ""
echo "[3/7] Downloading Font Awesome CSS..."
curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" \
     -o "public/vendor/fontawesome/all.min.css.tmp" 2>/dev/null

# Fix font paths in CSS (change ../webfonts/ to ./webfonts/)
sed 's|../webfonts/|./webfonts/|g' public/vendor/fontawesome/all.min.css.tmp > public/vendor/fontawesome/all.min.css
rm public/vendor/fontawesome/all.min.css.tmp
echo "  ✓ Font Awesome CSS"

# Download Font Awesome Fonts
echo ""
echo "[4/7] Downloading Font Awesome Fonts..."
fonts=(
    "fa-solid-900.woff2"
    "fa-solid-900.ttf"
    "fa-regular-400.woff2"
    "fa-regular-400.ttf"
    "fa-brands-400.woff2"
    "fa-brands-400.ttf"
)

for font in "${fonts[@]}"; do
    curl -L "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/$font" \
         -o "public/vendor/fontawesome/webfonts/$font" 2>/dev/null && echo "  ✓ $font" || echo "  ⚠ $font (optional)"
done

# Download Chart.js
echo ""
echo "[5/7] Downloading Chart.js..."
curl -L "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" \
     -o "public/vendor/chartjs/chart.umd.min.js" 2>/dev/null
echo "  ✓ Chart.js"

# Download Axios
echo ""
echo "[6/7] Downloading Axios..."
curl -L "https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js" \
     -o "public/vendor/axios.min.js" 2>/dev/null
echo "  ✓ Axios"

# Download Fonts CSS
echo ""
echo "[7/7] Creating Fonts CSS..."
cat > public/vendor/fonts/nunito.css << 'EOF'
/* Nunito Font - Embedded */
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap');
EOF
echo "  ✓ Nunito font CSS"

# Summary
echo ""
echo "================================================"
echo "   Download Selesai!"
echo "================================================"
echo ""
echo "Assets tersimpan di:"
echo "  - public/vendor/bootstrap/"
echo "  - public/vendor/fontawesome/"
echo "  - public/vendor/chartjs/"
echo "  - public/vendor/fonts/"
echo ""

# Check total size
if command -v du &> /dev/null; then
    total_size=$(du -sh public/vendor 2>/dev/null | cut -f1)
    echo "Total size: $total_size"
    echo ""
fi

echo "📦 Cara copy ke Windows:"
echo "1. Zip folder 'public/vendor':"
echo "   cd public && zip -r vendor.zip vendor/"
echo ""
echo "2. Copy vendor.zip ke PC Windows"
echo ""
echo "3. Extract di: C:\\xampp\\htdocs\\helpdesk-itso\\public\\"
echo ""
echo "4. Jalankan: toggle-offline-mode.bat di PC Windows"
echo ""
