# 📱 Setup WhatsApp untuk Local Network

## Masalah: WhatsApp API Butuh Webhook dari Internet

WhatsApp Business API official dari Meta memerlukan webhook URL yang bisa diakses dari internet untuk menerima callback events (message received, delivery status, etc).

**Problem di local network:**
- Server hanya bisa diakses di local network (192.168.x.x)
- WhatsApp API tidak bisa kirim callback ke IP lokal
- Webhook verification akan fail

---

## 🎯 Solusi yang Tersedia

### Pilihan 1: Disable WhatsApp (PALING MUDAH) ⭐

**Kapan gunakan:**
- WhatsApp notification tidak critical
- Email notification sudah cukup
- In-app notification sudah cukup

**Setup:**

1. Edit `.env`:
```env
# WhatsApp Configuration - DISABLED
WHATSAPP_ENABLED=false
WHATSAPP_API_URL=
WHATSAPP_API_TOKEN=
WHATSAPP_FROM_NUMBER=
```

2. Clear cache:
```bash
php artisan config:clear
php artisan config:cache
```

3. Aplikasi akan skip WhatsApp notifications otomatis.

**Keuntungan:**
- ✅ Paling mudah
- ✅ Tidak butuh setup tambahan
- ✅ Aplikasi tetap jalan normal

**Kekurangan:**
- ❌ Tidak ada WhatsApp notification

---

### Pilihan 2: WhatsApp Web.js (LOCAL, FREE) ⭐⭐⭐

**Kapan gunakan:**
- Butuh WhatsApp notification
- Tidak mau bayar WhatsApp Business API
- Bisa install Node.js di server

**Cara Kerja:**
- Bot WhatsApp berjalan di server lokal
- Tidak butuh webhook dari internet
- Menggunakan WhatsApp Web protocol
- Connect via QR Code seperti WhatsApp Web

**Setup:**

#### Step 1: Install Node.js

Download dari: https://nodejs.org/
- Pilih LTS version (Long Term Support)
- Install dengan default settings

#### Step 2: Setup WhatsApp Bot

1. Buat folder di server:
```bash
mkdir C:\whatsapp-bot
cd C:\whatsapp-bot
```

2. Initialize project:
```bash
npm init -y
```

3. Install dependencies:
```bash
npm install whatsapp-web.js qrcode-terminal express body-parser
```

4. Buat file `server.js`:

```javascript
const { Client, LocalAuth } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const express = require('express');
const bodyParser = require('body-parser');

const app = express();
app.use(bodyParser.json());

// WhatsApp Client
const client = new Client({
    authStrategy: new LocalAuth(),
    puppeteer: {
        args: ['--no-sandbox'],
    }
});

// QR Code untuk autentikasi
client.on('qr', (qr) => {
    console.log('');
    console.log('======================================');
    console.log('Scan QR Code ini dengan WhatsApp Anda:');
    console.log('======================================');
    qrcode.generate(qr, {small: true});
    console.log('');
    console.log('Cara scan:');
    console.log('1. Buka WhatsApp di HP');
    console.log('2. Tap Menu > Linked Devices');
    console.log('3. Tap Link a Device');
    console.log('4. Scan QR code di atas');
    console.log('======================================');
});

// Client ready
client.on('ready', () => {
    console.log('✅ WhatsApp Bot siap digunakan!');
    console.log('🌐 API Server: http://localhost:3000');
});

// Handle auth failures
client.on('auth_failure', msg => {
    console.error('❌ Authentication gagal:', msg);
});

// Handle disconnects
client.on('disconnected', (reason) => {
    console.log('⚠️ WhatsApp terputus:', reason);
});

// Initialize client
client.initialize();

// API Endpoint untuk Laravel
app.post('/send', async (req, res) => {
    try {
        const { phone, message } = req.body;
        
        if (!phone || !message) {
            return res.status(400).json({
                success: false,
                error: 'Phone dan message wajib diisi'
            });
        }
        
        // Format phone number (remove non-digits)
        const number = phone.replace(/[^0-9]/g, '');
        
        // Add country code if not exists (Indonesia = 62)
        let formattedNumber = number;
        if (!formattedNumber.startsWith('62')) {
            if (formattedNumber.startsWith('0')) {
                formattedNumber = '62' + formattedNumber.substring(1);
            } else {
                formattedNumber = '62' + formattedNumber;
            }
        }
        
        // Send message
        const chatId = `${formattedNumber}@c.us`;
        await client.sendMessage(chatId, message);
        
        res.json({
            success: true,
            message: 'Pesan berhasil dikirim',
            to: formattedNumber
        });
        
    } catch (error) {
        console.error('Error sending message:', error);
        res.status(500).json({
            success: false,
            error: error.message
        });
    }
});

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        whatsapp: client.info ? 'connected' : 'disconnected'
    });
});

// Start server
const PORT = 3000;
app.listen(PORT, '0.0.0.0', () => {
    console.log('');
    console.log('======================================');
    console.log('🚀 WhatsApp API Server Running');
    console.log(`📡 Listening on: http://0.0.0.0:${PORT}`);
    console.log('======================================');
    console.log('');
    console.log('Tunggu QR Code untuk autentikasi...');
});
```

5. Buat file `start-whatsapp-bot.bat`:

```batch
@echo off
echo Starting WhatsApp Bot...
cd C:\whatsapp-bot
node server.js
pause
```

#### Step 3: Jalankan Bot

1. Double-click `start-whatsapp-bot.bat`
2. QR Code akan muncul di console
3. Scan dengan WhatsApp di HP Anda
4. Bot akan auto-login untuk seterusnya

#### Step 4: Update Laravel Config

Edit `.env`:
```env
# WhatsApp Configuration - Local Bot
WHATSAPP_ENABLED=true
WHATSAPP_API_URL=http://192.168.1.100:3000/send
WHATSAPP_API_TOKEN=
```

**Notes:**
- Ganti `192.168.1.100` dengan IP server Anda
- Port 3000 harus dibuka di firewall
- Bot harus running 24/7 untuk terima/kirim pesan

**Keuntungan:**
- ✅ FREE (no cost)
- ✅ Berjalan di local network
- ✅ Tidak butuh internet untuk send message
- ✅ Sama seperti WhatsApp Web

**Kekurangan:**
- ❌ Butuh install Node.js
- ❌ Harus scan QR code first time
- ❌ Harus running 24/7

---

### Pilihan 3: Ngrok Tunnel (TEMPORARY)

**Kapan gunakan:**
- Testing WhatsApp Business API official
- Temporary solution
- Tidak mau setup Node.js

**Cara Kerja:**
- Ngrok membuat tunnel dari internet ke local server
- Dapat public URL yang bisa diakses WhatsApp API
- Free tier limited

**Setup:**

1. Download Ngrok: https://ngrok.com/download
2. Install dan jalankan:
```bash
ngrok http 192.168.1.100:8000
```
3. Akan dapat URL seperti: `https://abc123.ngrok.io`
4. Gunakan URL ini untuk webhook di Meta Developer

**Keuntungan:**
- ✅ Mudah setup
- ✅ Bisa pakai official WhatsApp Business API
- ✅ Tidak butuh Node.js

**Kekurangan:**
- ❌ Butuh internet connection
- ❌ Free tier limited (2 hours session)
- ❌ URL berubah setiap restart
- ❌ Not suitable untuk production

---

### Pilihan 4: VPS dengan VPN/Tunnel (ADVANCED)

**Kapan gunakan:**
- Production environment
- Butuh official WhatsApp Business API
- Ada budget untuk VPS

**Cara Kerja:**
- Setup VPS kecil di cloud (DigitalOcean, etc)
- VPS sebagai proxy/tunnel ke local server
- WhatsApp API kirim webhook ke VPS
- VPS forward request ke local server via VPN/Tunnel

**Setup:**
Too complex untuk dijelaskan di sini. Butuh knowledge:
- VPS management
- VPN/Tunnel (WireGuard, Tailscale, etc)
- Reverse proxy (Nginx)

---

## 📊 Comparison Table

| Solusi | Cost | Complexity | Internet Needed | Recommended |
|--------|------|------------|-----------------|-------------|
| **Disable WhatsApp** | Free | ⭐ Very Easy | No | ✅ Yes - if not critical |
| **WhatsApp Web.js** | Free | ⭐⭐ Medium | No (after setup) | ✅ Yes - best for local |
| **Ngrok** | Free (limited) | ⭐⭐ Easy | Yes | ⚠️ Only for testing |
| **VPS + VPN** | ~$5/mo | ⭐⭐⭐⭐ Hard | Yes | ⚠️ Only if necessary |

---

## 💡 Rekomendasi Saya

**Untuk deployment Anda (local network, PC Windows):**

### Skenario 1: WhatsApp Tidak Critical
→ **Gunakan Pilihan 1 (Disable WhatsApp)**
- Setting paling sederhana
- Aplikasi tetap fully functional
- Pakai email notification sebagai gantinya

### Skenario 2: WhatsApp Penting
→ **Gunakan Pilihan 2 (WhatsApp Web.js)**
- FREE dan berjalan lokal
- Mudah maintain
- Reliable untuk production

---

## 🔧 Implementation

Mau saya buatkan script dan code untuk setup mana?

1. ⚡ Disable WhatsApp (fastest)
2. 🤖 Setup WhatsApp Web.js Bot
3. 🌐 Setup Ngrok Tunnel

Beri tahu pilihan Anda!
