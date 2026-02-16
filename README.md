# WooCommerce Cashback System

A comprehensive WordPress plugin that provides a fully functional cashback system for WooCommerce stores. Users can earn cashback on purchases and use it on future orders with configurable rates and limits.

## Features

### Admin Features
- **Flexible Cashback Tiers**: Configure 3 tiers of cashback percentages based on order amounts
- **Maximum Limits**: Set global or per-user maximum cashback accumulation limits
- **Usage Restrictions**: Control what percentage of order total can be paid with cashback
- **User Management**: View, edit, and manage all user cashback balances
- **Transaction History**: Track all cashback earning and spending activities
- **Statistics Dashboard**: View overall cashback system statistics
- **Email Notifications**: Automatic notifications for cashback events

### Customer Features
- **My Cashback Dashboard**: Dedicated page showing balance, history, and earnings
- **Checkout Integration**: Apply cashback during checkout with real-time validation
- **Order Details**: View cashback earned and used on each order
- **Email Notifications**: Get notified when earning or using cashback
- **Transaction History**: See detailed history of all cashback activities

### Security Features
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Sanitization and validation of all inputs
- SQL injection prevention with prepared statements
- XSS protection with proper escaping

## Installation

1. Upload the `woo-cashback-system` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Cashback > Settings** to configure the system
4. Ensure WooCommerce is installed and activated

## Configuration

### Default Settings

The plugin comes with these default settings (can be modified):

- **Tier 1**: 3% cashback on orders over 500 UAH
- **Tier 2**: 5% cashback on orders over 1000 UAH
- **Tier 3**: 7% cashback on orders over 1500 UAH
- **Maximum Limit**: 10,000 UAH per user
- **Usage Limit**: 50% of order total

### Admin Settings Page

Navigate to **Cashback > Settings** to configure:

1. **Enable/Disable System**: Toggle cashback functionality globally
2. **Cashback Tiers**: Set thresholds and percentages for each tier
3. **Maximum Limits**: Set global maximum cashback accumulation
4. **Usage Limits**: Control percentage of order total usable as cashback
5. **Notifications**: Enable/disable email notifications

### Managing Users

Navigate to **Cashback > Manage Users** to:

- View all users with cashback balances
- Edit individual user maximum limits
- Reset user balances
- View user statistics
- Access detailed transaction history

### Statistics

Navigate to **Cashback > Statistics** to view:

- Total active cashback balance across all users
- Total cashback earned by all users
- Total cashback spent
- Number of users with cashback

## How It Works

### Earning Cashback

1. Customer completes an order
2. Order status changes to "Processing" or "Completed"
3. System calculates cashback based on order total and tier
4. Cashback is added to user's balance
5. User receives email notification
6. Transaction is recorded in history

### Using Cashback

1. Customer proceeds to checkout
2. Cashback section appears if user has available balance
3. Customer enters amount to use (up to maximum allowed)
4. System validates amount against balance and usage limits
5. Cashback discount is applied to order
6. Upon order completion, cashback is deducted from balance
7. User receives email notification

### Cashback Calculation

```php
Order Total ≥ 1500 UAH → 7% cashback
Order Total ≥ 1000 UAH → 5% cashback
Order Total ≥ 500 UAH  → 3% cashback
Order Total < 500 UAH  → 0% cashback
```

### Usage Limits

- Users can spend up to 50% of their order total using cashback (configurable)
- Users cannot use more cashback than they have available
- Cashback usage must be validated before order completion

## Database Structure

The plugin creates two custom tables:

### wp_wcs_cashback_balances
Stores user cashback balance information:
- `id`: Primary key
- `user_id`: WordPress user ID
- `balance`: Current cashback balance
- `total_earned`: Total cashback earned
- `total_spent`: Total cashback used
- `max_limit`: User-specific maximum limit (optional)
- `created_at`: Record creation timestamp
- `updated_at`: Last update timestamp

### wp_wcs_cashback_transactions
Stores all cashback transactions:
- `id`: Primary key
- `user_id`: WordPress user ID
- `order_id`: WooCommerce order ID
- `transaction_type`: earned, spent, or adjustment
- `amount`: Transaction amount
- `balance_before`: Balance before transaction
- `balance_after`: Balance after transaction
- `order_total`: Order total amount
- `cashback_percentage`: Percentage earned
- `description`: Transaction description
- `created_at`: Transaction timestamp

## Shortcodes

Currently, the plugin uses WooCommerce endpoints. Future versions may include shortcodes for:
- `[wcs_balance]` - Display user's current balance
- `[wcs_history]` - Display transaction history

## Hooks and Filters

### Actions

```php
// Triggered when cashback is earned
do_action('wcs_cashback_earned', $user_id, $cashback_amount, $order_id);

// Triggered when cashback is spent
do_action('wcs_cashback_spent', $user_id, $cashback_amount, $order_id);
```

### Filters

```php
// Modify cashback calculation
apply_filters('wcs_calculate_cashback', $cashback_amount, $order_total, $user_id);

// Modify maximum usable amount
apply_filters('wcs_max_usable_cashback', $max_amount, $order_total, $user_id);
```

## API Functions

### Get User Balance
```php
$balance = WCS_Cashback_Database::get_user_balance($user_id);
echo $balance->balance; // Current balance
echo $balance->total_earned; // Total earned
echo $balance->total_spent; // Total spent
```

### Calculate Cashback
```php
$cashback_data = WCS_Cashback_Calculator::calculate_cashback($order_total);
echo $cashback_data['amount']; // Cashback amount
echo $cashback_data['percentage']; // Percentage used
```

### Validate Cashback Usage
```php
$validation = WCS_Cashback_Calculator::validate_cashback_usage($user_id, $cashback_amount, $order_total);
if ($validation['valid']) {
    // Proceed with cashback usage
}
```

## Troubleshooting

### Cashback Not Being Earned

1. Check if cashback system is enabled in settings
2. Verify order status is "Processing" or "Completed"
3. Check if order total meets minimum threshold (500 UAH by default)
4. Verify user hasn't reached maximum cashback limit

### Cashback Not Applying at Checkout

1. Ensure user is logged in
2. Verify user has available cashback balance
3. Check that amount doesn't exceed 50% of order total
4. Clear browser cache and WooCommerce sessions

### Database Issues

If tables are not created properly, you can:
1. Deactivate the plugin
2. Reactivate the plugin (tables are created on activation)
3. Check database for `wp_wcs_cashback_balances` and `wp_wcs_cashback_transactions` tables

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## Support

For support, feature requests, or bug reports:
- Create an issue in the repository
- Contact the plugin author
- Check documentation for common issues

## Changelog

### Version 1.0.0
- Initial release
- Configurable cashback tiers
- User dashboard and management
- Checkout integration
- Email notifications
- Admin statistics
- Transaction history

## License

GPL v2 or later

## Credits

Developed with best practices for WordPress plugin development and WooCommerce integration.

## Roadmap

Future features planned:
- Cashback expiration dates
- Promotional cashback campaigns
- Referral bonuses
- CSV export for transactions
- REST API endpoints
- Multi-currency support
- Bulk user operations
- Advanced reporting
