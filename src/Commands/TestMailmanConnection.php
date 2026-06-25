<?php

namespace VEximweb\Plugin\VEximMailman3\Commands;

use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use VEximweb\Plugin\VEximMailman3\MailmanInterface;

class TestMailmanConnection extends Command
{
    protected $signature = 'vw:test-mailman-connection
                            {--host= : Mailman host (overrides config)}
                            {--port= : Mailman port (overrides config)}
                            {--username= : Mailman username (overrides config)}
                            {--password= : Mailman password (overrides config)}
                            {--list= : Specific list to test (optional)}';

    protected $description = 'Test the connection to Mailman 3 API';

    protected MailmanInterface $mailman;

    public function __construct(MailmanInterface $mailman)
    {
        parent::__construct();
        $this->mailman = $mailman;
    }

    public function handle(): int
    {
        $this->info('🔍 Testing Mailman 3 API Connection...');
        $this->newLine();

        // Show configuration being used
        $this->displayConfiguration();

        // Test connection
        try {
            $this->info('📡 Attempting to connect to Mailman API...');
            $this->newLine();

            // Fetch lists
            $startTime = microtime(true);
            $lists = $this->mailman->lists();
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            $this->info('✅ Connection successful!');
            $this->info("⏱️  Response time: {$duration}ms");
            $this->newLine();

            // Display results
            $this->displayResults($lists);

            return Command::SUCCESS;

        } catch (ConnectException $e) {
            $this->error('❌ Connection failed: Could not reach Mailman server');
            $this->error("   Error: {$e->getMessage()}");
            $this->newLine();
            $this->line('💡 Troubleshooting tips:');
            $this->line('   - Check if MAILMAN_HOST is correct');
            $this->line('   - Verify MAILMAN_PORT is open');
            $this->line('   - Ensure the Mailman API is running');
            $this->line('   - Check network connectivity');

            return Command::FAILURE;

        } catch (ClientException $e) {
            $statusCode = $e->getResponse()->getStatusCode();
            $this->error("❌ Authentication failed (HTTP {$statusCode})");
            $this->error("   Error: {$e->getMessage()}");
            $this->newLine();
            $this->line('💡 Troubleshooting tips:');
            $this->line('   - Check MAILMAN_USERNAME is correct');
            $this->line('   - Verify MAILMAN_PASSWORD is correct');
            $this->line('   - Ensure the user has API access');

            return Command::FAILURE;

        } catch (\Exception $e) {
            $this->error('❌ Unexpected error: ' . $e->getMessage());
            $this->newLine();
            $this->line('💡 Troubleshooting tips:');
            $this->line('   - Check the Mailman API version');
            $this->line('   - Verify the API endpoint is correct');
            $this->line('   - Check the error logs for details');
            Log::error('Mailman connection test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }

    protected function displayConfiguration(): void
    {
        $this->line('📋 Configuration:');
        $this->line(sprintf('   Host:     %s', config('vexim-mailman3.host')));
        $this->line(sprintf('   Port:     %s', config('vexim-mailman3.port')));
        $this->line(sprintf('   Username: %s', config('vexim-mailman3.admin_user')));
        $this->line(sprintf('   Password: %s', str_repeat('*', strlen(config('vexim-mailman3.admin_pass')))));
        $this->line(sprintf('   Version:  %s', config('vexim-mailman3.api_version', '3.0')));
        $this->newLine();
    }

    protected function displayResults(array $lists): void
    {
        $listCount = count($lists);

        if ($listCount === 0) {
            $this->info('📭 No mailing lists found.');
            $this->line('   Your connection is working but no lists exist yet.');

            return;
        }

        $this->info("📋 Found {$listCount} mailing list(s):");
        $this->newLine();

        // Create a table for display
        $tableData = [];
        foreach ($lists as $index => $list) {
            $listId = $list['list_id'] ?? $list['fqdn_listname'] ?? $list['name'] ?? 'Unknown';
            $displayName = $list['display_name'] ?? $list['list_name'] ?? 'Unnamed';
            $members = $list['member_count'] ?? '?';

            $tableData[] = [
                '#' => $index + 1,
                'List ID' => $listId,
                'Display Name' => $displayName,
                'Members' => $members,
            ];
        }

        $this->table(['#', 'List ID', 'Display Name', 'Members'], $tableData);

        // Test a specific list if requested
        $specificList = $this->option('list');
        if ($specificList) {
            $this->testSpecificList($specificList);
        }

        // Show member counts for first few lists
        if ($listCount > 0) {
            $this->newLine();
            $this->line('📊 Summary:');
            $totalMembers = 0;
            foreach ($lists as $list) {
                $totalMembers += $list['member_count'] ?? 0;
            }
            $this->line(sprintf('   Total lists:   %d', $listCount));
            $this->line(sprintf('   Total members: %d', $totalMembers));
            $this->line(sprintf('   Avg members:   %d', $listCount > 0 ? round($totalMembers / $listCount) : 0));
        }
    }

    protected function testSpecificList(string $listName): void
    {
        $this->newLine();
        $this->info("🔍 Testing specific list: {$listName}");

        try {
            $members = $this->mailman->members($listName);
            $memberCount = count($members);

            $this->info(sprintf('✅ Found %d member(s) in list "%s"', $memberCount, $listName));

            if ($memberCount > 0 && $memberCount <= 10) {
                $this->newLine();
                $this->line('📋 Members:');
                foreach ($members as $member) {
                    $email = $member['email'] ?? $member['address'] ?? 'Unknown';
                    $name = $member['display_name'] ?? $member['name'] ?? 'No name';
                    $this->line(sprintf('   - %s (%s)', $email, $name));
                }
            } elseif ($memberCount > 10) {
                $this->line('   (Showing first 10 of ' . $memberCount . ' members)');
                $this->newLine();
                foreach (array_slice($members, 0, 10) as $member) {
                    $email = $member['email'] ?? $member['address'] ?? 'Unknown';
                    $name = $member['display_name'] ?? $member['name'] ?? 'No name';
                    $this->line(sprintf('   - %s (%s)', $email, $name));
                }
            }

        } catch (\Exception $e) {
            $this->error("❌ Failed to fetch members for list '{$listName}': " . $e->getMessage());
        }
    }
}
