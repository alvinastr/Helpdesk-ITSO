# PowerShell Script untuk Download CDN Assets
# Jalankan di PowerShell as Administrator dari folder root project

param(
    [string]$ProjectPath = "C:\xampp\htdocs\helpdesk-itso"
)

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Download CDN Assets untuk Local Network" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Change to project directory
if (Test-Path $ProjectPath) {
    Set-Location $ProjectPath
    Write-Host "✓ Project path: $ProjectPath" -ForegroundColor Green
} else {
    Write-Host "✗ Project path tidak ditemukan: $ProjectPath" -ForegroundColor Red
    exit 1
}

# Create vendor directories
Write-Host ""
Write-Host "[1/7] Membuat folder vendor..." -ForegroundColor Yellow
$folders = @(
    "public\vendor\bootstrap",
    "public\vendor\fontawesome\webfonts",
    "public\vendor\chartjs",
    "public\vendor\fonts"
)

foreach ($folder in $folders) {
    New-Item -ItemType Directory -Force -Path $folder | Out-Null
    Write-Host "  ✓ $folder" -ForegroundColor Green
}

# Download Bootstrap
Write-Host ""
Write-Host "[2/7] Downloading Bootstrap..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" -OutFile "public\vendor\bootstrap\bootstrap.min.css"
    Write-Host "  ✓ Bootstrap CSS" -ForegroundColor Green
    
    Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" -OutFile "public\vendor\bootstrap\bootstrap.bundle.min.js"
    Write-Host "  ✓ Bootstrap JS" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error downloading Bootstrap: $_" -ForegroundColor Red
}

# Download Font Awesome CSS
Write-Host ""
Write-Host "[3/7] Downloading Font Awesome..." -ForegroundColor Yellow
try {
    $faCSS = Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    # Fix font paths in CSS
    $cssContent = $faCSS.Content -replace '\.\./webfonts/', './webfonts/'
    Set-Content -Path "public\vendor\fontawesome\all.min.css" -Value $cssContent
    Write-Host "  ✓ Font Awesome CSS" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error downloading Font Awesome CSS: $_" -ForegroundColor Red
}

# Download Font Awesome Fonts
Write-Host ""
Write-Host "[4/7] Downloading Font Awesome Fonts..." -ForegroundColor Yellow
$faFonts = @(
    "fa-solid-900.woff2",
    "fa-solid-900.ttf",
    "fa-regular-400.woff2",
    "fa-regular-400.ttf",
    "fa-brands-400.woff2",
    "fa-brands-400.ttf"
)

foreach ($font in $faFonts) {
    try {
        Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/$font" -OutFile "public\vendor\fontawesome\webfonts\$font"
        Write-Host "  ✓ $font" -ForegroundColor Green
    } catch {
        Write-Host "  ✗ $font (optional)" -ForegroundColor Yellow
    }
}

# Download Chart.js
Write-Host ""
Write-Host "[5/7] Downloading Chart.js..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" -OutFile "public\vendor\chartjs\chart.umd.min.js"
    Write-Host "  ✓ Chart.js" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error downloading Chart.js: $_" -ForegroundColor Red
}

# Download Axios
Write-Host ""
Write-Host "[6/7] Downloading Axios..." -ForegroundColor Yellow
try {
    Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/axios@1.6.0/dist/axios.min.js" -OutFile "public\vendor\axios.min.js"
    Write-Host "  ✓ Axios" -ForegroundColor Green
} catch {
    Write-Host "  ✗ Error downloading Axios: $_" -ForegroundColor Red
}

# Download Fonts (optional)
Write-Host ""
Write-Host "[7/7] Downloading Fonts..." -ForegroundColor Yellow
try {
    # Download Nunito font
    Invoke-WebRequest -Uri "https://fonts.bunny.net/css?family=Nunito:400,600,700" -OutFile "public\vendor\fonts\nunito.css"
    Write-Host "  ✓ Nunito font CSS" -ForegroundColor Green
} catch {
    Write-Host "  ⚠ Font download skipped (optional)" -ForegroundColor Yellow
}

# Summary
Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Download Selesai!" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Assets tersimpan di:" -ForegroundColor White
Write-Host "  - public\vendor\bootstrap\" -ForegroundColor Gray
Write-Host "  - public\vendor\fontawesome\" -ForegroundColor Gray
Write-Host "  - public\vendor\chartjs\" -ForegroundColor Gray
Write-Host "  - public\vendor\fonts\" -ForegroundColor Gray
Write-Host ""
Write-Host "Langkah selanjutnya:" -ForegroundColor Yellow
Write-Host "1. Update layout file untuk menggunakan assets lokal" -ForegroundColor White
Write-Host "2. Ganti 'app.blade.php' dengan 'app-offline.blade.php'" -ForegroundColor White
Write-Host "3. Test aplikasi tanpa koneksi internet" -ForegroundColor White
Write-Host ""
