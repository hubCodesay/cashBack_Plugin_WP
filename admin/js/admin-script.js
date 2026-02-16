/**
 * WooCommerce Cashback System - Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    /**
     * Update user max limit
     */
    $('.wcs-update-limit').on('click', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var userId = button.data('user-id');
        var input = $('.wcs-user-max-limit[data-user-id="' + userId + '"]');
        var maxLimit = input.val();
        
        if (!maxLimit || maxLimit < 0) {
            alert('Please enter a valid maximum limit.');
            return;
        }
        
        // Show loading state
        button.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: wcs_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wcs_update_user_balance',
                nonce: wcs_admin.nonce,
                user_id: userId,
                max_limit: maxLimit
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                button.prop('disabled', false).text('Update');
            }
        });
    });
    
    /**
     * Reset user balance
     */
    $('.wcs-reset-balance').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to reset this user\'s cashback balance? This action cannot be undone.')) {
            return;
        }
        
        var button = $(this);
        var userId = button.data('user-id');
        
        // Show loading state
        button.prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: wcs_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wcs_reset_user_balance',
                nonce: wcs_admin.nonce,
                user_id: userId
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    // Reload page after 1 second
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message, 'error');
                    button.prop('disabled', false).text('Reset');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
                button.prop('disabled', false).text('Reset');
            }
        });
    });
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        var notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notification = $('<div class="notice ' + notificationClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Confirm settings changes
     */
    $('form[action="options.php"]').on('submit', function(e) {
        var enabled = $('#enabled').is(':checked');
        
        if (!enabled) {
            if (!confirm('You are about to disable the cashback system. Users will not be able to earn or use cashback while it is disabled. Continue?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    /**
     * Validate tier thresholds
     */
    $('input[name*="tier_"][name*="threshold"]').on('change', function() {
        var tier1 = parseFloat($('#tier_1_threshold').val()) || 0;
        var tier2 = parseFloat($('#tier_2_threshold').val()) || 0;
        var tier3 = parseFloat($('#tier_3_threshold').val()) || 0;
        
        if (tier2 < tier1) {
            alert('Tier 2 threshold must be greater than or equal to Tier 1 threshold.');
            $('#tier_2_threshold').val(tier1);
        }
        
        if (tier3 < tier2) {
            alert('Tier 3 threshold must be greater than or equal to Tier 2 threshold.');
            $('#tier_3_threshold').val(tier2);
        }
    });
});
