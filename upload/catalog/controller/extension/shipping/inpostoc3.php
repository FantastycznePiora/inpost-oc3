<?php
class ControllerExtensionShippingInPostOC3 extends Controller {
    public function index() {
        //$this->log->write(print_r('controller\extension\shipping\inpostoc3 here', true));
       // $this->load->model('extension/shipping/inpost');
        //$this->load->language('extension/shipping/inpostoc3');
        //$this->document->addScript('catalog/view/javascript/inpostoc3.js');
        //addScript
        //set $data with stuff needed for twig to display/hide parcel selector
        $data = array ();
        $data['aaa'] = 'testowy';
        //$this->response->setOutput($this->load->view('extension/shipping/inpostoc3', $data));
        return $this->load->view('extension/shipping/inpostoc3', $data);
    }
}
