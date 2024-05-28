<?php

class ModelExtensionPaymentIntenduz extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('extension/payment/intenduz');

        $method_data = [
            'code'       => 'intenduz',
            'title'      => $this->language->get('text_title'),
            'terms'      => '',
            'sort_order' => $this->config->get('payment_intenduz_sort_order'),
        ];

        return $method_data;
    }

    public function addLog($data)
    {
        $keys = [
            'intend_trans_id'     => 0,
            'service_id'          => 0,
            'intend_paydoc_id'    => 0,
            'merchant_trans_id'   => '',
            'merchant_prepare_id' => '',
            'amount'              => 0,
            'action'              => 0,
            'error'               => 0,
            'error_note'          => '',
            'sign_time'           => '',
            'sign_string'         => '',
        ];

        foreach ($keys as $key => $default) {
            if (!array_key_exists($key, $data)) {
                $data[$key] = $default;
            }
        }

        $this->db->query(
            "
			INSERT INTO ".DB_PREFIX."intenduz_ipn
			SET `intend_trans_id` = ".(int)$data['intend_trans_id'].",
				`service_id` = ".(int)$data['service_id'].",
				`intend_paydoc_id` = ".(int)$data['intend_paydoc_id'].",
				`merchant_trans_id` = '".$this->db->escape($data['merchant_trans_id'])."',
				`merchant_prepare_id` = '".$this->db->escape($data['merchant_prepare_id'])."',
				`amount` = ".(float)$data['amount'].",
				`action` = ".(int)$data['action'].",
				`error` = ".(int)$data['error'].",
				`error_note` = '".$this->db->escape($data['error_note'])."',
				`sign_time` = '".$this->db->escape($data['sign_time'])."',
				`sign_string` = '".$this->db->escape($data['sign_string'])."'
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
            $value    = $this->db->escape($value);
            $values[] = " {$key} = '{$value}'";
        }

        if (count($values)) {
            $this->db->query("UPDATE ".DB_PREFIX."intenduz_ipn SET ".implode(',', $values)." WHERE id = {$id} ");
        }
    }
}