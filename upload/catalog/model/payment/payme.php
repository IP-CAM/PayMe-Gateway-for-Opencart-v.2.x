<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

class ModelPaymentPayme extends Model {
	
	public $result = true;
	public $resultArray=null;	

	public function ReceiptsCancel($order_id) {
	
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);		
		
		if ($order_info['payment_method'] == 'Payme' ) {
			
			if ($this->config->get('payme_test_enabled')=='Y'){
					 
				$url=$this->config->get('payme_subscribe_api_url_test');
				$key=$this->config->get('payme_merchant_private_key_test');
				
			} else if ($this->config->get('payme_test_enabled')=='N') {
				
				$url=$this->config->get('payme_subscribe_api_url');
				$key=$this->config->get('payme_merchant_private_key');
			}
			
			$qry = $this->db->query("SELECT 
									t.paycom_transaction_id,  
									FROM " . DB_PREFIX . "payme_transactions t 
									WHERE t.cms_order_id = '".$order_id. "'" );
								
			if ($qry->num_rows ==1)	{
				
				if ($qry->row['paycom_transaction_id']) {
					
					$ch = curl_init($url);			
			
					$jsonData = array(); 
					$jsonData['id'] =(int) $order_id;
					$jsonData['method'] = "receipts.cancel";
					$jsonData['params'] =array('id'=>$qry->row['paycom_transaction_id']);
					$jsonDataEncoded = json_encode($jsonData);
					
					error_log($url."   jsonDataEncoded =".$jsonDataEncoded);		
					 
					curl_setopt($ch, CURLOPT_POST, 1);			 
					curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonDataEncoded);			 
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Auth: '.$this->config->get('payme_merchant_id').":".$key)); 
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$result = curl_exec($ch);		
					
					//var_dump(json_decode($result, true));
					error_log(" 1  ReceiptsCancel end ".$result);
					
					curl_close($ch);
				}
			}
		}	
    }	
 
	public function CheckRequest($inputArray) { 
	 
		//Check Method Post 32300		
		if ($_SERVER['REQUEST_METHOD']!='POST') {
			
			$this->GenerateErrorResponse($inputArray['id'],'-32300',__METHOD__,false);
			
		} else {
			
			// Check Auth 32504		
				 if(! isset($_SERVER['PHP_AUTH_USER'])) $this->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__,false);
			else if(! isset($_SERVER['PHP_AUTH_PW']))   $this->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__,false);
			else {
				
				// Check in Db by merchant_id 32504
				if ($this->result){	
				
					$merchantKey="";
					
						 if ($this->config->get('payme_test_enabled')=='Y') $merchantKey=html_entity_decode($this->config->get('payme_merchant_private_key_test'));
					else if ($this->config->get('payme_test_enabled')=='N') $merchantKey=html_entity_decode($this->config->get('payme_merchant_private_key'));
				 

					if( $merchantKey != html_entity_decode($_SERVER['PHP_AUTH_PW']) ) {						
						
						$this->GenerateErrorResponse($inputArray['id'],'-32504',__METHOD__,false);
						
					} else {
						
						// Check Method Name 32601						
						if(! method_exists($this, $inputArray['method'])) {
							
							$this->GenerateErrorResponse($inputArray['id'],'-32601',__METHOD__,false);
						}
					}	 
				}
			}
		}  
	}
	
	public function CheckPerformTransaction($inputArray) {	
		
		$qry = $this->db->query("SELECT 
									t.state, 
									t.amount,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.cms_order_id = '".$this->db->escape($inputArray['params']['account']['order_id']). "'" );
		
		// Check is single and exists
		if ($qry->num_rows !=1)			
		$this->GenerateErrorResponse($inputArray['id'], '-31050', __METHOD__, false ); 	
	
		if ($this->result){
		
			// Check status of transaction
			if($qry->row['state'] != 0) {
				
				$this->GenerateErrorResponse($inputArray['id'], '-31050', __METHOD__, false );  
			}	
			// Check amount of transaction
			else if($qry->row['amount'] != $inputArray['params']['amount']) {
				
				$this->GenerateErrorResponse($inputArray['id'], '-31001', __METHOD__, false );  
				
			} else {
				
				// All things is OK
				$responseArray = array(); 
				$responseArray['result'] = array ( 'allow' =>true );				
				$this->resultArray=json_encode($responseArray);	
	
				// Status Pending 1
				$this->load->model('checkout/order');		
				$this->model_checkout_order->addOrderHistory($qry->row['order_id'], $this->config->get('payme_order_status_id')); 
			}
		}		
	}
	
	public function CreateTransaction($inputArray) {
		
		$qry = $this->db->query("SELECT 
									t.state,
									t.paycom_time,
									t.order_id
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
		
		// Check is single and exists
		if ($qry->num_rows >1)	{
			
			$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__.">1", false );  
		
		} else if ($qry->num_rows ==1)	{
			
			// Check status of transaction
			if($qry->row['state'] != 1) {
				
				$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__." !=1 ", false );
			} 
			// Check timeout
			else if($qry->row['paycom_time']+43200000 <= $this->timestamp2milliseconds(time())) {

				$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__." timeout", false );
				
				//Cencel transaction
				$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
				
				// Status Canceled 7
				$this->load->model('checkout/order');		
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'] , "7" );
			
			} else {

				// All things is OK
				$this->GeneratePositiveResponse($inputArray['id'],$inputArray['params']['account']['order_id'],$inputArray['params']['id'], 1); 
				
				//Блокировка заказ status Processing 2
				$this->load->model('checkout/order');		
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "2");
			}
		} else if ($qry->num_rows ==0)	{
			
			$this->CheckPerformTransaction($inputArray,1);
			
			if ($this->result){ 
			
				$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions t SET 
				t.state = 1,
				t.paycom_time='".$this->db->escape($inputArray['params']['time'])."', 
				t.paycom_time_datetime='".$this->timestamp2datetime($this->db->escape($inputArray['params']['time']))."',
				t.paycom_transaction_id='".$this->db->escape($inputArray['params']['id'])."' 
				WHERE t.cms_order_id = '".$this->db->escape($inputArray['params']['account']['order_id']). "'" );
				
				// All things is OK
				$this->GeneratePositiveResponse($inputArray['id'],$inputArray['params']['account']['order_id'],$inputArray['params']['id'],1); 
				
				//Блокировка заказ status Processing 2
				$this->load->model('checkout/order');		
				$this->model_checkout_order->addOrderHistory( $inputArray['params']['account']['order_id'] , "2");
			}
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
		
			$this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__, false );
			
		} else if ($qry->num_rows >1) {
			
			$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__, false );
			
		} else if ($qry->num_rows==1) {
			
			// Check status of transaction
			if($qry->row['state'] != 1) {
				
				if($qry->row['state'] != 2) {
					
					$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__, false );
					
				} else {
					
					// All things is OK 
					$this->GeneratePositiveResponse($inputArray['id'], $qry->row['order_id'], $inputArray['params']['id'], 2); 
				}
				
			} else {
				
				// Check timeout
				if($qry->row['paycom_time']+43200000 <= $this->timestamp2milliseconds(time())) {
					
					$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__, false );
				
					//Cencel transaction
					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, reason = 4, cancel_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
					
					// Status Canceled 7
					$this->load->model('checkout/order');		
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
					
				} else {
					
					// Update state
					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = 2, perform_time =NOW() WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
					
					// All things is OK
					$this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 2); 
					
					// Status Complete 5
					$this->load->model('checkout/order');		
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "5");
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
		
			$this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__, false );
			
		} else if ($qry->num_rows >1) {
			
			$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__, false );
			
		} else if ($qry->num_rows==1) {
			
			// Check status of transaction
			if($qry->row['state'] == 1) {
				
				//Cencel transaction
				$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -1, cancel_time =NOW(),reason = ".$this->db->escape($inputArray['params']['reason']). " WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
				
				// All thing is OK 
				$this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3); 
				
				// Status Canceled 7
				$this->load->model('checkout/order');		
				$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
				
			} else {
				
				if($qry->row['state'] != 2) {
					
					// All thing is OK
					$this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3); 
					
				} else {
					
					//Cencel transaction
					$this->db->query("UPDATE " . DB_PREFIX . "payme_transactions SET state = -2, cancel_time =NOW(),reason = ".$this->db->escape($inputArray['params']['reason']). " WHERE paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
					
					// All thing is OK
					$this->GeneratePositiveResponse($inputArray['id'],$qry->row['order_id'],$inputArray['params']['id'], 3);

					// Status Canceled 7
					$this->load->model('checkout/order');		
					$this->model_checkout_order->addOrderHistory( $qry->row['order_id'], "7" );
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
									 t.reason
								FROM " . DB_PREFIX . "payme_transactions t 
								WHERE t.paycom_transaction_id = '".$this->db->escape($inputArray['params']['id']). "'" );
		if ($qry->num_rows ==0) {
		
			$this->GenerateErrorResponse($inputArray['id'], '-31003', __METHOD__, false );
			
		} else if ($qry->num_rows >1) {
			
			$this->GenerateErrorResponse($inputArray['id'], '-31008', __METHOD__, false );
			
		} else if ($qry->num_rows==1) {
			
			$responseArray = array(); 
			$responseArray['id'] = $inputArray['id'];				
			$responseArray['result'] = array(
				
				"create_time"	=> $this->datetime2timestamp($qry->row['create_time'])*1000,
				"perform_time"	=> $this->datetime2timestamp($qry->row['perform_time'])*1000,
				"cancel_time"	=> $this->datetime2timestamp($qry->row['cancel_time'])*1000,
				"transaction"	=> $inputArray['params']['id'],
				"state"			=> (int)$qry->row['state'],
				"reason"		=> (is_null($qry->row['reason'])?null:(int)$qry->row['reason'])   
			);
			
			$this->resultArray=json_encode($responseArray);
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
				"amount"       => $row["amount"],
				"state"	       => (int) row['state'],
				"reason"       => (is_null($qry->row['reason'])?null:(int) row['reason']) ,
				
				"create_time"  => (is_null($row['create_time']) ? null: $this->datetime2timestamp( $row['create_time']) ) ,
				"perform_time" => (is_null($row['perform_time'])? null: $this->datetime2timestamp( $row['perform_time'])) ,
				"cancel_time"  => (is_null($row['cancel_time']) ? null: $this->datetime2timestamp( $row['cancel_time']) ) ,
				
				"receivers"    => null
			)) ;
		}		
		
		$responseArray['result'] = array( "transactions"=> $transactions );		
		
		$this->resultArray=json_encode($responseArray);		
	}
	
	public function ChangePassword($inputArray) {
		
		if (! array_key_exists("params", $inputArray)) {
			
			$this->GenerateErrorResponse($inputArray['id'], '-32600', __METHOD__." params", false ); 
			
		} else if (! array_key_exists("password", $inputArray["params"])) {
			
			$this->GenerateErrorResponse($inputArray['id'], '-32600', __METHOD__." password", false );  
			
		} else {
			 
			if ($this->config->get('payme_test_enabled')=='Y') { 
				
				$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='".$inputArray['params']['password']."' WHERE `key` = 'payme_merchant_private_key_test' AND `group` = 'payme'");
			}  
			else if ($this->config->get('payme_test_enabled')=='N'){
				
				$this->db->query("UPDATE " . DB_PREFIX . "setting SET `value`='".$inputArray['params']['password']."' WHERE `key` = 'payme_merchant_private_key' AND `group` = 'payme'");
			}
			
			// All things is OK
			$responseArray = array(); 
			$responseArray['result'] = array ( 'success' =>true );	
							
			$this->resultArray=json_encode($responseArray);	
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
						"transaction"	=> $transaction_id,
						"state"			=> (int)$qry->row['state']
				);
			
			} else if ($responseType==2) {
				
				$responseArray['result'] = array(
				
						"perform_time"	=> $this->datetime2timestamp($qry->row['perform_time'])*1000,
						"transaction"	=> $transaction_id,
						"state"			=> (int)$qry->row['state']
				);
			} else if ($responseType==3) {
				
				$responseArray['result'] = array(
				
						"cancel_time"	=> $this->datetime2timestamp($qry->row['cancel_time'])*1000,
						"transaction"	=> $transaction_id,
						"state"			=> (int)$qry->row['state']
				);	
			}
			
			$this->resultArray=json_encode($responseArray);
			
		} else {
			
			$this->GenerateErrorResponse($request_id, '-31008', __METHOD__, false );  
		}
	}
	
	public function GenerateErrorResponse($request_id, $codeOfError, $data, $typeOfResponse ) {
		
		$responseArray['id']    = $request_id;
		$responseArray['error'] = array (
		
						'code'   =>(int) $codeOfError,
						'message'=> array(
						                 "ru"=>$this->getGenerateErrorText($codeOfError,"ru"),
						                 "uz"=>$this->getGenerateErrorText($codeOfError,"uz"),
										 "en"=>$this->getGenerateErrorText($codeOfError,"en"),
						                 "data" =>$data
						));	
		
		$this->result=$typeOfResponse;
		$this->resultArray=json_encode($responseArray);
	}
		
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
	
	public function getMethod($address, $total) {
        
		$this->load->language('payment/payme');
		
		$status = true;
		/*
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('sanjar_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('sanjar_total') > 0 && $this->config->get('sanjar_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('sanjar_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}
        */
		
		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'payme',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('sanjar_sort_order')
			);
		}

		return $method_data;
	}
}