#!/bin/bash

# Manual Testing Script untuk Sistem Helpdesk ITSO
# Berdasarkan flowchart yang diberikan

echo "🔧 HELPDESK ITSO - Manual Testing Script"
echo "========================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_step() {
    echo -e "${BLUE}$1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if Laravel is running
check_server() {
    print_step "Checking if Laravel server is running..."
    if curl -s http://localhost:8000 > /dev/null; then
        print_success "Laravel server is running on http://localhost:8000"
    else
        print_warning "Laravel server is not running. Starting server..."
        php artisan serve --port=8000 &
        sleep 3
        if curl -s http://localhost:8000 > /dev/null; then
            print_success "Laravel server started successfully"
        else
            print_error "Failed to start Laravel server"
            exit 1
        fi
    fi
}

# Setup database
setup_database() {
    print_step "Setting up database..."
    php artisan migrate:fresh --force
    php artisan db:seed --force
    print_success "Database setup completed"
}

# Test API endpoints
test_api_endpoints() {
    print_step "Testing API Endpoints..."
    
    # Test webhook endpoints
    echo "Testing Email Webhook..."
    curl -X POST http://localhost:8000/api/v1/webhooks/email \
        -H "Content-Type: application/json" \
        -d '{
            "from": "test@example.com",
            "subject": "Test Email Ticket",
            "body": "This is a test ticket from email webhook",
            "message_id": "email-test-001"
        }' \
        -w "\nHTTP Status: %{http_code}\n"
    
    echo "Testing WhatsApp Webhook..."
    curl -X POST http://localhost:8000/api/v1/webhooks/whatsapp \
        -H "Content-Type: application/json" \
        -d '{
            "from": "+6281234567890",
            "message": "Help! My laptop is broken.",
            "message_id": "wa-test-001"
        }' \
        -w "\nHTTP Status: %{http_code}\n"
    
    echo "Testing Ticket API..."
    curl -X GET http://localhost:8000/api/v1/tickets \
        -H "Accept: application/json" \
        -w "\nHTTP Status: %{http_code}\n"
}

# Create test data
create_test_data() {
    print_step "Creating test data using artisan commands..."
    
    # Create admin user
    php artisan tinker --execute="
        \$admin = App\Models\User::create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin'
        ]);
        echo 'Admin user created: ' . \$admin->email . PHP_EOL;
    "
    
    # Create regular user
    php artisan tinker --execute="
        \$user = App\Models\User::create([
            'name' => 'User Test',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'user'
        ]);
        echo 'Regular user created: ' . \$user->email . PHP_EOL;
    "
    
    # Create sample tickets
    php artisan tinker --execute="
        \$user = App\Models\User::where('email', 'user@test.com')->first();
        \$ticket = App\Models\Ticket::create([
            'ticket_number' => 'TCK-' . date('Ymd') . '-001',
            'user_id' => \$user->id,
            'user_name' => \$user->name,
            'user_email' => \$user->email,
            'user_phone' => '081234567890',
            'subject' => 'Test Ticket - Komputer Rusak',
            'description' => 'Komputer saya tidak bisa menyala setelah pemadaman listrik kemarin.',
            'category' => 'hardware',
            'priority' => 'medium',
            'status' => 'pending_review',
            'channel' => 'web',
            'input_method' => 'manual'
        ]);
        echo 'Sample ticket created: ' . \$ticket->ticket_number . PHP_EOL;
    "
}

# Test flowchart scenarios
test_flowchart_scenarios() {
    print_step "Testing Flowchart Scenarios..."
    
    echo "📋 Scenario 1: Complete ticket workflow (New Ticket → Approved → Closed)"
    echo "   1. User creates ticket (INPUT DATA)"
    echo "   2. System standardizes data (STANDARDISASI DATA)"
    echo "   3. System validates (VALIDASI SISTEM)"
    echo "   4. Admin approves (VALIDASI ADMIN)"
    echo "   5. Ticket is opened and worked on"
    echo "   6. Issue resolved and ticket closed"
    echo ""
    
    echo "📋 Scenario 2: Ticket rejection workflow"
    echo "   1. User creates ticket with insufficient info"
    echo "   2. Admin rejects ticket"
    echo "   3. User receives rejection notification"
    echo ""
    
    echo "📋 Scenario 3: Reply/Update thread workflow"
    echo "   1. User replies to existing ticket"
    echo "   2. System updates thread (not new ticket)"
    echo "   3. Admin responds"
    echo ""
    
    echo "📋 Scenario 4: External input workflow"
    echo "   1. Email/WhatsApp creates ticket"
    echo "   2. System processes and creates ticket"
    echo "   3. Goes through normal approval flow"
}

# Display login credentials
show_credentials() {
    print_step "Login Credentials for Manual Testing:"
    echo "Admin Login:"
    echo "  Email: admin@test.com"
    echo "  Password: password"
    echo ""
    echo "User Login:"
    echo "  Email: user@test.com"
    echo "  Password: password"
    echo ""
    echo "Test URLs:"
    echo "  Main App: http://localhost:8000"
    echo "  Admin Dashboard: http://localhost:8000/admin/dashboard"
    echo "  User Dashboard: http://localhost:8000/dashboard"
}

# Menu function
show_menu() {
    echo ""
    echo "Select testing option:"
    echo "1. Setup Database & Create Test Data"
    echo "2. Test API Endpoints"
    echo "3. Run Automated Tests"
    echo "4. Show Login Credentials"
    echo "5. Test Flowchart Scenarios"
    echo "6. Full Testing Suite"
    echo "7. Exit"
}

# Main execution
main() {
    check_server
    
    while true; do
        show_menu
        read -p "Enter your choice (1-7): " choice
        
        case $choice in
            1)
                setup_database
                create_test_data
                print_success "Setup completed!"
                ;;
            2)
                test_api_endpoints
                ;;
            3)
                print_step "Running automated tests..."
                php artisan test
                ;;
            4)
                show_credentials
                ;;
            5)
                test_flowchart_scenarios
                ;;
            6)
                print_step "Running full testing suite..."
                setup_database
                create_test_data
                test_api_endpoints
                php artisan test
                show_credentials
                test_flowchart_scenarios
                print_success "Full testing suite completed!"
                ;;
            7)
                print_success "Testing script terminated. Happy testing!"
                exit 0
                ;;
            *)
                print_error "Invalid option. Please choose 1-7."
                ;;
        esac
        
        echo ""
        read -p "Press Enter to continue..."
    done
}

# Run main function
main