<?php 

class ModelPaymentPayme extends Model { 

	public function CreateTable() {

		$this->db->query("

			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payme_s_state` (
			`code` INT(11) NOT NULL , 
			`name` varchar(150)  COLLATE utf8_unicode_ci NOT NULL, 
			PRIMARY KEY (`code`)
			) ENGINE=InnoDB DEFAULT COLLATE=utf8_unicode_ci;
		");

		$this->db->query("

			INSERT INTO `" . DB_PREFIX . "payme_s_state`  (`code`, `name`) VALUES
			('-2', 'Транзакция отменена после завершения (начальное состояние 2).'),
			('-1', 'Транзакция отменена (начальное состояние 1).'),
			('0',  'ожидание подтверждения'),
			('1',  'Транзакция успешно создана, ожидание подтверждения (начальное состояние 0).'),
			('2',  'Транзакция успешно завершена (начальное состояние 1).'),
			('3',  'Заказ выполнен. Невозможно отменить транзакцию. Товар или услуга предоставлена покупателю в полном объеме.' );
		");

		$this->db->query("

			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "payme_transactions` (
			`transaction_id` bigint(11) NOT NULL AUTO_INCREMENT COMMENT 'идентификатор транзакции ',
			`paycom_transaction_id` char(25) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Номер или идентификатор транзакции в биллинге мерчанта. Формат строки определяется мерчантом.',
			`paycom_time` varchar(13) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`paycom_time_datetime` datetime DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`create_time` datetime NOT NULL COMMENT 'Время добавления транзакции в биллинге мерчанта.',
			`perform_time` datetime DEFAULT NULL COMMENT 'Время проведения транзакции в биллинге мерчанта',
			`cancel_time` datetime DEFAULT NULL COMMENT 'Время отмены транзакции в биллинге мерчанта.',
			`amount` int(11) NOT NULL COMMENT 'Сумма платежа в тийинах.',
			`state` int(11) NOT NULL DEFAULT '0' COMMENT 'Состояние транзакции',
			`reason` tinyint(2) DEFAULT NULL COMMENT 'причина отмены транзакции.',
			`receivers` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'JSON array of receivers',
			`order_id` bigint(20) NOT NULL COMMENT 'заказ',
			`cms_order_id` char(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'номер заказа CMS',
			`is_flag_test` enum('Y','N') COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (`transaction_id`),
			UNIQUE KEY `paycom_transaction_id` (`paycom_transaction_id`),
			UNIQUE KEY `order_id` (`order_id`,`paycom_transaction_id`),
			KEY `state` (`state`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;
		");

		$this->db->query("
		
			ALTER TABLE `" . DB_PREFIX . "payme_transactions` ADD CONSTRAINT `payme_transactions_ibfk_1` FOREIGN KEY (`state`) REFERENCES `payme_s_state` (`code`) ON DELETE NO ACTION ON UPDATE NO ACTION;
		");
	}

	public function DropTable() {

		$this->db->query("

			DROP TABLE IF EXISTS `" . DB_PREFIX . "payme_s_state`, `" . DB_PREFIX . "payme_transactions`;
		"); 
	}
}

?>