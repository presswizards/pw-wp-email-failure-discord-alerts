# Press Wizards Mail Logger with Discord Alerts

A lightweight WordPress plugin that logs all email attempts and sends Discord notifications when emails fail to send.

## Description

This plugin provides comprehensive email monitoring for WordPress sites using the native PHP `mail()` function. It's perfect for sites using external SMTP relays like Mailgun, SendGrid, or other mail services configured at the server level.

### Key Features

- **Email Logging**: Records all email attempts with timestamps, recipients, subjects, and status
- **Discord Alerts**: Automatic notifications to Discord when emails fail to send
- **Test Functions**: Built-in tools to test both email delivery and Discord webhooks
- **Simple Interface**: Clean admin page under Tools → PW Mail Alerts
- **Lightweight**: Minimal overhead, uses native WordPress functions

## Installation

1. Download the plugin file `mail-logger-discord.php`
2. Upload to `/wp-content/plugins/mail-logger-discord/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Navigate to Tools → PW Mail Alerts to configure settings

## Configuration

### Discord Webhook Setup

1. Open your Discord server
2. Go to Server Settings → Integrations → Webhooks
3. Click "New Webhook" or select an existing one
4. Copy the Webhook URL
5. In WordPress, go to Tools → PW Mail Alerts
6. Paste the webhook URL in the settings field
7. Click "Save Settings"
8. Use the "Test Discord Alert" button to verify it's working

### Testing

The plugin provides two test functions:

- **Test Email**: Send a test email to any address to verify mail delivery
- **Test Discord Alert**: Send a sample failure notification to your Discord channel

## Intended Use

This plugin is designed for:

- WordPress sites using server-level SMTP relays (Mailgun, SendGrid, etc.)
- Monitoring email delivery success/failure rates
- Getting immediate notifications of email problems
- Debugging email configuration issues
- Maintaining email delivery logs for compliance or troubleshooting

## Database

The plugin creates a custom table `wp_mail_log` to store email records. The table includes:

- Email recipient(s)
- Subject line
- Success/failure status
- Error messages (if failed)
- Timestamp

The admin interface displays the last 100 log entries. You can clear all logs using the "Clear All Logs" button.

## Security Considerations

### Important Security Notes

1. **Admin Access Only**: All plugin functionality requires WordPress admin capabilities (`manage_options`)
2. **Sensitive Data**: Email logs may contain sensitive information including:
   - Email addresses
   - Subject lines
   - Error messages
   - Site URLs
3. **Discord Webhooks**: Webhook URLs should be kept private as they allow posting to your Discord channel
4. **Database Cleanup**: Consider clearing old logs periodically to minimize data retention
5. **Server Security**: This plugin relies on your server's PHP `mail()` configuration - ensure your server is properly secured

### Best Practices

- Only share Discord webhook URLs with trusted administrators
- Regularly review and clear old email logs
- Ensure your WordPress admin area is protected with strong passwords and 2FA
- Monitor who has access to the Tools → PW Mail Alerts page
- Consider WordPress multisite compatibility if using in a network environment

### What This Plugin Does NOT Do

- Does not store email body content
- Does not modify email headers or routing
- Does not provide SMTP authentication (relies on server configuration)
- Does not encrypt data in the database beyond WordPress defaults
- Does not automatically delete old logs (manual cleanup required)

## Privacy & Compliance

If you're subject to GDPR, CCPA, or other privacy regulations:

- Email addresses in logs may be considered personal data
- Inform users that email metadata is logged
- Implement a data retention policy
- Consider anonymizing or regularly purging old logs
- Update your privacy policy to reflect this logging

## Technical Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Server configured to use PHP `mail()` function
- (Optional) Discord account with webhook access

## Troubleshooting

### Emails Not Logging

- Verify the plugin is activated
- Check that emails are being sent through WordPress's `wp_mail()` function
- Some plugins bypass `wp_mail()` and won't be logged

### Discord Alerts Not Sending

- Verify webhook URL is correct
- Use the "Test Discord Alert" button to diagnose
- Check Discord webhook is still active
- Ensure your server can make outbound HTTPS requests

### Database Errors

- Verify database user has CREATE TABLE permissions
- Try deactivating and reactivating the plugin to recreate the table
- Check WordPress database prefix matches your configuration

## Support

For support, feature requests, or bug reports:

- GitHub: Create an issue in the plugin repository

## Changelog

### Version 1.0.0
- Initial release
- Email logging functionality
- Discord webhook integration
- Admin interface with test functions
- Database table creation and management

## License

This plugin is free software and is provided "as is" without warranty of any kind under GPL v3.

## Credits

**Author**: Press Wizards  
**Website**: [https://presswizards.com/wordpress-maintenance/](https://presswizards.com/wordpress-maintenance/)

---

*Made with ❤️ for WordPress administrators who need reliable email monitoring*
