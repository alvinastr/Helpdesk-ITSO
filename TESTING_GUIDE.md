# Panduan Pengujian Sistem Helpdesk ITSO

## Ringkasan Flowchart
Berdasarkan flowchart yang diberikan, sistem memiliki alur sebagai berikut:
1. **Input Data** → Standardisasi Data
2. **Cek Data (Email/WA)** → Generate Ticket atau Update Thread
3. **Generate Ticket** → Validasi Sistem
4. **Validasi Sistem** → Pending Review atau Rejected
5. **Validasi Admin** → Open atau Rejected atau Request Revision
6. **Update Thread** → Update Status
7. **Issue Resolved** → Closed

## 1. Persiapan Environment Testing

### Setup Database Testing
```bash
# Copy .env untuk testing
cp .env .env.testing

# Setup database testing
php artisan migrate:fresh --seed --env=testing
```

### Konfigurasi .env.testing
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

MAIL_MAILER=log
QUEUE_CONNECTION=sync
```

## 2. Pengujian Manual berdasarkan Flowchart

### A. Testing Input Data & Standardisasi
1. **Via Web Interface (User)**
2. **Via Email Webhook**
3. **Via WhatsApp Webhook**
4. **Via API**

### B. Testing Generate Ticket Flow
1. **New Ticket Creation**
2. **Reply to Existing Ticket (Update Thread)**

### C. Testing Validation System
1. **Format Validation**
2. **Required Fields Check**
3. **Email/Phone Validation**

### D. Testing Admin Validation Flow
1. **Approve Ticket**
2. **Reject Ticket**
3. **Request Revision**

### E. Testing Status Updates
1. **Pending → Open**
2. **Open → In Progress**
3. **In Progress → Closed**
4. **Closed → Reopened**

## 3. Automated Testing

### Unit Tests
- Model Tests
- Service Tests
- Validation Tests

### Feature Tests
- Complete Workflow Tests
- API Tests
- Authentication Tests

### Integration Tests
- Email Integration
- WhatsApp Integration
- Database Integration

## 4. Test Data Requirements

### Users
- Regular User
- Admin User
- System User (for webhooks)

### Tickets
- Various statuses
- Different priorities
- Multiple categories

## 5. Monitoring & Logging

### Test Logs
- Application logs
- Email logs
- Database queries
- API responses