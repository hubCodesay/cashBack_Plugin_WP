/**
 * WooCommerce Cashback System — Public JS
 * Handles apply / remove cashback via AJAX
 */
(function ($) {
	'use strict';

	if (typeof wcs_public === 'undefined') {
		return;
	}

	var ajaxUrl = wcs_public.ajax_url;
	var nonce   = wcs_public.nonce;

	/* ── De-duplicate cashback blocks ─────────────────── */
	function removeDuplicateBlocks() {
		var seen = {};
		$('.wcs-cashback-block').each(function () {
			var id = this.id || 'unnamed';
			if (seen[id]) {
				$(this).remove(); // remove duplicate
			} else {
				seen[id] = true;
			}
		});
	}

	// Run on load
	$(document).ready(removeDuplicateBlocks);

	// Run after WC AJAX updates
	$(document.body).on('updated_checkout updated_cart_totals', removeDuplicateBlocks);

	/* ── Apply cashback ────────────────────────────────── */
	$(document).on('click', '.wcs-cb-apply-btn', function (e) {
		e.preventDefault();
		var $btn     = $(this);
		var context  = $btn.data('context');
		var $input   = $('#wcs-amount-' + context);
		var amount   = parseFloat($input.val()) || 0;
		var $msg     = $('#wcs-cb-message-' + context);

		if (amount <= 0) {
			showMessage($msg, 'Введіть суму більшу за 0', 'error');
			return;
		}

		$btn.prop('disabled', true).text('…');

		$.post(ajaxUrl, {
			action: 'wcs_apply_cashback',
			nonce:  nonce,
			amount: amount
		}, function (res) {
			if (res.success) {
				showMessage($msg, res.data.message, 'success');
				// Refresh cart / checkout fragments
				refreshPage();
			} else {
				showMessage($msg, res.data.message || 'Помилка', 'error');
				$btn.prop('disabled', false).text('Використати');
			}
		}).fail(function () {
			showMessage($msg, 'Помилка з\'єднання', 'error');
			$btn.prop('disabled', false).text('Використати');
		});
	});

	/* ── Remove cashback ───────────────────────────────── */
	$(document).on('click', '.wcs-cb-remove-btn', function (e) {
		e.preventDefault();
		var $btn    = $(this);
		var context = $btn.data('context');
		var $msg    = $('#wcs-cb-message-' + context);

		$btn.prop('disabled', true).text('…');

		$.post(ajaxUrl, {
			action: 'wcs_remove_cashback',
			nonce:  nonce
		}, function (res) {
			if (res.success) {
				showMessage($msg, res.data.message, 'success');
				refreshPage();
			} else {
				showMessage($msg, res.data.message || 'Помилка', 'error');
				$btn.prop('disabled', false).text('Скасувати');
			}
		}).fail(function () {
			showMessage($msg, 'Помилка з\'єднання', 'error');
			$btn.prop('disabled', false).text('Скасувати');
		});
	});

	/* ── Use-all shortcut: click on balance value to auto-fill max ── */
	$(document).on('click', '.wcs-cb-has-balance', function () {
		var $block = $(this).closest('.wcs-cashback-block');
		var $input = $block.find('.wcs-cb-input');
		if ($input.length) {
			$input.val($input.attr('max')).trigger('focus');
		}
	});

	/* ── Helpers ───────────────────────────────────────── */
	function showMessage($el, text, type) {
		$el.text(text)
		   .removeClass('wcs-msg-success wcs-msg-error')
		   .addClass(type === 'success' ? 'wcs-msg-success' : 'wcs-msg-error')
		   .slideDown(200);

		setTimeout(function () { $el.slideUp(200); }, 4000);
	}

	function refreshPage() {
		// Use WC built-in update for checkout
		if ($('form.checkout').length) {
			$(document.body).trigger('update_checkout');
		}
		// For cart page — update cart
		if ($('.woocommerce-cart-form').length) {
			$(document.body).trigger('wc_update_cart');
			// Fallback: reload page after short delay
			setTimeout(function () {
				window.location.reload();
			}, 500);
		}
	}

})(jQuery);
