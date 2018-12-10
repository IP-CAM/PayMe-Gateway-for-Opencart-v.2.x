<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">

        <button type="submit" form="form-sanjar" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>

	  <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach ($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php } ?>
      </ul>
    </div>
  </div>  
  
  <div class="container-fluid">

    <?php if ($error_warning) { ?>
    <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?php echo $error_warning; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>

    <div class="panel panel-default">

      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_set_payment_options; ?></h3>
      </div>

      <div class="panel-body">

        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-sanjar" class="form-horizontal">

			<div class="form-group">
				<div class="col-sm-12">
					<label><?php echo $text_link_to_personal_cabinet; ?> </label>
				</div>
			</div>

			<div class="form-group">
				<label class="col-sm-2 control-label" ><?php echo $entry_endpoint_url; ?></label>
				<div class="col-sm-10">
					<div style="float: left; padding: 30px 30px 0px 30px;font-size: 20px;color: #fff; background-color: #816ff1; width: 100%;">
					<em><?php echo $payme_endpoint_url; ?></em>
					<p class="description" style="margin-top: 40px; color: #fff;font-size: 14px;">
						<?php echo $hint_endpoint_url; ?>
					</p>
				</div>
				</div>
            </div>

			<div class="form-group">
				<label class="col-sm-2 control-label"><?php echo $entry_redirection_url; ?></label>
				  <div class="col-sm-10">
					<?php echo $payme_order_return; ?>
				</div>
            </div>

			<div class="form-group">
				<label class="col-sm-2 control-label" for="payme-status"><?php echo $entry_payme_status; ?></label>
				<div class="col-sm-10">
				  <select name="payme_status" id="payme-status" class="form-control">
					<?php if ($payme_status) { ?>
					<option value="1" selected="selected"><?php echo $text_enable; ?></option>
					<option value="0"><?php echo $text_disable; ?></option>
					<?php } else { ?>
					<option value="1"><?php echo $text_enable; ?></option>
					<option value="0" selected="selected"><?php echo $text_disable; ?></option>
					<?php } ?>
				  </select>
				</div>
			</div>

			<div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-test-enabled"><?php echo $entry_enable_test_mode; ?></label>
              <div class="col-sm-10">
                <select name="payme_test_enabled" id="payme-test-enabled" class="form-control">
                  <?php if ($payme_test_enabled == "Y") { ?>
                  <option value="Y" selected="selected"><?php echo $text_yes; ?></option>
                  <option value="N">                    <?php echo $text_no; ?></option>
                  <?php } else { ?>	
                  <option value="Y">					<?php echo $text_yes; ?></option>
                  <option value="N" selected="selected"><?php echo $text_no; ?></option>
                  <?php  }  ?>
                </select>
              </div>
            </div>

			<div class="form-group required">
				<label class="col-sm-2 control-label" for="payme-merchant"><?php echo $entry_merchant_id; ?></label>
				<div class="col-sm-10">
				  <input type="text" name="payme_merchant_id" 
				  value="<?php echo $payme_merchant_id; ?>" placeholder="<?php echo $entry_merchant_id; ?>" id="payme-merchant" class="form-control" />
				  <?php if ($error_merchant_id) { ?>
				  <div class="text-danger"><?php echo $error_merchant_id; ?></div>
				  <?php } ?>
				</div>
            </div>

			<div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-private-key"><?php echo $entry_merchant_private_key; ?></label>
              <div class="col-sm-10">
                <textarea name="payme_merchant_private_key" rows="3" 
				placeholder="<?php echo $entry_merchant_private_key;?>" id="payme-private-key" 
				class="form-control"><?php echo $payme_merchant_private_key;?></textarea>

				<?php  if ($error_merchant_private_key) { ?>
                  <div class="text-danger"><?php echo $error_merchant_private_key;?></div>
                <?php  } ?>
              </div>
            </div>

			<div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-public-key"><?php echo $entry_merchant_private_key_test;?></label>
              <div class="col-sm-10">

                <textarea name="payme_merchant_private_key_test" rows="3" 
				placeholder="<?php echo $entry_merchant_private_key_test;?>" id="payme-public-key" 
				class="form-control"><?php echo $payme_merchant_private_key_test;?></textarea>

				<?php  if ($error_merchant_private_key_test) { ?>
                  <div class="text-danger"><?php echo $error_merchant_private_key_test;?></div>
                 <?php  }  ?>
              </div>
            </div>

		  <div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-checkout-url"><?php echo $entry_checkout_url;?></label>
              <div class="col-sm-10">

                <input type="text" name="payme_checkout_url" value="<?php echo $payme_checkout_url;?>" 
				placeholder="<?php echo $entry_checkout_url;?>" id="payme-checkout-url" class="form-control"/>

				<?php  if ($error_checkout_url) { ?>
                <div class="text-danger"><?php echo $error_checkout_url;?></div>
                <?php  }  ?>
              </div>
            </div>

		  <div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-checkout-url-test"><?php echo $entry_checkout_url_test;?></label>
              <div class="col-sm-10">

                <input type="text" name="payme_checkout_url_test" value="<?php echo $payme_checkout_url_test;?>" 
				placeholder="<?php echo $entry_checkout_url_test;?>" id="payme-checkout-url-test" class="form-control"/>

                <?php  if ($error_checkout_url_test) { ?>
                <div class="text-danger"><?php echo $error_checkout_url_test;?></div>
                <?php  }  ?>
              </div>
            </div>

			<div class="form-group required">
              <label class="col-sm-2 control-label" for="payme-url"><?php echo $entry_payme_url;?></label>
              <div class="col-sm-10">

                <input type="text" name="payme_url" value="<?php echo $payme_url;?>" 
				placeholder="<?php echo $entry_payme_url;?>" id="payme-url" class="form-control"/>

                <?php  if ($error_payme_url) { ?>
                <div class="text-danger">{{ error_payme_url }}</div>
                <?php  }  ?>

              </div>
            </div>

			<div class="form-group">
              <label class="col-sm-2 control-label" for="payme-callback-pay-time"><?php echo $entry_return_after_payment; ?></label>
              <div class="col-sm-10">

                <select name="payme_callback_pay_time" id="payme-callback-pay-time" class="form-control">

				  <?php foreach ($payme_callback_pay_time_list as $callback_pay_time) { ?> 

					<?php if ($callback_pay_time['value'] == $payme_callback_pay_time) { ?> 

					  <option value="<?php echo $callback_pay_time['value']; ?>" selected="selected"><?php echo $callback_pay_time['name']; ?></option>
                    <?php } else { ?>
					  <option value="<?php echo $callback_pay_time['value']; ?>">                    <?php echo $callback_pay_time['name']; ?></option> 
                    <?php } ?>
                    <?php } ?>
                </select>

              </div>
            </div> 

			<div class="form-group">
              <label class="col-sm-2 control-label" for="payme-product-information"><?php echo $entry_add_product_information_; ?></label>
              <div class="col-sm-10">
                <select name="payme_product_information" id="payme-product-information" class="form-control">
                  <?php if ($payme_product_information == 'Y' ) { ?> 
                  <option value="Y" selected="selected"><?php echo $text_yes; ?></option>
                  <option value="N">                    <?php echo $text_no; ?></option>
                 <?php } else { ?>	
                  <option value="Y">                    <?php echo $text_yes; ?></option>
                  <option value="N" selected="selected"><?php echo $text_no; ?></option>
                  <?php } ?>
                </select>

				 <input type="hidden" name="payme_order_status_id" value="1" />
				 <input type="hidden" name="payme_sort_order"      value="0" />

				 <input type="hidden" name="payme_endpoint_url"    value="<?php echo $payme_endpoint_url;?>"/>
				 <input type="hidden" name="payme_order_return"    value="<?php echo $payme_order_return;?>"  />

				 <input type="hidden" name="payme_subscribe_api_url"       value="<?php echo $payme_subscribe_api_url;?>"/>
				 <input type="hidden" name="payme_subscribe_api_url_test"  value="<?php echo $payme_subscribe_api_url_test;?>" />

              </div>
            </div>

        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>