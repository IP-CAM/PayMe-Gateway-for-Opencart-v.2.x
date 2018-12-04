<form id="form-payme" action="<?php echo $checkout_url; ?>" method="post">

  <input type="text" name="merchant"          value="<?php echo $merchant_id; ?>" />  
  <input type="text" name="amount"            value="<?php echo $total; ?>" />
  <input type="text" name="callback_timeout"  value="<?php echo $pay_time; ?>" /> 
  <input type="text" name="callback"          value="<?php echo $redirect; ?>" />   
  <input type="text" name="account[order_id]" value="<?php echo $order_id; ?>" />
  <input type="text" name="currency"          value="<?php echo $currency; ?>" />    
  
  <div class="buttons">
    <div class="pull-right">
      <input type="submit" value="<?php echo $button_confirm; ?>" class="btn btn-primary" />
    </div>
  </div> 
 
</form>
 