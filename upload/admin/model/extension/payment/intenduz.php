<?php

class ModelExtensionPaymentintenduz extends Model
{
    public function install()
    {
        $this->db->query(
            "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."intenduz_ipn` (
                `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'The primary identifier for IPN log',
                `ref_id` int(10) varchar(255)  COMMENT 'The ref_id from intend',
                `order_id` int(10) bigint  COMMENT 'The order_id from opencart',
                `status` int(10) int  COMMENT 'The status from intend',
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
			SET ref_id = ".$data['ref_id'].",
				order_id = ".(int)$data['order_id'].",
				status = ".(int)$data['status'].",
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
