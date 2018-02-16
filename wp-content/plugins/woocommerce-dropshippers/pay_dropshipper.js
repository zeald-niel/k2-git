/**
* File: dropshipper-new-order-email.php
* Author: ArticNet LLC.
**/

function payDropshipper(email, howmuch, currency) {
	htmlz = '<form id="paypalform" name="_xclick" action="https://www.paypal.com/cgi-bin/webscr" method="post">';
	htmlz += '<input type="hidden" name="cmd" value="_cart">';
	htmlz += '<input type="hidden" name="business" value="'+ email +'">';
	htmlz += '<input type="hidden" name="currency_code" value="'+ currency +'">';
	htmlz += '<input type="hidden" name="lc" value="EN">';
	htmlz += '<input type="hidden" name="upload" value="1">';
	htmlz += '<input type="hidden" name="charset" value="utf-8">';
	htmlz += '<input type="hidden" name="item_name_1" value="Dropshipper Payment">';
	htmlz += '<input type="hidden" name="amount_1" value="'+ howmuch +'">';
	htmlz += '<input type="hidden" name="tax_1" value="0">';
	htmlz += '</form>';
	jQuery('body').append(htmlz);
	document.getElementById("paypalform").submit();
	jQuery('#paypalform').remove();
}