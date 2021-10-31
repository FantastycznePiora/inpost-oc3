<?php
class ModelExtensionShippingInPostOC3 extends Model {
    public function getQuote($address){
        $this->load->language('extension/shipping/inpostoc3');

        $quote_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name");
        $inpost_services = $this->getServices();

        foreach ($query->rows as $result) {
            //if particular service enabled for geo zone to which address belongs
            foreach($inpost_services as $inpost_service) {
                $status = false;
                if ($this->config->get('shipping_inpostoc3_'. $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_status')) {
                    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$result['geo_zone_id'] . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
                    if ($query->num_rows) {
                        $status = true;
                    }
                }

                if ($status) {
                    $cost = '';
                    /* WARNING:
                        -> required conversion to mm, this length class must be configured
                        -> required conversin to kilograms, this weight class must be configured
                    */
                    // do all magic to select best parcel template from the avilable ones
                    // size order first
                    $products = $this->cart->getProducts();
                    $order['weight'] =0;
                    $order['length'] =0;
                    $order['width'] = 0;
                    $order['height'] =0;
                    $cart_l_clid = $this->cart->config->get('config_length_class_id');
                    $cart_w_clid = $this->cart->config->get('config_weight_class_id');
                    foreach ($products as $product) {
                        if ($product['shipping']) {
                            
                            $order['weight'] += $this->weight->convert($product['weight'] * $product['quantity'], $product['weight_class_id'], $cart_w_clid);
                            $order['length'] = max( $this->length->convert( $product['length'], $product['length_class_id'], $cart_l_clid), $order['length'] );
                            $order['width'] = max( $this->length->convert($product['width'], $product['length_class_id'], $cart_l_clid), $order['width']);
                            $order['height'] += $this->length->convert($product['height'] * $product['quantity'], $product['length_class_id'], $cart_l_clid);
                        }
                    }
                    
                    $filter['service_id'] = $inpost_service['id'];
                    $parcel_templates = $this->getParcelTemplates($filter);
                    $pt_sizes = array ();
                    $selected_template;
                    // match sizes
                    foreach($parcel_templates as $parcel_template){
                        //pt_l_clid should be fixed for mm, pt_w_clid for kg, or all template sizes should be adjustable along with classes
                        $pt_l_clid=$this->config->get('shipping_inpostoc3_' . $result['geo_zone_id'] . '_' . $parcel_template['id'] . '_length_class_id');
                        $pt_w_clid=$this->config->get('shipping_inpostoc3_' . $result['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id');
                        if (isset($pt_l_clid) && isset($pt_w_clid)) {
                            $t_order['height'] = $this->length->convert($order['height'], $cart_l_clid, $pt_l_clid);
                            $t_order['width'] = $this->length->convert($order['width'], $cart_l_clid, $pt_l_clid);
                            $t_order['length'] = $this->length->convert( $order['length'] , $cart_l_clid, $pt_l_clid);
                            $t_order['weight'] =  $this->weight->convert($order['weight'], $cart_w_clid, $pt_w_clid);
                            if(
                                // hack: assuming mm and for kg length/height classes, TODO: allow customization of parcel templates sizes via admin
                                ($parcel_template['max_height'] - 20) > $t_order['height']
                                && ($parcel_template['max_width'] - 20) > $t_order['width'] && ($parcel_template['max_length'] - 20)  > $t_order['length']
                                && $t_order['weight'] < $parcel_template['max_weight']
                            ) { 
                                $pt_sizes[$parcel_template['id']]=$parcel_template['max_height'] - $t_order['height']; 
                            }  
                        }
                    }
                    if(!empty($pt_sizes)) {
                        // finds closest parcel_template['id'] via min on height differences
                        $selected_template = $this->getParcelTemplate(array_search(min($pt_sizes),$pt_sizes,true));
                    } else {
                        // wooah, too big or non-standard to be sent via this service - break the loop
                        $this->log->write(print_r($this->language->get('error_quote_template'), true));
                        $this->log->write(print_r($products, true));
                        break;
                        //$selected_template = $this->getParcelTemplate("3");
                    }
                    
                    $this->log->write(print_r('selected_template:', true));
                    $this->log->write(print_r($selected_template, true));

                    $rates = explode(',', $this->config->get('shipping_inpostoc3_' . $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_locker_standard_rate'));
    
                    foreach ($rates as $rate) {
                        $data = explode(':', $rate); //assuming it'll be template_identifier:cost

                        if (isset($data[1]) && isset($data[0]) && ($data[0] == $selected_template['template_identifier']) ) {
                            $cost = $data[1];
                            break;
                        }
                    }
                    
                    if ((string)$cost != '') {
                        $quote_data[ $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_' . $selected_template['template_identifier'] ] = array(
                            'code'         => 'inpostoc3.' . $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_' . $selected_template['template_identifier'] . '.NONE',
                            'title'        => $result['name'] . '  (' . $this->language->get('text_'. $inpost_service['service_identifier'] .'_description') .')',
                            'cost'         => $cost,
                            'tax_class_id' => $this->config->get('shipping_inpostoc3_tax_class_id'),
                            'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_inpostoc3_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
                        );
                    }
                    
                    
                }

            }

        }
        
        $method_data = array();

		if ($quote_data) {
			$method_data = array(
				'code'       => 'inpostoc3',
				'title'      => $this->language->get('text_'. $inpost_service['service_identifier'] .'_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_inpostoc3_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
        
    }

    public function getServices() {
        
        $query = $this->db->query("
            SELECT * FROM `inpostoc3_services`;
        ");
        $results = array();
        foreach($query->rows as $row){
            $results[]=$row;
        }
        return $results; 
    }

    public function getParcelTemplates($data) {
        
        if(isset($data['service_id'])){
            $query = $this->db->query("
            SELECT * FROM `inpostoc3_parcel_templates`
            WHERE service_id = ". $data['service_id'] .";
        ");
        }
        else {
            $query = $this->db->query("
            SELECT * FROM `inpostoc3_parcel_templates`;
        ");
        }
        $results = array();
        foreach($query->rows as $row){
            $results[]=$row;
        }
        return $results; 
    }

    public function getParcelTemplate($id) {
        $results = array();
        
        if(isset($id)) {
            $query = $this->db->query("
            SELECT * FROM `inpostoc3_parcel_templates`
            WHERE id = ".$id.";
         ");
        }
        foreach($query->rows as $row){
            $results=$row;
        }
        return $results; 
    }
}