<?php

class ModelExtensionPaymentintenduz extends Model
{
    public function install()
    {
        $this->db->query(
            "
			CREATE TABLE IF NOT EXISTS `".DB_PREFIX."intenduz_ipn` (
                `id` bigint unsigned NOT NULL AUTO_INCREMENT,
                `ref_id` varchar(255),
                `order_id` bigint,
                `status` int ,
			  PRIMARY KEY (`id`)
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
