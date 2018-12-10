<form id="form-payme" action="<?php echo $checkout_url; ?>" method="post">

<input type="hidden" name="merchant"			value="<?php echo $merchant_id; ?>"/>
<input type="hidden" name="amount"				value="<?php echo $total; ?>"/>
<input type="hidden" name="callback_timeout"	value="<?php echo $pay_time; ?>"/>
<input type="hidden" name="callback"			value="<?php echo $redirect; ?>"/>
<input type="hidden" name="account[order_id]"	value="<?php echo $order_id; ?>"/>
<input type="hidden" name="currency"			value="<?php echo $currency; ?>"/>
<input type="hidden" name="detail"			    value="<?php echo $detail; ?>"/>

<div class="buttons">
	<div class="pull-right">
		<input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary"/>
	</div>
</div>

</form>
 