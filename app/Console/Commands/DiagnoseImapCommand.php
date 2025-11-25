<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DiagnoseImapCommand extends Command
{
    protected $signature = 'imap:diagnose';
    protected $description = 'Comprehensive IMAP connection diagnosis and troubleshooting';

    public function handle()
    {
        $this->info('🔍 IMAP Connection Diagnostic Tool');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Step 1: Check PHP IMAP extension
        $this->checkImapExtension();

        // Step 2: Check configuration
        $config = $this->checkConfiguration();
        
        if (!$config) {
            return Command::FAILURE;
        }

        // Step 3: Test network connectivity
        $this->testNetworkConnectivity($config['host'], $config['port']);

        // Step 4: Test IMAP connection with different methods
        $this->testImapConnection($config);

        $this->info("\n✨ Diagnostic completed!");
        
        return Command::SUCCESS;
    }

    protected function checkImapExtension()
    {
        $this->info("\n📦 Step 1: Checking PHP IMAP Extension");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
        
        if (!extension_loaded('imap')) {
            $this->error('❌ PHP IMAP extension is NOT loaded!');
            $this->warn("\n💡 Installation instructions:");
            $this->line("   Windows: Enable in php.ini - extension=imap");
            $this->line("   macOS: brew install imap-php");
            $this->line("   Ubuntu: sudo apt-get install php-imap");
            $this->line("\n   After install, restart PHP/Apache/Nginx");
            return false;
        }

        $this->info('✅ PHP IMAP extension is loaded');
        
        // Show IMAP extension details
        $version = phpversion('imap');
        $this->line("   Version: " . ($version ?: 'N/A'));
        
        return true;
    }

    protected function checkConfiguration()
    {
        $this->info("\n⚙️  Step 2: Checking Configuration");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $host = config('mail.imap.host');
        $port = config('mail.imap.port', 993);
        $username = config('mail.imap.username');
        $password = config('mail.imap.password');
        $encryption = config('mail.imap.encryption', 'ssl');
        $validateCert = config('mail.imap.validate_cert', true);

        $config = compact('host', 'port', 'username', 'password', 'encryption', 'validateCert');

        $this->table(
            ['Setting', 'Value', 'Status'],
            [
                ['IMAP_HOST', $host ?: '(not set)', $host ? '✅' : '❌'],
                ['IMAP_PORT', $port, '✅'],
                ['IMAP_USERNAME', $username ?: '(not set)', $username ? '✅' : '❌'],
                ['IMAP_PASSWORD', $password ? str_repeat('*', 16) : '(not set)', $password ? '✅' : '❌'],
                ['IMAP_ENCRYPTION', $encryption, '✅'],
                ['IMAP_VALIDATE_CERT', $validateCert ? 'true' : 'false', '✅'],
            ]
        );

        if (empty($host) || empty($username) || empty($password)) {
            $this->error("\n❌ Configuration is incomplete!");
            $this->warn("Please set these in .env file:");
            if (!$host) $this->line("   IMAP_HOST=your.mail.server");
            if (!$username) $this->line("   IMAP_USERNAME=your@email.com");
            if (!$password) $this->line("   IMAP_PASSWORD=yourpassword");
            return null;
        }

        $this->info("\n✅ Configuration is complete");
        return $config;
    }

    protected function testNetworkConnectivity($host, $port)
    {
        $this->info("\n🌐 Step 3: Testing Network Connectivity");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        // Test 1: DNS Resolution
        $this->line("🔍 Resolving hostname: {$host}");
        $ip = gethostbyname($host);
        
        if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
            $this->error("❌ Cannot resolve hostname: {$host}");
            $this->warn("   - Check if hostname is correct");
            $this->warn("   - Check DNS settings");
            $this->warn("   - Try using IP address instead");
            return false;
        }
        
        $this->info("✅ DNS resolved: {$host} → {$ip}");

        // Test 2: Port connectivity
        $this->line("\n🔌 Testing port connectivity: {$host}:{$port}");
        $startTime = microtime(true);
        $connection = @fsockopen($host, $port, $errno, $errstr, 10);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if (!$connection) {
            $this->error("❌ Cannot connect to {$host}:{$port}");
            $this->error("   Error #{$errno}: {$errstr}");
            $this->warn("\n💡 Possible causes:");
            $this->line("   1. Server is down or unreachable");
            $this->line("   2. Firewall blocking port {$port}");
            $this->line("   3. Wrong port number (try 143 for non-SSL, 993 for SSL)");
            $this->line("   4. Not connected to VPN (if required)");
            $this->line("   5. Network restrictions on this PC");
            
            // Suggest testing with telnet
            $this->warn("\n🔧 Try manual test:");
            $this->line("   telnet {$host} {$port}");
            $this->line("   OR");
            $this->line("   nc -zv {$host} {$port}");
            
            return false;
        }
        
        fclose($connection);
        $this->info("✅ Port {$port} is reachable (response time: {$duration}ms)");
        
        return true;
    }

    protected function testImapConnection($config)
    {
        $this->info("\n📧 Step 4: Testing IMAP Connection Methods");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");

        $host = $config['host'];
        $port = $config['port'];
        $username = $config['username'];
        $password = $config['password'];
        
        $certValidation = $config['validateCert'] ? '/validate-cert' : '/novalidate-cert';

        // Different connection methods to try
        $methods = [
            ['string' => "{{$host}:{$port}/imap/ssl{$certValidation}}INBOX", 'desc' => 'SSL with certificate validation'],
            ['string' => "{{$host}:{$port}/imap/ssl/novalidate-cert}INBOX", 'desc' => 'SSL without certificate validation'],
            ['string' => "{{$host}:{$port}/imap/tls/novalidate-cert}INBOX", 'desc' => 'TLS without certificate validation'],
            ['string' => "{{$host}:{$port}/imap/notls}INBOX", 'desc' => 'Plain IMAP without encryption'],
            ['string' => "{{$host}:143/imap/tls/novalidate-cert}INBOX", 'desc' => 'Port 143 with TLS'],
            ['string' => "{{$host}:143/imap}INBOX", 'desc' => 'Port 143 plain'],
        ];

        $successMethod = null;

        foreach ($methods as $index => $method) {
            $num = $index + 1;
            $this->line("\n🔄 Method {$num}: {$method['desc']}");
            $this->line("   Connection: {$method['string']}");

            // Clear previous errors
            @imap_errors();
            @imap_alerts();

            $startTime = microtime(true);
            $mailbox = @imap_open($method['string'], $username, $password, 0, 1);
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($mailbox) {
                $this->info("   ✅ SUCCESS! Connected in {$duration}ms");
                
                // Get mailbox info
                $check = imap_check($mailbox);
                if ($check) {
                    $this->line("   📊 Messages: {$check->Nmsgs}");
                }
                
                imap_close($mailbox);
                $successMethod = $method;
                break;
            } else {
                $error = imap_last_error();
                $errors = imap_errors();
                
                $this->error("   ❌ Failed in {$duration}ms");
                if ($error) {
                    $this->line("   Error: {$error}");
                }
                if ($errors && is_array($errors)) {
                    foreach (array_slice($errors, 0, 2) as $err) {
                        $this->line("   • {$err}");
                    }
                }
            }
        }

        if ($successMethod) {
            $this->info("\n✅ Found working connection method!");
            $this->info("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->line("Connection String: {$successMethod['string']}");
            
            $this->warn("\n💡 Update your .env file to use this method:");
            
            // Parse the connection string to provide .env recommendations
            if (strpos($successMethod['string'], '/ssl') !== false) {
                $this->line("   IMAP_ENCRYPTION=ssl");
            } elseif (strpos($successMethod['string'], '/tls') !== false) {
                $this->line("   IMAP_ENCRYPTION=tls");
            } else {
                $this->line("   IMAP_ENCRYPTION=");
            }
            
            if (strpos($successMethod['string'], '/novalidate-cert') !== false) {
                $this->line("   IMAP_VALIDATE_CERT=false");
            } else {
                $this->line("   IMAP_VALIDATE_CERT=true");
            }
            
            if (strpos($successMethod['string'], ':143') !== false) {
                $this->line("   IMAP_PORT=143");
            }

        } else {
            $this->error("\n❌ All connection methods failed!");
            $this->warn("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->warn("\n💡 Troubleshooting steps:");
            $this->line("   1. Verify credentials are correct");
            $this->line("   2. Check if IMAP is enabled on mail server");
            $this->line("   3. Try from another PC/network");
            $this->line("   4. Contact mail server administrator");
            $this->line("   5. Check server logs for authentication failures");
            $this->line("   6. For Gmail: Use App Password, not regular password");
            $this->line("   7. For Outlook: Enable 'Less secure apps'");
        }
    }
}
