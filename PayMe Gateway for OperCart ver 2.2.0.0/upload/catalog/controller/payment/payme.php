<?php
class ControllerPaymentPayme extends Controller {

	public function on_order_delete($route, $order_id){

		$this->load->model('payment/payme');
		$this->model_payment_payme->ReceiptsCancel($order_id);
	}
 
	public function callback() {

		$inputStream = file_get_contents("php://input");
		$inputArray = json_decode($inputStream, true);

		$parsingJsonError=false;

		switch (json_last_error()){

			case JSON_ERROR_NONE: break;

			default: $parsingJson=true; break;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->load->model('payment/payme');

		if ($parsingJsonError) {

			exit($this->model_payment_payme->GenerateErrorResponse(0,'-32700',__METHOD__));

		} else if ($_SERVER['REQUEST_METHOD']!='POST') {

			exit($this->model_payment_payme->GenerateErrorResponse($inputArray['id'],'-32300',__METHOD__));

		} else if(! isset($_SERVER['PHP_AUTH_USER'])) {

			exit($this->model_payment_payme->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__.'Point 1'));

		} else if(! isset($_SERVER['PHP_AUTH_PW'])) {

			exit($this->model_payment_payme->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__.'Point 2'));

		} else {

			$merchantKey="";

				 if ($this->config->get('payme_test_enabled')=='Y') $merchantKey=html_entity_decode($this->config->get('payme_merchant_private_key_test'));
			else if ($this->config->get('payme_test_enabled')=='N') $merchantKey=html_entity_decode($this->config->get('payme_merchant_private_key'));

			if( $merchantKey != html_entity_decode($_SERVER['PHP_AUTH_PW']) ) {

				exit($this->model_payment_payme->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__.'Point 3'));

			} else {

				if( $this->model_payment_payme->isMethodExists($inputArray)) {

					exit($this->model_payment_payme->$inputArray['method']($inputArray));

				} else {

					exit($this->model_payment_payme->GenerateErrorResponse($inputArray['id'],'-32601',__METHOD__));
				}
			}
		}
	}

	public function index() {

		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['merchant_id'] = $this->config->get('payme_merchant_id');
		$data['pay_time']    = $this->config->get('payme_callback_pay_time');
		$data['redirect']    = $this->config->get('payme_order_return');
		$data['order_id']    = trim($this->session->data['order_id']);
		$data['total']       = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false)*100;
		$data['detail'] 	 = "";
		
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

		if ( $this->config->get('payme_product_information') == 'Y' ) {

			$products=array( );

			$qry = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_product` o WHERE o.order_id = " . (int)$data['order_id'] );

			foreach ($qry->rows as $row) {

				array_push($products,array(

					"name"      => addslashes($row["name"]),
					"model"     => addslashes($row["model"]),
					"quantity"	=> $row["quantity"],
					"price"		=> $row['price'] ,
					"total"     => $row["total"],
					"tax"       => $row["tax"] 
				));
			}

			$productArray = array( "products"=> $products );
			$data['detail'] = base64_encode( json_encode($productArray) );
	 	}

		if (VERSION == '2.2.0.0') {

			return $this->load->view('payment/payme.tpl', $data);

		} else {

			return $this->load->view('default/template/payment/payme.tpl', $data);
		}
	}
}