<?php
class ControllerPaymentPayme extends Controller {

	public function on_order_delete($order_id) {

		$this->load->model('payment/payme');
		$this->model_payment_payme->ReceiptsCancel($order_id);
	}

	public function callback() {

		$inputStream = file_get_contents("php://input");
		$inputArray = json_decode($inputStream, true);

		$parsingJsonError=false;

		switch (json_last_error()) {

			case JSON_ERROR_NONE: break;

			default: $parsingJson=true; break;
		}

		$this->load->model('payment/payme');

		if ($parsingJsonError) {

			$this->model_payment_payme->GenerateErrorResponse(0,'-32700',__METHOD__,false);

		} else {

			$this->model_payment_payme->CheckRequest($inputArray);

			if( $this->model_payment_payme->result ) {

				$this->model_payment_payme->$inputArray['method']($inputArray); 
			}
		}

		exit($this->model_payment_payme->resultArray);
	}

	public function index() {

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['merchant_id'] = $this->config->get('payme_merchant_id');
		$data['pay_time']    = $this->config->get('payme_callback_pay_time');
		$data['information'] = $this->config->get('payme_product_information');
		$data['redirect']    = $this->config->get('payme_order_return');

		$data['order_id']    = trim($this->session->data['order_id']);
		$data['total']       = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)*100;

			 if( $order_info['currency_code'] == 'UZS') $data['currency'] = 860;
		else if( $order_info['currency_code'] == 'USD') $data['currency'] = 840;
		else if( $order_info['currency_code'] == 'RUB') $data['currency'] = 643;
		else if( $order_info['currency_code'] == 'EUR') $data['currency'] = 978;
		else											$data['currency'] = 860;

		if ($this->config->get('payme_test_enabled')=='Y') {

			$data['checkout_url'] = $this->config->get('payme_checkout_url_test');

		} else {

			$data['checkout_url'] = $this->config->get('payme_checkout_url');
		}

		$this->load->model('payment/payme');
		$this->model_payment_payme->SaveOrder($data['total'], $data['order_id'], $data['order_id'], $this->config->get('payme_test_enabled'));

		return $this->load->view('default/template/payment/payme.tpl', $data);
	}
}