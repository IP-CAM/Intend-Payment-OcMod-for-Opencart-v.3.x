<?php
class ControllerExtensionPaymentIntenduz extends Controller {
    private $error = array();

    public function index() {
        $this->load->language('extension/payment/intenduz');

        $this->load->model('extension/payment/intenduz');

        $this->load->model('localisation/geo_zone');

        $this->load->model('localisation/order_status');

        $this->load->model('setting/setting');

        $this->document->setTitle($this->language->get('heading_title'));

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

            $this->model_setting_setting->editSetting('payment_intenduz', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/payment/intenduz', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['entry_payment_intenduz_api_key'] = $this->language->get('entry_payment_intenduz_api_key');

        $data['error_api_key'] = isset($this->error['error_api_key']) ? $this->error['error_api_key'] : '';

        $data['error_secret_key'] = isset($this->error['secret_key']) ? $this->error['secret_key'] : '';

        $data['action'] = $this->url->link('extension/payment/intenduz', 'user_token=' . $this->session->data['user_token'], true);

        $data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

        $params = array(
            'payment_intenduz_api_key' => '',
            'payment_intenduz_secret_key' => '',
            'payment_intenduz_status' => 1,
            'payment_intenduz_sort_order' => 1,
            'payment_intenduz_method' => 'only_card',
            'payment_intenduz_order_status_id' => 2,
        );

        foreach( $params as $key => $default ) {

            if( isset($this->request->post[$key]) ) {
                $data[$key] = $this->request->post[$key];
            } elseif($this->config->get($key)) {
                $data[$key] = $this->config->get($key);
            } else {
                $data[$key] = $default;
            }

        }

        $data['payment_intenduz_callback_url'] = str_replace('/admin/', '/', $this->url->link('extension/payment/intenduz/complete'));

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/payment/intenduz', $data));
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', 'extension/payment/intenduz')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if (!$this->request->post['payment_intenduz_api_key']) {
            $this->error['error_api_key'] = $this->language->get('error_api_key');
        }


        if (!$this->request->post['payment_intenduz_secret_key']) {
            $this->error['secret_key'] = $this->language->get('error_secret_key');
        }

        return !$this->error;
    }

    public function install() {
        $this->load->model('extension/payment/intenduz');

        $this->model_extension_payment_intenduz->install();
    }

    public function uninstall() {
        $this->load->model('extension/payment/intenduz');

        $this->model_extension_payment_intenduz->uninstall();
    }
}