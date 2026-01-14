<?php
/**
 * Plugin Name: Press Wizards Mail Logger with Discord Alerts
 * Description: Logs PHP mail() attempts and sends Discord notifications on failures
 * Version: 1.0.0
 * Author: Press Wizards
 * Author URI: https://presswizards.com/wordpress-maintenance/
 */

if (!defined('ABSPATH')) exit;

class Mail_Logger_Discord {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mail_log';
        
        register_activation_hook(__FILE__, [$this, 'activate']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_filter('wp_mail', [$this, 'intercept_mail'], 999);
        add_action('wp_mail_failed', [$this, 'log_failure']);
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            to_email varchar(255) NOT NULL,
            subject text NOT NULL,
            status varchar(20) NOT NULL,
            error_message text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    public function intercept_mail($args) {
        // Store original mail data for logging
        add_action('wp_mail_succeeded', function() use ($args) {
            $this->log_success($args);
        }, 10, 1);
        
        return $args;
    }
    
    public function log_success($mail_data) {
        global $wpdb;
        
        $to = is_array($mail_data['to']) ? implode(', ', $mail_data['to']) : $mail_data['to'];
        
        $wpdb->insert(
            $this->table_name,
            [
                'to_email' => $to,
                'subject' => $mail_data['subject'],
                'status' => 'success'
            ]
        );
    }
    
    public function log_failure($wp_error) {
        global $wpdb;
        
        $error_data = $wp_error->get_error_data();
        $to = isset($error_data['to']) ? (is_array($error_data['to']) ? implode(', ', $error_data['to']) : $error_data['to']) : 'unknown';
        $subject = isset($error_data['subject']) ? $error_data['subject'] : 'unknown';
        $error_message = $wp_error->get_error_message();
        
        $wpdb->insert(
            $this->table_name,
            [
                'to_email' => $to,
                'subject' => $subject,
                'status' => 'failed',
                'error_message' => $error_message
            ]
        );
        
        $this->send_discord_alert($to, $subject, $error_message);
    }
    
    private function send_discord_alert($to, $subject, $error) {
        $webhook_url = get_option('mail_logger_discord_webhook', '');
        
        if (empty($webhook_url)) return;
        
        $payload = json_encode([
            'embeds' => [[
                'title' => '📧 Email Failure Alert',
                'color' => 15158332, // Red color
                'fields' => [
                    ['name' => 'To', 'value' => $to, 'inline' => false],
                    ['name' => 'Subject', 'value' => $subject, 'inline' => false],
                    ['name' => 'Error', 'value' => $error, 'inline' => false],
                    ['name' => 'Site', 'value' => get_bloginfo('name') . ' (' . home_url() . ')', 'inline' => false]
                ],
                'timestamp' => date('c')
            ]]
        ]);
        
        return wp_remote_post($webhook_url, [
            'body' => $payload,
            'headers' => ['Content-Type' => 'application/json']
        ]);
    }
    
    public function add_admin_menu() {
        add_management_page(
            'PW Mail Alerts',
            'PW Mail Alerts',
            'manage_options',
            'pw-mail-logger',
            [$this, 'admin_page']
        );
    }
    
    public function admin_page() {
        global $wpdb;
        
        if (isset($_POST['discord_webhook'])) {
            check_admin_referer('mail_logger_settings');
            update_option('mail_logger_discord_webhook', sanitize_text_field($_POST['discord_webhook']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        if (isset($_POST['clear_logs'])) {
            check_admin_referer('mail_logger_clear');
            $wpdb->query("TRUNCATE TABLE {$this->table_name}");
            echo '<div class="notice notice-success"><p>Logs cleared!</p></div>';
        }
        
        if (isset($_POST['test_email'])) {
            check_admin_referer('mail_logger_test');
            $test_email = sanitize_email($_POST['test_email_address']);
            if (!empty($test_email) && is_email($test_email)) {
                $sent = wp_mail(
                    $test_email,
                    'Test Email from Press Wizards Mail Logger',
                    'This is a test email to verify your mail configuration is working correctly.'
                );
                if ($sent) {
                    echo '<div class="notice notice-success"><p>Test email sent successfully to ' . esc_html($test_email) . '!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Test email failed to send. Check logs below for details.</p></div>';
                }
            } else {
                echo '<div class="notice notice-error"><p>Please enter a valid email address.</p></div>';
            }
        }
        
        if (isset($_POST['test_discord'])) {
            check_admin_referer('mail_logger_test_discord');
            $webhook = get_option('mail_logger_discord_webhook', '');
            if (empty($webhook)) {
                echo '<div class="notice notice-error"><p>Please configure a Discord webhook URL first.</p></div>';
            } else {
                $result = $this->send_discord_alert(
                    'test@example.com',
                    'Test Alert from Press Wizards Mail Logger',
                    'This is a test alert to verify your Discord webhook is configured correctly.'
                );
                if (!is_wp_error($result) && wp_remote_retrieve_response_code($result) == 204) {
                    echo '<div class="notice notice-success"><p>Test alert sent to Discord successfully!</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>Failed to send Discord alert. Please check your webhook URL.</p></div>';
                }
            }
        }
        
        $webhook = get_option('mail_logger_discord_webhook', '');
        $logs = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>PW Mail Alerts</h1>
            
            <h2>Settings</h2>
            <form method="post">
                <?php wp_nonce_field('mail_logger_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="discord_webhook">Discord Webhook URL</label></th>
                        <td>
                            <input type="url" name="discord_webhook" id="discord_webhook" 
                                   value="<?php echo esc_attr($webhook); ?>" class="regular-text" />
                            <p class="description">Enter your Discord webhook URL to receive failure alerts</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
            
            <form method="post" style="margin-top: 10px;">
                <?php wp_nonce_field('mail_logger_test_discord'); ?>
                <?php submit_button('Test Discord Alert', 'secondary', 'test_discord'); ?>
            </form>
            
            <hr>
            
            <h2>Test Email</h2>
            <form method="post">
                <?php wp_nonce_field('mail_logger_test'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="test_email_address">Send Test Email To</label></th>
                        <td>
                            <input type="email" name="test_email_address" id="test_email_address" 
                                   class="regular-text" placeholder="email@example.com" required />
                            <p class="description">Enter an email address to send a test email</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Send Test Email', 'secondary', 'test_email'); ?>
            </form>
            
            <hr>
            
            <h2>Email Logs (Last 100)</h2>
            <form method="post" style="margin-bottom: 20px;">
                <?php wp_nonce_field('mail_logger_clear'); ?>
                <input type="submit" name="clear_logs" class="button" value="Clear All Logs" 
                       onclick="return confirm('Are you sure you want to clear all logs?');" />
            </form>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>To</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5">No logs yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->created_at); ?></td>
                                <td><?php echo esc_html($log->to_email); ?></td>
                                <td><?php echo esc_html($log->subject); ?></td>
                                <td>
                                    <span style="color: <?php echo $log->status === 'success' ? 'green' : 'red'; ?>">
                                        <?php echo esc_html(ucfirst($log->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($log->error_message); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

new Mail_Logger_Discord();
