# WooCommerce Cashback System - Plugin Overview

## Complete Plugin Structure

```
wp-content/plugins/woo-cashback-system/
│
├── woo-cashback-system.php          # Main plugin file (entry point)
├── uninstall.php                     # Cleanup script for uninstallation
├── README.md                         # Full documentation
├── INSTALLATION.md                   # Installation & testing guide
├── CHANGELOG.md                      # Version history
│
├── includes/                         # Core plugin classes
│   ├── class-cashback-admin.php     # Admin settings & user management
│   ├── class-cashback-calculator.php # Cashback calculation logic
│   ├── class-cashback-checkout.php  # Checkout integration
│   ├── class-cashback-database.php  # Database operations
│   ├── class-cashback-notifications.php # Email notifications
│   └── class-cashback-user.php      # User dashboard & frontend
│
├── admin/                            # Admin assets
│   ├── css/
│   │   └── admin-style.css          # Admin styles
│   └── js/
│       └── admin-script.js          # Admin JavaScript
│
└── public/                           # Public assets
    ├── css/
    │   └── public-style.css         # Frontend styles
    └── js/
        └── public-script.js         # Frontend JavaScript
```

## Plugin Components

### 1. Main Plugin File (woo-cashback-system.php)
**Purpose**: Entry point and initialization
- Plugin headers and metadata
- Constants definition
- Main class `WCS_Cashback_System`
- Activation/deactivation hooks
- Dependencies loader
- Script/style enqueuing

**Key Functions**:
- `activate()` - Creates tables, sets defaults
- `deactivate()` - Cleanup on deactivation
- `init_components()` - Initializes all classes
- `admin_scripts()` - Loads admin assets
- `public_scripts()` - Loads frontend assets

### 2. Database Handler (class-cashback-database.php)
**Purpose**: All database operations
- Table creation and management
- CRUD operations for balances
- CRUD operations for transactions
- Query methods for statistics

**Key Methods**:
- `create_tables()` - Creates DB tables
- `get_user_balance()` - Retrieves user balance
- `update_balance()` - Updates balance (earned/spent/adjustment)
- `add_transaction()` - Records transaction
- `get_user_transactions()` - Gets transaction history
- `get_all_users_with_cashback()` - Gets all users for admin
- `get_statistics()` - Calculates overall statistics

### 3. Calculator (class-cashback-calculator.php)
**Purpose**: Cashback calculation and validation
- Calculates cashback based on order total
- Validates cashback earning eligibility
- Validates cashback usage
- Processes earning and spending

**Key Methods**:
- `calculate_cashback()` - Calculates cashback amount
- `can_earn_cashback()` - Checks earning eligibility
- `calculate_max_usable_cashback()` - Max usable amount
- `validate_cashback_usage()` - Validates usage
- `process_cashback_earning()` - Processes earning
- `process_cashback_spending()` - Processes spending

### 4. Admin Interface (class-cashback-admin.php)
**Purpose**: Admin pages and management
- Settings page
- User management page
- Statistics page
- AJAX handlers for admin actions

**Key Methods**:
- `add_admin_menu()` - Adds admin menu items
- `settings_page()` - Renders settings page
- `users_page()` - Renders user management
- `statistics_page()` - Renders statistics
- `ajax_update_user_balance()` - Updates user limit
- `ajax_reset_user_balance()` - Resets user balance

### 5. User Dashboard (class-cashback-user.php)
**Purpose**: Frontend user interface
- My Cashback page in My Account
- Dashboard widget
- Order details display

**Key Methods**:
- `add_cashback_menu_item()` - Adds menu to My Account
- `add_cashback_endpoint()` - Registers endpoint
- `cashback_content()` - Renders cashback page
- `display_dashboard_widget()` - Shows widget
- `display_order_cashback_info()` - Shows order info

### 6. Checkout Integration (class-cashback-checkout.php)
**Purpose**: Checkout functionality
- Displays cashback field at checkout
- Handles cashback application/removal
- Applies discount to cart
- Processes on order completion

**Key Methods**:
- `display_cashback_field()` - Shows checkout field
- `ajax_apply_cashback()` - Applies cashback
- `ajax_remove_cashback()` - Removes cashback
- `apply_cashback_discount()` - Adds discount to cart
- `save_cashback_to_order()` - Saves to order meta
- `process_order_cashback()` - Processes on completion

### 7. Notifications (class-cashback-notifications.php)
**Purpose**: Email notifications
- Cashback earned notifications
- Cashback used notifications
- Admin alerts

**Key Methods**:
- `notify_cashback_earned()` - Sends earned email
- `notify_cashback_used()` - Sends used email
- `get_email_template()` - Generates HTML email
- `notify_admin_unusual_activity()` - Admin alerts

## Database Tables

### wp_wcs_cashback_balances
Stores current balance and totals for each user.

**Columns**:
- `id` (Primary Key)
- `user_id` (Unique, Indexed)
- `balance` - Current available balance
- `total_earned` - Lifetime earnings
- `total_spent` - Lifetime spending
- `max_limit` - User-specific limit (nullable)
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### wp_wcs_cashback_transactions
Stores all cashback transactions (history).

**Columns**:
- `id` (Primary Key)
- `user_id` (Indexed)
- `order_id` (Indexed)
- `transaction_type` (earned/spent/adjustment)
- `amount` - Transaction amount
- `balance_before` - Balance before transaction
- `balance_after` - Balance after transaction
- `order_total` - Related order total
- `cashback_percentage` - Percentage earned
- `description` - Transaction description
- `created_at` - Transaction timestamp

## User Flow Diagrams

### Earning Cashback Flow
```
1. Customer places order
   ↓
2. Order status → Processing/Completed
   ↓
3. WooCommerce hook triggers
   ↓
4. Calculator checks eligibility
   ↓
5. Calculator calculates cashback amount
   ↓
6. Database updates balance
   ↓
7. Transaction recorded
   ↓
8. Email notification sent
   ↓
9. User sees updated balance
```

### Using Cashback Flow
```
1. Customer at checkout
   ↓
2. Cashback section displayed (if balance > 0)
   ↓
3. Customer enters amount
   ↓
4. AJAX validation request
   ↓
5. Server validates amount
   ↓
6. If valid → Apply discount to cart
   ↓
7. Customer completes order
   ↓
8. Order status → Processing/Completed
   ↓
9. Cashback deducted from balance
   ↓
10. Transaction recorded
    ↓
11. Email notification sent
```

## Settings & Configuration

### Default Settings
```php
array(
    'enabled' => 'yes',
    'tier_1_threshold' => 500,
    'tier_1_percentage' => 3,
    'tier_2_threshold' => 1000,
    'tier_2_percentage' => 5,
    'tier_3_threshold' => 1500,
    'tier_3_percentage' => 7,
    'max_cashback_limit' => 10000,
    'usage_limit_percentage' => 50,
    'enable_notifications' => 'yes',
)
```

### Stored in WordPress Options
Option name: `wcs_cashback_settings`

## Security Measures

1. **Nonce Verification**: All AJAX requests
2. **Capability Checks**: `manage_woocommerce` for admin
3. **Input Sanitization**: All user inputs
4. **Output Escaping**: All displayed data
5. **Prepared Statements**: All SQL queries
6. **Direct Access Prevention**: All PHP files
7. **Session Management**: Checkout cashback storage

## Hooks & Filters

### Actions (for developers)
```php
// Cashback earned
do_action('wcs_cashback_earned', $user_id, $amount, $order_id);

// Cashback spent
do_action('wcs_cashback_spent', $user_id, $amount, $order_id);
```

### Filters (for developers)
```php
// Modify calculation
apply_filters('wcs_calculate_cashback', $amount, $order_total, $user_id);

// Modify max usable
apply_filters('wcs_max_usable_cashback', $max, $order_total, $user_id);
```

## Activation Sequence

1. Check if WooCommerce is active
2. Create database tables
3. Set default options
4. Flush rewrite rules
5. Initialize plugin classes

## AJAX Endpoints

### Admin AJAX
- `wcs_update_user_balance` - Update user max limit
- `wcs_reset_user_balance` - Reset user balance

### Public AJAX
- `wcs_apply_cashback` - Apply cashback at checkout
- `wcs_remove_cashback` - Remove cashback at checkout

## CSS Classes Reference

### Admin Classes
- `.wcs-stats-grid` - Statistics grid
- `.wcs-stat-box` - Stat card
- `.wcs-user-max-limit` - User limit input
- `.wcs-update-limit` - Update button
- `.wcs-reset-balance` - Reset button

### Frontend Classes
- `.wcs-cashback-dashboard` - Main dashboard
- `.wcs-balance-summary` - Balance cards grid
- `.wcs-balance-card` - Individual balance card
- `.wcs-checkout-cashback` - Checkout section
- `.wcs-transactions-table` - Transaction history table

## JavaScript Functions Reference

### Admin JS
- `showNotification()` - Display admin notice
- Event: `.wcs-update-limit click` - Update limit
- Event: `.wcs-reset-balance click` - Reset balance

### Public JS
- `showMessage()` - Display user message
- Event: `.wcs-apply-cashback click` - Apply cashback
- Event: `.wcs-remove-cashback click` - Remove cashback
- Event: `updated_checkout` - Update max usable

## Testing Checklist

✅ Plugin activation
✅ Database tables creation
✅ Settings page functionality
✅ User management interface
✅ Cashback calculation (all tiers)
✅ Checkout integration
✅ Balance updates
✅ Transaction recording
✅ Email notifications
✅ Frontend display
✅ Admin statistics
✅ Security measures
✅ Validation logic
✅ Error handling

## Support & Maintenance

### Regular Tasks
- Monitor database table size
- Review transaction logs
- Check email delivery
- Update settings as needed
- Review user feedback

### Troubleshooting
- Check error logs
- Verify WooCommerce compatibility
- Test email functionality
- Review database queries
- Check cache issues

## Future Enhancements

Consider adding:
- REST API endpoints
- Cashback expiration
- Promotional campaigns
- CSV export
- Advanced reports
- Multi-currency
- Bulk operations
- Product-specific rules
- Category-specific rules

---

**Plugin Version**: 1.0.0  
**WordPress Version**: 5.8+  
**WooCommerce Version**: 5.0+  
**PHP Version**: 7.4+  
**License**: GPL v2 or later
