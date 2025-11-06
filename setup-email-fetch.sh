#!/bin/bash

# ============================================
# Email Auto-Fetch Setup Script
# ============================================

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📧 Email Auto-Fetch Setup"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

# Check PHP IMAP extension
echo "1️⃣  Checking PHP IMAP extension..."
if php -m | grep -q imap; then
    echo "   ✅ PHP IMAP extension is installed"
else
    echo "   ❌ PHP IMAP extension is NOT installed"
    echo ""
    echo "   Installing IMAP extension..."
    
    # Detect OS
    if [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        echo "   Detected macOS - using Homebrew"
        brew install php@8.4-imap || echo "   ⚠️  Failed to install. Install manually: brew install php@8.4-imap"
    elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        echo "   Detected Linux"
        sudo apt-get update
        sudo apt-get install -y php-imap || echo "   ⚠️  Failed to install. Install manually: sudo apt-get install php-imap"
    else
        echo "   ⚠️  Unknown OS. Please install php-imap manually"
    fi
fi

echo ""
echo "2️⃣  Checking .env configuration..."

if [ ! -f .env ]; then
    echo "   ❌ .env file not found!"
    echo "   Creating from .env.example..."
    cp .env.example .env
fi

# Check if IMAP config exists
if grep -q "IMAP_HOST" .env; then
    echo "   ✅ IMAP configuration found in .env"
else
    echo "   ⚠️  IMAP configuration not found in .env"
    echo "   Adding IMAP configuration..."
    echo "" >> .env
    cat .env.imap.example >> .env
    echo "   ✅ IMAP configuration added to .env"
    echo "   ⚠️  Please edit .env and set IMAP_* values!"
fi

echo ""
echo "3️⃣  Testing IMAP connection..."
php artisan imap:test

echo ""
echo "4️⃣  Setup instructions..."
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ Setup is complete!"
echo ""
echo "📝 Next steps:"
echo "   1. Edit .env and configure IMAP settings:"
echo "      - IMAP_HOST"
echo "      - IMAP_USERNAME"
echo "      - IMAP_PASSWORD (use App Password for Gmail)"
echo ""
echo "   2. Test the connection:"
echo "      php artisan imap:test"
echo ""
echo "   3. Test fetching emails:"
echo "      php artisan emails:fetch"
echo ""
echo "   4. Setup cron job:"
echo "      crontab -e"
echo "      Add: * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "   5. Read full documentation:"
echo "      cat DOC/EMAIL_AUTO_FETCH_GUIDE.md"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
