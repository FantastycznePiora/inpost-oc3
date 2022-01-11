<?php
class ControllerExtensionShippingInPostOC3 extends Controller {
    public function saveSelectedPoint(){
        $this->load->language('extension/shipping/inpostoc3');

        $json = array();
        
        //$this->log->write(print_r('catalog/controller/extension/shipping/inpostoc3 here!', true));
        //$this->log->write(print_r($this->request->post['shipping_method'], true));

        if (!isset($this->request->post['shipping_method'])) {
			$json['error']['warning'] = $this->language->get('error_no_point_selected');
		} else {
            $shipping = explode('.', trim($this->request->post['shipping_method']));
            if ( !isset($shipping[0]) || !isset($shipping[1]) || !isset( $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]] ) ) {
				//$this->log->write(print_r(isset($shipping[0]), true));
				//$this->log->write(print_r(isset($shipping[1]), true));
				//$this->log->write(print_r(isset($this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]])),true);

				$json['error']['warning'] = $this->language->get('error_shipping');
            }
        }
        
        // update point selection in session data
        if (!$json) {
            $this->session->data['shipping_methods'][$shipping[0]]['quote'][$shipping[1]]['code'] = $this->request->post['shipping_method'];
            $this->session->data['shipping_method'] = $this->request->post['shipping_method'];
        }

        $this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
    }

    // catalog/view/checkout/checkout/after
    public function eventCatalogCheckoutShippingMethodAfter(&$route,&$data,&$output)
    {
        //$this->log->write('catalog/controller/extension/shipping/inpostoc3/eventCatalogCheckoutShippingMethodAfter event handler');
        // inject geowidget script and handler once page rendered and before sending to client
        $pos = strpos($output,'</head>');
        $inject = '<script src="catalog/view/javascript/inpostoc3.js" type="text/javascript"></script>';
        $output=substr($output, 0, $pos) . $inject . substr($output, $pos);
    }

}
