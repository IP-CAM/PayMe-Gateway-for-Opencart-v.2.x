<?php

class ModelExtensionPaymentPayme extends Model {

	public function isMethodExists($inputArray) {
		
		if ( method_exists($this, $inputArray['method'])) {
			
			return true;
			
		} else {
			
			return false;
		}
	}
	public function getMethod($address, $total) {

		$this->load->language('extension/payment/payme');

		$status = true;
		
		if ($total > 0) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {

			$method_data = array(
				'code'       => 'payme',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('payme_sort_order')
			);
		}

		return $method_data;
	}

	public function CheckPerformTransaction($inputArray, $actionFromCreateTransaction=false) {

		$qry = $this->db->query("SELECT 
									t.state, 
									t.amount,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.cms_order_id = '".$this->db->escape($inputArray['params']['account']['order_id']). "'");

		if ($qry->num_rows !=1) {
			
			return $this->GenerateErrorResponse($inputArray['id'], '-31050', __METHOD__);

		} else {
		
			if($qry->row['state'] != 0) {

				return $this->GenerateErrorResponse($inputArray['id'], '-31050', __METHOD__);

			} else if($qry->row['amount'] != $inputArray['params']['amount']) {

				return $this->GenerateErrorResponse($inputArray['id'], '-31001', __METHOD__);  

			} else {

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistory($qry->row['order_id'], $this->config->get('payme_order_status_id'));
				
				if ($actionFromCreateTransaction) {

					//Action from CreateTransaction
					
					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions t SET 
									t.state = 1,
									t.paycom_time='".$this->db->escape($inputArray['params']['time'])."', 
									t.paycom_time_datetime='".$this->timestamp2datetime($this->db->escape($inputArray['params']['time']))."',
									t.paycom_transaction_id='".$this->db->escape($inputArray['params']['id'])."' 
								 WHERE t.cms_order_id = '".$this->db->escape($inputArray['params']['account']['order_id']). "'" );
					 
					$this->model_checkout_order->addOrderHistory( $inputArray['params']['account']['order_id'] , "2");
					
					return $this->GeneratePositiveResponse($inputArray['id'],$inputArray['params']['account']['order_id'],$inputArray['params']['id'],1);
					
				} else {
					
					$responseArray = array(); 
					$responseArray['result'] = array ( 'allow' => true );

					return json_encode($responseArray);
				}
			}
		}
	}

	public function CreateTransaction($inputArray) {

		$qry = $this->db->query("SELECT 
									t.state,
									t.paycom_time,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'");

		if ($qry->num_rows >1) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__.">1");

		} else if ($qry->num_rows ==1)	{

			$paycom_time_integer=(int)$qry->row['paycom_time'];
			$paycom_time_integer=$paycom_time_integer+43200000;

			if($qry->row['state'] != 1) {

				return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__." !=1 ");

			} else if( $paycom_time_integer <= $this->timestamp2milliseconds(time())) {

				$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'] , "7" );
				
				return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__." timeout");

			} else {

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "2");
				
				return $this->GeneratePositiveResponse($inputArray['id'],$inputArray['params']['account']['order_id'],$inputArray['params']['id'], 1);
			}

		} else if ($qry->num_rows ==0){

			return $this->CheckPerformTransaction($inputArray, true);
		}
	}

	public function PerformTransaction($inputArray) {

		$qry = $this->db->query("SELECT 
									t.state,
									t.paycom_time,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

		if ($qry->num_rows ==0) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__);

		} else if ($qry->num_rows >1) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__);

		} else if ($qry->num_rows==1) {

			if($qry->row['state'] != 1) {

				if($qry->row['state'] != 2) {

					return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__);

				} else {

					return $this->GeneratePositiveResponse($inputArray['id'], $qry->row['order_id'], $inputArray['params']['id'], 2); 
				}

			} else {

				$paycom_time_integer=(int)$qry->row['paycom_time'];
				$paycom_time_integer=$paycom_time_integer+43200000;

				if( $paycom_time_integer <= $this->timestamp2milliseconds(time()) ) {

					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

					$this->load->model('checkout/order');
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
					
					return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__);

				} else {

					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = 2, perform_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

					$this->load->model('checkout/order');
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "5");
					
					return $this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 2); 
				}
			}
		}
	}

	public function CancelTransaction($inputArray) {

		$qry = $this->db->query("SELECT 
									t.state, 
									t.paycom_time,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
		if ($qry->num_rows ==0) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__);

		} else if ($qry->num_rows >1) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__);

		} else if ($qry->num_rows==1) {

			if ($qry->row['state'] == 1) {

				$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, cancel_time =NOW(),reason = ".$this->db->escape($inputArray['params']['reason']). " WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

				$this->load->model('checkout/order');
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
				
				return $this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3); 

			} else {

				if($qry->row['state'] != 2) {

					return $this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3); 

				} else {

					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -2, cancel_time =NOW(),reason = ".$this->db->escape($inputArray['params']['reason']). " WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

					$this->load->model('checkout/order');
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
					
					return $this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3);
				}
			}
		}
	}

	public function CheckTransaction($inputArray) {

		$qry = $this->db->query("SELECT 
									 t.create_time,
									 t.state,
									 t.perform_time,
									 t.cancel_time,
									 t.reason,
									 t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );

		if ($qry->num_rows ==0) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__);

		} else if ($qry->num_rows >1) {

			return $this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__);

		} else if ($qry->num_rows==1) {

			$responseArray = array(); 
			$responseArray['id'] = $inputArray['id'];
			$responseArray['result'] = array(

				"create_time"	=> $this->datetime2timestamp($qry->row['create_time'])*1000,
				"perform_time"	=> $this->datetime2timestamp($qry->row['perform_time'])*1000,
				"cancel_time"	=> $this->datetime2timestamp($qry->row['cancel_time'])*1000,
				"transaction"	=> $qry->row['order_id'],
				"state"			=> (int)$qry->row['state'],
				"reason"		=> (is_null($qry->row['reason'])?null:(int)$qry->row['reason'])
			);

			return json_encode($responseArray);
		}
	}

	public function GetStatement($inputArray) { 

		$qry = $this->db->query("SELECT 
									 t.paycom_time,
									 t.paycom_transaction_id,
									 t.amount,
									 t.order_id,
									 t.create_time,
									 t.perform_time,
									 t.cancel_time,
									 t.state,
									 t.reason,
									 t.receivers

								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_time_datetime>='".$this->timestamp2datetime($this->db->escape($inputArray['params']['from']))."' and 
								      t.paycom_time_datetime<='".$this->timestamp2datetime($this->db->escape($inputArray['params']['to']))  ."'
								ORDER BY t.paycom_time_datetime " );

		$responseArray = array();
		$transactions=array( );

		foreach ($qry->rows as $row) {

			array_push($transactions,array(

				"id"           => $row["paycom_transaction_id"],
				"time"		   => $row['paycom_time']  ,
				"amount"       => $row["amount"],
				"account"	   => array("order_id" => $row["order_id"]),
				"create_time"  => (is_null($row['create_time']) ? null: $this->datetime2timestamp( $row['create_time']) ) ,
				"perform_time" => (is_null($row['perform_time'])? null: $this->datetime2timestamp( $row['perform_time'])) ,
				"cancel_time"  => (is_null($row['cancel_time']) ? null: $this->datetime2timestamp( $row['cancel_time']) ) ,
				"transaction"  => $row["order_id"],
				"state"	       => (int) $row['state'],
				"reason"       => (is_null($row['reason'])?null:(int) $row['reason']) ,
				
				"receivers"    => null
			)) ;
		}

		$responseArray['result'] = array( "transactions"=> $transactions );		

		return json_encode($responseArray);		
	}

	public function ChangePassword($inputArray) {

		if (! array_key_exists("params", $inputArray)) {

			return $this->GenerateErrorResponse($inputArray['id'], '-32600', __METHOD__." params");

		} else if (! array_key_exists("password", $inputArray["params"])) {

			return $this->GenerateErrorResponse($inputArray['id'], '-32600', __METHOD__." password");

		} else {

			if ($this->config->get('payme_test_enabled')=='Y') {

				$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='".$inputArray['params']['password']."' WHERE `key` = 'payme_merchant_private_key_test' AND `group` = 'payme'");
			}
			else if ($this->config->get('payme_test_enabled')=='N'){

				$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='".$inputArray['params']['password']."' WHERE `key` = 'payme_merchant_private_key' AND `group` = 'payme'");
			}

			$responseArray = array(); 
			$responseArray['result'] = array ( 'success' =>true );

			return json_encode($responseArray);
		}
	}

	public function GeneratePositiveResponse($request_id,$order_id,$transaction_id,$responseType) {

		$qry=$this->db->query("SELECT 
							     t.create_time,
							     t.state,
								 t.perform_time,
								 t.cancel_time
						    FROM " . DB_PREFIX . "payme_transactions t
							WHERE t.cms_order_id = '".$this->db->escape($order_id). "'" );

		if ($qry->num_rows ==1 ) {

			$responseArray = array(); 
			$responseArray['id']    = $request_id;

			if ($responseType==1) {

				$responseArray['result'] = array(

						"create_time"	=> $this->datetime2timestamp($qry->row['create_time'])*1000,
						"transaction"	=> $order_id,
						"state"			=> (int)$qry->row['state']
				);

			} else if ($responseType==2) {

				$responseArray['result'] = array(

						"perform_time"	=> $this->datetime2timestamp($qry->row['perform_time'])*1000,
						"transaction"	=> $order_id,
						"state"			=> (int)$qry->row['state']
				);
			} else if ($responseType==3) {

				$responseArray['result'] = array(

						"cancel_time"	=> $this->datetime2timestamp($qry->row['cancel_time'])*1000,
						"transaction"	=> $order_id,
						"state"			=> (int)$qry->row['state']
				);
			}

			return json_encode($responseArray);

		} else {

			$this->GenerateErrorResponse($request_id, '-31008', __METHOD__);  
		}
	}

	public function GenerateErrorResponse($request_id, $codeOfError, $data) {

		$responseArray['id']    = $request_id;
		$responseArray['error'] = array (

						'code'   =>(int) $codeOfError,
						'message'=> array(
										 "ru"=>$this->getGenerateErrorText($codeOfError,"ru"),
										 "uz"=>$this->getGenerateErrorText($codeOfError,"uz"),
										 "en"=>$this->getGenerateErrorText($codeOfError,"en"),
										 "data" =>$data." order_id"
						));

		return json_encode($responseArray);
	}

	//Utils 

	public function datetime2timestamp($datetime) {

        if ($datetime) { 

            return strtotime($datetime);
        }

        return $datetime;
    }

	public function timestamp2milliseconds($timestamp) {
        // is it already as milliseconds
        if (strlen((string)$timestamp) == 13) {
            return $timestamp;
        }

        return $timestamp * 1000;
    }

	public function timestamp2datetime($timestamp){
        // if as milliseconds, convert to seconds
        if (strlen((string)$timestamp) == 13) {
            $timestamp = $this->timestamp2seconds($timestamp);
        }

        // convert to datetime string
        return date('Y-m-d H:i:s', $timestamp);
    }

	public function timestamp2seconds($timestamp) {
        // is it already as seconds
        if (strlen((string)$timestamp) == 10) {
            return $timestamp;
        }

        return floor(1 * $timestamp / 1000);
    }

	public function SaveOrder($amount,$orderId,$cmsOrderId,$isFlagTest) {
		
		$qry = $this->db->query("SELECT 
									t.state, 
									t.amount,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.cms_order_id = '".(is_null( $cmsOrderId )? 0:$cmsOrderId )."' and t.order_id =".(is_null( $orderId )? 0:$orderId). " and t.amount=".$amount);

		if ($qry->num_rows ==0) {
			
			$this->db->query(

			 "INSERT INTO ". DB_PREFIX ."payme_transactions SET 
			 `create_time`=NOW(),
			 `amount`=".$amount.",
			 `state`=0,
			 `order_id`=".(is_null( $orderId )? 0:$orderId).",
			 `cms_order_id`='".(is_null( $cmsOrderId )? 0:$cmsOrderId )."',
			 `is_flag_test`='".$isFlagTest."'"
			);
		}		
	}

	public function getGenerateErrorText($codeOfError,$codOfLang ){

		$listOfError=array ('-31001' => array(
		                                  "ru"=>'Неверная сумма.',
						                  "uz"=>'Неверная сумма.',
							              "en"=>'Неверная сумма.'
										),
							'-31003' => array(
		                                  "ru"=>'Транзакция не найдена.',
						                  "uz"=>'Транзакция не найдена.',
							              "en"=>'Транзакция не найдена.'
										),
							'-31008' => array(
		                                  "ru"=>'Невозможно выполнить операцию.',
						                  "uz"=>'Невозможно выполнить операцию.',
							              "en"=>'Невозможно выполнить операцию.'
										),
							'-31050' => array(
		                                  "ru"=>'Ошибки связанные с неверным пользовательским вводом account. Например: введённый логин не найден, введённый номер телефона не найден и т.д. Локализованное поле message обязательно. Поле data должно содержать название субполя account.',
						                  "uz"=>'Ошибки связанные с неверным пользовательским вводом account. Например: введённый логин не найден, введённый номер телефона не найден и т.д. Локализованное поле message обязательно. Поле data должно содержать название субполя account.',
							              "en"=>'Ошибки связанные с неверным пользовательским вводом account. Например: введённый логин не найден, введённый номер телефона не найден и т.д. Локализованное поле message обязательно. Поле data должно содержать название субполя account.'
										),
							'-32300' => array(
		                                  "ru"=>'Ошибка возникает если метод запроса не POST.',
						                  "uz"=>'Ошибка возникает если метод запроса не POST.',
							              "en"=>'Ошибка возникает если метод запроса не POST.'
										),
							'-32600' => array(
		                                  "ru"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации',
						                  "uz"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации',
							              "en"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации'
										),
							'-32700' => array(
		                                  "ru"=>'Ошибка парсинга JSON.',
						                  "uz"=>'Ошибка парсинга JSON.',
							              "en"=>'Ошибка парсинга JSON.'
										),
							'-32600' => array(
		                                  "ru"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.',
						                  "uz"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.',
							              "en"=>'Отсутствуют обязательные поля в RPC-запросе или тип полей не соответствует спецификации.'
										),
							'-32601' => array(
		                                  "ru"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.',
						                  "uz"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.',
							              "en"=>'Запрашиваемый метод не найден. В RPC-запросе имя запрашиваемого метода содержится в поле data.'
										),
							'-32504' => array(
		                                  "ru"=>'Недостаточно привилегий для выполнения метода.',
						                  "uz"=>'Недостаточно привилегий для выполнения метода.',
							              "en"=>'Недостаточно привилегий для выполнения метода.'
										),
							'-32400' => array(
		                                  "ru"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.',
						                  "uz"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.',
							              "en"=>'Системная (внутренняя ошибка). Ошибку следует использовать в случае системных сбоев: отказа базы данных, отказа файловой системы, неопределенного поведения и т.д.'
										)
						    );

		return $listOfError[$codeOfError][$codOfLang];
	}
}