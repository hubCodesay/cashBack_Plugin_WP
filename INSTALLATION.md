# Installation & Testing Guide

## Quick Start

### 1. Activate the Plugin

1. Go to WordPress Admin Dashboard
2. Navigate to **Plugins > Installed Plugins**
3. Find "WooCommerce Cashback System"
4. Click **Activate**

The plugin will automatically:

- Create database tables
- Set default settings
- Add necessary rewrite endpoints

### 2. Configure Settings

1. Go to **Cashback > Settings** in admin menu
2. Review and adjust default settings:
   - Enable/disable the system
   - Modify cashback tier thresholds and percentages
   - Set maximum cashback limits
   - Configure usage restrictions
   - Enable email notifications

3. Click **Save Changes**

### 3. Test the System

#### Test Cashback Earning

1. Create a test order as a customer:
   - Add products to cart (total should be over 500 UAH)
   - Complete checkout
   - Mark order as "Completed" in admin

2. Check cashback was added:
   - Go to **Cashback > Manage Users**
   - Find the test user
   - Verify balance shows earned cashback

3. Verify user can see cashback:
   - Log in as test user
   - Go to **My Account > My Cashback**
   - Check balance and transaction history

#### Test Cashback Usage

1. As a customer with cashback balance:
   - Add products to cart
   - Go to checkout
   - Look for "Use Your Cashback" section
   - Enter amount to use
   - Click "Apply Cashback"
   - Verify discount is applied
   - Complete order

2. Verify cashback was deducted:
   - Check **My Account > My Cashback**
   - Verify balance decreased
   - Check transaction history shows "spent" entry

#### Test Validations

1. **Maximum Usage Limit** (50% by default):
   - Try to use more than 50% of order total
   - Should show error message

2. **Insufficient Balance**:
   - Try to use more cashback than available
   - Should show error message

3. **Maximum Cashback Limit**:
   - Set a low max limit for test user
   - Place orders that would exceed limit
   - Verify cashback caps at maximum

### 4. Check Notifications

1. Configure email settings in **Settings > General**
2. Test cashback earning:
   - Complete an order
   - Check user's email for cashback earned notification

3. Test cashback usage:
   - Use cashback on checkout
   - Complete order
   - Check email for cashback used notification

## Verification Checklist

### Admin Side

- [ ] Plugin activates without errors
- [ ] Settings page loads correctly
- [ ] Can modify all settings successfully
- [ ] Manage Users page shows users with cashback
- [ ] Can update user max limits
- [ ] Can reset user balances
- [ ] Statistics page displays correctly
- [ ] Database tables created properly

### Frontend Side

- [ ] My Cashback menu item appears in My Account
- [ ] Cashback dashboard displays correctly
- [ ] Transaction history shows properly
- [ ] Dashboard widget appears (if user has balance)
- [ ] Checkout cashback section appears
- [ ] Can apply cashback at checkout
- [ ] Can remove applied cashback
- [ ] Order details show cashback info

### Functionality

- [ ] Cashback calculates correctly for each tier
- [ ] Cashback adds to user balance on order completion
- [ ] Maximum cashback limit enforces properly
- [ ] Usage limit (50%) enforces at checkout
- [ ] Cashback deducts from balance after order
- [ ] Transaction history records all activities
- [ ] Email notifications send correctly

## Common Issues & Solutions

### Issue: My Cashback page shows 404

**Solution**:

1. Go to **Settings > Permalinks**
2. Click **Save Changes** (without changing anything)
3. This will flush rewrite rules

### Issue: Database tables not created

**Solution**:

1. Deactivate plugin
2. Reactivate plugin
3. Check database for tables: `wp_wcs_cashback_balances` and `wp_wcs_cashback_transactions`

### Issue: Cashback not appearing at checkout

**Solution**:

1. Verify user is logged in
2. Check user has cashback balance
3. Verify cashback system is enabled in settings
4. Clear browser cache and cookies

### Issue: Cashback not earned after order

**Solution**:

1. Check order status is "Completed" or "Processing"
2. Verify order total meets minimum threshold
3. Check user hasn't reached maximum cashback limit
4. Review order for any existing cashback earned

## Testing Scenarios

### Scenario 1: First-Time User

```
1. New user creates account
2. Places order for 1,200 UAH
3. Expected: Earns 60 UAH (5% of 1,200)
4. Can see balance in My Account
5. Receives email notification
```

### Scenario 2: Using Cashback

```
1. User has 100 UAH cashback balance
2. Places order for 150 UAH
3. Applies 75 UAH cashback (50% limit)
4. Pays 75 UAH
5. New order also earns cashback (calculated on full 150 UAH)
```

### Scenario 3: Maximum Limit Reached

```
1. User has 9,950 UAH balance (limit is 10,000)
2. Places order that would earn 100 UAH
3. Expected: Earns only 50 UAH (to reach 10,000 limit)
4. Admin notification sent about limit reached
```

### Scenario 4: Tier Progression

```
Order 400 UAH → 0 UAH cashback (below minimum)
Order 600 UAH → 18 UAH cashback (3%)
Order 1,100 UAH → 55 UAH cashback (5%)
Order 2,000 UAH → 140 UAH cashback (7%)
```

## Database Queries for Testing

### Check User Balance

```sql
SELECT * FROM wp_wcs_cashback_balances WHERE user_id = 1;
```

### View Recent Transactions

```sql
SELECT * FROM wp_wcs_cashback_transactions
ORDER BY created_at DESC
LIMIT 10;
```

### Total Cashback Statistics

```sql
SELECT
    SUM(balance) as total_balance,
    SUM(total_earned) as total_earned,
    SUM(total_spent) as total_spent,
    COUNT(*) as total_users
FROM wp_wcs_cashback_balances;
```

## Performance Tips

1. **Database Optimization**:
   - Tables have proper indexes
   - Queries use prepared statements
   - No N+1 query issues

2. **Caching**:
   - Consider object caching for balance lookups
   - Cache statistics on admin dashboard

3. **Asset Loading**:
   - Scripts only load on relevant pages
   - Styles are minified for production

## Next Steps

After successful testing:

1. **Customize Settings**: Adjust tiers and limits to match your business model
2. **Email Templates**: Customize notification emails if needed
3. **Styling**: Modify CSS to match your theme
4. **Documentation**: Train staff on using admin features
5. **Monitoring**: Regularly check statistics and user activity

## Support

If you encounter any issues not covered here:

- Check plugin error logs
- Enable WordPress debug mode
- Review browser console for JavaScript errors
- Check PHP error logs
- Contact support with specific error messages
