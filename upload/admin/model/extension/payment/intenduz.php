<?php

class ModelExtensionPaymentintenduz extends Model
{
    public function install()
    {
        $this->db->query(
            "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."intenduz_ipn` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for IPN log',
                `intend_trans_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The transaction ID from intend',
                `service_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The service ID from intend',
                `intend_paydoc_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'The payment ID from intend',
                `merchant_trans_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'The transaction ID sent from our site (on payment button form)',
                `merchant_prepare_id` varchar(255) DEFAULT NULL COMMENT 'Received from confirm request only; initially sent in response to prepare request',
                `amount` decimal(12,2) unsigned NOT NULL COMMENT 'Payment amount',
                `action` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'Action code: 0 - prepare, 1 - complete',
                `error` int(10) NOT NULL DEFAULT '0' COMMENT 'Error code: 0 - success',
                `error_note` varchar(255) DEFAULT '' COMMENT 'Error message',
                `sign_time` varchar(255) NOT NULL DEFAULT '' COMMENT 'Payment datetime format: YYYY-MM-DD HH:mm:ss',
                `sign_string` varchar(255) NOT NULL DEFAULT '' COMMENT 'Hash string for checking the validity of the request',
                `result_string` text NULL COMMENT 'Result of callback',
			  PRIMARY KEY (`id`),
        KEY `intend_paydoc_id` (`intend_paydoc_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Logs intend Instant Payment Notifications.'
		"
        );
    }

    public function uninstall()
    {
        $this->db->query("DROP TABLE IF EXISTS `".DB_PREFIX."intenduz_ipn`");
    }

    public function addLog($data)
    {
        $this->db->query(
            "
			INSERT INTO ".DB_PREFIX."intenduz_ipn
			SET intend_trans_id = ".(int)$data['intend_trans_id'].",
				service_id = ".(int)$data['service_id'].",
				intend_paydoc_id = ".(int)$data['intend_paydoc_id'].",
				merchant_trans_id = '".$this->db->escape($data['merchant_trans_id'])."',
				merchant_prepare_id = '".$this->db->escape($data['merchant_prepare_id'])."',
				amount = ".(float)$data['amount'].",
				`action` = ".(int)$data['action'].",
				error = ".(int)$data['error'].",
				error_note = '".$this->db->escape($data['error_note'])."',
				sign_time = '".$this->db->escape($data['sign_time'])."',
				sign_string = '".$this->db->escape($data['sign_string'])."'
		"
        );

        return $this->db->getLastId();
    }

    public function getLog($id)
    {
        $result = $this->db->query("SELECT * FROM ".DB_PREFIX."intenduz_ipn WHERE id = '".$id."'")->row;

        if ($result) {
            $transaction = $result;
        } else {
            $transaction = false;
        }

        return $transaction;
    }

    public function updateLog($id, $data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            $values[] = " {$key} = '{$value}'";
        }

        if (count($values)) {
            $this->db->query("UPDATE ".DB_PREFIX."intenduz_ipn ".implode(',', $values)." Set WHERE id = '".$id."'");
        }
    }

}
