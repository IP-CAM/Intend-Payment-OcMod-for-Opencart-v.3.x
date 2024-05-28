<?php

class ControllerExtensionPaymentIntenduz extends Controller
{

    public function index()
    {

        $this->load->language('extension/payment/intenduz');
        $this->load->model('checkout/order');

        $order_id = $this->session->data['order_id'];

        $data['button_confirm'] = $this->language->get('button_confirm');
        $data['entry_duration'] = $this->language->get('entry_duration');
        $data['action'] = 'https://my.intend.uz/pay/';

        $data['api_key'] = $this->config->get('payment_intenduz_api_key');
        $data['secret_key'] = $this->config->get('payment_intenduz_secret_key');
        $data['redirect_url'] = HTTP_SERVER;

        $this->load->model('account/order');
        $orders = $this->model_account_order->getOrderProducts($order_id);
        $products = [];
        foreach ($orders as $order) {
            $result['id'] = $order['product_id'];
            $result['price'] = $order['price'];
            $result['quantity'] = $order['quantity'];
            $result['name'] = $order['name'];
            $products[] = $result;
        }

        $data['orders'] = $products;
        $data['order_id'] = $order_id;

        return $this->load->view('extension/payment/intenduz', $data);
    }

    public function pay()
    {
        if ($this->session->data['payment_method']['code'] == 'intenduz') {
            $this->load->model('checkout/order');

            $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

            $query_vars = [
                'service_id' => $this->config->get('payment_intenduz_service_id'),
                'merchant_id' => $this->config->get('payment_intenduz_merchant_user_id'),
                'amount' => $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false),
                'transaction_param' => $this->session->data['order_id'],
                'source' => 'opencart',
                'return_url' => $this->url->link('extension/payment/intenduz/callback'),
            ];

            $pay_url = 'https://pay.intend.uz/services/pay?' . http_build_query($query_vars, '', '&');

            header('Location: ' . $pay_url);

            exit;
        }

    }

    public function payment_received()
    {

        $this->load->model('checkout/order');

        $json = [];

        if ($this->session->data['payment_method']['code'] == 'intenduz') {
            $this->load->model('checkout/order');

            $json['redirect'] = $this->url->link('checkout/success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function callback()
    {
        $redirect = $this->url->link('checkout/success');

        if (isset($this->request->get['payment_status'])) {
            switch ($this->request->get['payment_status']) {
                case 0:
                    $redirect = $this->url->link('checkout/success');
                    break;
                case -10000:
                    $redirect = $this->url->link('checkout/failure');
            }
        }
        $this->response->redirect($redirect);
    }

    public function prepare()
    {

        error_reporting(0);
        $result = $this->validate_prepare();

        $this->log->write($result);
        $this->model_extension_payment_intenduz->updateLog($result['log_id'], ['result_string' => json_encode($result['data'])]);

        exit(json_encode($result['data']));
    }

    public function complete()
    {

        error_reporting(0);
        $result = $this->validate_complete();

        $this->model_extension_payment_intenduz->updateLog($result['log_id'], ['result_string' => json_encode($result['data'])]);

        echo json_encode($result['data']);

        exit;
    }

    public function validate_prepare()
    {

        $this->load->model('extension/payment/intenduz');
        $this->load->model('checkout/order');

        $result = [];

        $this->log->write('validate prepare');

        $prepare_id = $this->model_extension_payment_intenduz->addLog($_POST);

        $this->log->write('prepare_id=' . $prepare_id);

        $result['log_id'] = $prepare_id;

        if (!isset(
            $_POST['intend_trans_id'],
            $_POST['service_id'],
            $_POST['merchant_trans_id'],
            $_POST['amount'],
            $_POST['action'],
            $_POST['sign_time']
        )) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-8',
                    'error_note' => 'Error in request from intend',
                ],
            ];
        }


        $this->log->write('params check passed');

        $signString = $_POST['intend_trans_id'] .
            $_POST['service_id'] .
            $this->config->get('payment_intenduz_secret_key') .
            $_POST['merchant_trans_id'] .
            $_POST['amount'] .
            $_POST['action'] .
            $_POST['sign_time'];

        $signString = md5($signString);

        if ($signString !== $_POST['sign_string']) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-1',
                    'error_note' => 'Sign check error',
                ],
            ];

        }

        $this->log->write('sign check passed');

        $order_info = $this->model_checkout_order->getOrder($_POST['merchant_trans_id']);

        if (!$order_info) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-5',
                    'error_note' => 'User does not exist',
                ],
            ];
        }

        $this->log->write('order check passed');

        $status = $order_info['order_status_id'];

        $total = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        if ($this->config->get('payment_intenduz_order_status_id') == $status) {

            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-4',
                    'error_note' => 'Already paid',
                ],
            ];
        }

        $this->log->write('order paid check passed');

        if (abs($total - (float)$_POST['amount']) > 0.01) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-2',
                    'error_note' => 'Incorrect parameter amount',
                ],
            ];
        }

        $this->log->write('amount check passed');

        try {
            //$this->model_checkout_order->addOrderHistory($order_info['order_id'], $status, 'Prepare request from intend processed. intend Transaction ID: ' . $_POST['intend_trans_id']);

            return [
                'log_id' => $prepare_id,
                'data' => [
                    'intend_trans_id' => $_POST['intend_trans_id'],
                    'merchant_trans_id' => $_POST['merchant_trans_id'],
                    'merchant_prepare_id' => $prepare_id,
                    'error' => '0',
                    'error_note' => 'Success',
                ],
            ];
        } catch (Exception $ex) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-7',
                    'error_note' => 'Failed to update user',
                ],
            ];
        }

    }

    public function validate_complete()
    {

        $this->load->model('extension/payment/intenduz');
        $this->load->model('checkout/order');
        $result = [];
        $prepare_id = $this->model_extension_payment_intenduz->addLog($_POST);

        $result['log_id'] = $prepare_id;

        if (!isset(
            $_POST['intend_trans_id'],
            $_POST['service_id'],
            $_POST['merchant_trans_id'],
            $_POST['merchant_prepare_id'],
            $_POST['amount'],
            $_POST['action'],
            $_POST['sign_time']
        )) {

            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-8',
                    'error_note' => 'Error in request from intend',
                ],
            ];
        }

        $signString = $_POST['intend_trans_id'] .
            $_POST['service_id'] .
            $this->config->get('payment_intenduz_secret_key') .
            $_POST['merchant_trans_id'] .
            $_POST['merchant_prepare_id'] .
            $_POST['amount'] .
            $_POST['action'] .
            $_POST['sign_time'];

        $signString = md5($signString);

        if ($signString !== $_POST['sign_string']) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-1',
                    'error_note' => 'Sign check error',
                ],
            ];
        }

        $order_info = $this->model_checkout_order->getOrder($_POST['merchant_trans_id']);

        if (!$order_info) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-5',
                    'error_note' => 'User does not exist',
                ],
            ];
        }

        $status = $order_info['order_status_id'];

        $total = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);

        if (!$this->model_extension_payment_intenduz->getLog($_POST['merchant_prepare_id'])) {

            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-6',
                    'error_note' => 'Transaction does not exist',
                ],
            ];
        }

        if (abs($total - (float)$_POST['amount']) > 0.01) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-2',
                    'error_note' => 'Incorrect parameter amount',
                ],
            ];
        }

        if ($status == 10) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-9',
                    'error_note' => 'Transaction cancelled',
                ],
            ];
        }

        if (in_array($status, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-4',
                    'error_note' => 'Already paid',
                ],
            ];
        }

        if ($_POST['error'] < 0) {

            $this->model_checkout_order->addOrderHistory($order_info['order_id'], 10, $_POST['error_note']);

            return [
                'log_id' => $prepare_id,
                'data' => [
                    'intend_trans_id' => $_POST['intend_trans_id'],
                    'merchant_trans_id' => $_POST['merchant_trans_id'],
                    'merchant_confirm_id' => $_POST['merchant_prepare_id'],
                    'error' => '-9',
                    'error_note' => 'Transaction cancelled',
                ],
            ];
        }

        try {

            $this->model_checkout_order->addOrderHistory($order_info['order_id'], $this->config->get('payment_intenduz_order_status_id'), $_POST['error_note']);


            return [
                'log_id' => $prepare_id,
                'data' => [
                    'intend_trans_id' => $_POST['intend_trans_id'],
                    'merchant_trans_id' => $_POST['merchant_trans_id'],
                    'merchant_confirm_id' => $_POST['merchant_prepare_id'],
                    'error' => '0',
                    'error_note' => 'Success',
                ],
            ];
        } catch (Exception $ex) {
            return [
                'log_id' => $prepare_id,
                'data' => [
                    'error' => '-7',
                    'error_note' => 'Failed to update user',
                ],
            ];
        }
    }
}