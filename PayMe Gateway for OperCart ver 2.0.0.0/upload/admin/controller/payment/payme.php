<?php 

class ControllerPaymentPayme extends Controller {

	private $error = array();

	public function install() {
	  
		$this->load->model('payment/payme');
		$this->model_payment_payme->CreateTable();		

		if (file_exists(DIR_APPLICATION . 'model/tool/event.php')) {
			
			$this->load->model('tool/event');
			$this->model_tool_event->addEvent('payme', 'pre.order.delete', 'payment/payme/on_order_delete');
		
		} else if (file_exists(DIR_APPLICATION . 'model/extension/event.php')) {
			
			$this->load->model('extension/event');
			$this->model_extension_event->addEvent('payme', 'pre.order.delete', 'payment/payme/on_order_delete');
		}

	}

	public function uninstall() {

		$this->load->model('payment/payme');
		$this->model_payment_payme->DropTable();

		if (file_exists(DIR_APPLICATION . 'model/tool/event.php')) {
			
			$this->load->model('tool/event');
			$this->model_tool_event->deleteEvent('payme');
		
		} else if (file_exists(DIR_APPLICATION . 'model/extension/event.php')) {
			
			$this->load->model('extension/event');
			$this->model_extension_event->deleteEvent('payme');			
		}
	
	}

	public function index() {

		// Initialize
		$data['payme_status'] = 0;
		$data['payme_test_enabled'] = 'Y';
		$data['payme_url'] = str_replace('admin/', '', HTTPS_SERVER)."?payme=pay";
		$data['payme_checkout_url'] = "https://checkout.paycom.uz";
		$data['payme_checkout_url_test'] = "https://test.paycom.uz";
		$data['payme_callback_pay_time'] = 0;
		$data['payme_product_information'] = 'N';

		$this->load->language('payment/payme');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('payme', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		} 

		// Check permission
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		// Return values of fields

		if (isset($this->request->post['payme_status'])) {
			$data['payme_status'] = $this->request->post['payme_status'];
		} elseif ($this->config->get('payme_status')) {
			$data['payme_status'] = $this->config->get('payme_status');
		}

		if (isset($this->request->post['payme_test_enabled'])) {
			$data['payme_test_enabled'] = $this->request->post['payme_test_enabled'];
		} elseif ($this->config->get('payme_test_enabled')) {
			$data['payme_test_enabled'] = $this->config->get('payme_test_enabled');
		}

		if (isset($this->request->post['payme_merchant_id'])) {
			$data['payme_merchant_id'] = $this->request->post['payme_merchant_id'];
		} else {
			$data['payme_merchant_id'] = $this->config->get('payme_merchant_id');
		}

		if (isset($this->request->post['payme_merchant_private_key'])) {
			$data['payme_merchant_private_key'] = $this->request->post['payme_merchant_private_key'];
		} else {
			$data['payme_merchant_private_key'] = $this->config->get('payme_merchant_private_key');
		}

		if (isset($this->request->post['payme_merchant_private_key_test'])) {
			$data['payme_merchant_private_key_test'] = $this->request->post['payme_merchant_private_key_test'];
		} else {
			$data['payme_merchant_private_key_test'] = $this->config->get('payme_merchant_private_key_test');
		}

		if (isset($this->request->post['payme_checkout_url'])) {
			$data['payme_checkout_url'] = $this->request->post['payme_checkout_url'];
		} elseif ($this->config->get('payme_checkout_url')) {
			$data['payme_checkout_url'] = $this->config->get('payme_checkout_url');
		}

		if (isset($this->request->post['payme_checkout_url_test'])) {
			$data['payme_checkout_url_test'] = $this->request->post['payme_checkout_url_test'];
		} elseif ($this->config->get('payme_checkout_url_test')) {
			$data['payme_checkout_url_test'] = $this->config->get('payme_checkout_url_test');
		}

		if (isset($this->request->post['payme_url'])) {
			$data['payme_url'] = $this->request->post['payme_url'];
		} elseif ($this->config->get('payme_url')) {
			$data['payme_url'] = $this->config->get('payme_url');
		}

		if (isset($this->request->post['payme_callback_pay_time'])) {
			$data['payme_callback_pay_time'] = $this->request->post['payme_callback_pay_time'];
		} elseif ($this->config->get('payme_callback_pay_time')) {
			$data['payme_callback_pay_time'] = $this->config->get('payme_callback_pay_time');
		}

		if (isset($this->request->post['payme_product_information'])) {
			$data['payme_product_information'] = $this->request->post['payme_product_information'];
		} elseif ($this->config->get('payme_product_information')) {
			$data['payme_product_information'] = $this->config->get('payme_product_information');
		}

		// Copy result of validation 

		if (isset($this->error['error_merchant_id'])) {
			$data['error_merchant_id'] = $this->error['error_merchant_id'];
		} else {
			$data['error_merchant_id'] = '';
		}

		if (isset($this->error['error_merchant_private_key'])) {
			$data['error_merchant_private_key'] = $this->error['error_merchant_private_key'];
		} else {
			$data['error_merchant_private_key'] = '';
		}

		if (isset($this->error['error_merchant_private_key_test'])) {
			$data['error_merchant_private_key_test'] = $this->error['error_merchant_private_key_test'];
		} else {
			$data['error_merchant_private_key_test'] = '';
		}

		if (isset($this->error['error_checkout_url'])) {
			$data['error_checkout_url'] = $this->error['error_checkout_url'];
		} else {
			$data['error_checkout_url'] = '';
		}

		if (isset($this->error['error_checkout_url_test'])) {
			$data['error_checkout_url_test'] = $this->error['error_checkout_url_test'];
		} else {
			$data['error_checkout_url_test'] = '';
		}

		if (isset($this->error['error_payme_url'])) {
			$data['error_payme_url'] = $this->error['error_payme_url'];
		} else {
			$data['error_payme_url'] = '';
		}

		$data['heading_title']     					= $this->language->get('heading_title');
		$data['hint_endpoint_url'] 					= $this->language->get('hint_endpoint_url');
		$data['button_save']       					= $this->language->get('button_save');
		$data['button_cancel']     					= $this->language->get('button_cancel');

		$data['text_set_payment_options'] 			= $this->language->get('text_set_payment_options');
		$data['text_link_to_personal_cabinet'] 		= $this->language->get('text_link_to_personal_cabinet');
		$data['text_yes'] 							= $this->language->get('text_yes');
		$data['text_no'] 							= $this->language->get('text_no');
		$data['text_enable'] 						= $this->language->get('text_enable');
		$data['text_disable'] 						= $this->language->get('text_disable');

		$data['entry_payme_status'] 				= $this->language->get('entry_payme_status');
		$data['entry_enable_test_mode'] 			= $this->language->get('entry_enable_test_mode');
		$data['entry_endpoint_url'] 				= $this->language->get('entry_endpoint_url');
		$data['entry_redirection_url'] 				= $this->language->get('entry_redirection_url');
		$data['entry_merchant_id'] 					= $this->language->get('entry_merchant_id');
		$data['entry_merchant_private_key'] 	 	= $this->language->get('entry_merchant_private_key');
		$data['entry_merchant_private_key_test'] 	= $this->language->get('entry_merchant_private_key_test');
		$data['entry_checkout_url'] 			 	= $this->language->get('entry_checkout_url');
		$data['entry_checkout_url_test'] 		 	= $this->language->get('entry_checkout_url_test');
		$data['entry_payme_url']				 	= $this->language->get('entry_payme_url');
		$data['entry_return_after_payment']      	= $this->language->get('entry_return_after_payment');
		$data['entry_add_product_information_']  	= $this->language->get('entry_add_product_information_'); 

		$data['payme_endpoint_url'] 				= str_replace('admin/', '', HTTPS_SERVER)."?route=payment/payme/callback";
		$data['payme_order_return'] 				= str_replace('admin/', '', HTTPS_SERVER)."?route=account/order";
		$data['payme_subscribe_api_url']      		= "https://checkout.paycom.uz/api";
		$data['payme_subscribe_api_url_test'] 		= "https://checkout.test.paycom.uz/api";
		$data['cancel'] 							= $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
		$data['action'] 							= $this->url->link('payment/payme', 	'token=' . $this->session->data['token'], 'SSL');

		$data['payme_callback_pay_time_list'] = array(
			array('value'=>0,	    'name'=>      $this->language->get('text_instantly')),
			array('value'=>15000,	'name'=>'15 '.$this->language->get('text_seconds')),
			array('value'=>30000,	'name'=>'30 '.$this->language->get('text_seconds')),
			array('value'=>60000,	'name'=>'60 '.$this->language->get('text_seconds'))
		);
		$data['breadcrumbs']   = array();
		$data['breadcrumbs'][] = array( 'text' => $this->language->get('text_home_page'),    'href' => $this->url->link('common/dashboard',  'token=' . $this->session->data['token'], 'SSL') );
		$data['breadcrumbs'][] = array( 'text' => $this->language->get('text_payment_list'), 'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL') );
        $data['breadcrumbs'][] = array( 'text' => $this->language->get('heading_title'),	 'href' => $this->url->link('payment/payme',     'token=' . $this->session->data['token'], 'SSL') );

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('payment/payme.tpl', $data));
	}

	protected function validate() {

		if (!$this->user->hasPermission('modify', 'payment/payme')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payme_merchant_id']) {
			$this->error['error_merchant_id'] = $this->language->get('error_merchant_id');
		}

		if (!$this->request->post['payme_merchant_private_key']) {
			$this->error['error_merchant_private_key'] = $this->language->get('error_merchant_private_key');
		}

		if (!$this->request->post['payme_merchant_private_key_test']) {
			$this->error['error_merchant_private_key_test'] = $this->language->get('error_merchant_private_key_test');
		}

		if (!$this->request->post['payme_checkout_url']) {
			$this->error['error_checkout_url'] = $this->language->get('error_checkout_url');
		}

		if (!$this->request->post['payme_checkout_url_test']) {
			$this->error['error_checkout_url_test'] = $this->language->get('error_checkout_url_test');
		}

		if (!$this->request->post['payme_url']) {
			$this->error['error_payme_url'] = $this->language->get('error_payme_url');
		}

		return !$this->error;
	}
}