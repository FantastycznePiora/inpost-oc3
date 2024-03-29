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
                if ($this->config->get('shipping_inpostoc3_'. $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_status') 
                    && $this->config->get('shipping_inpostoc3_'. $result['geo_zone_id'] . '_' . $inpost_service['id'] . '_show_in_checkout') ) {
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
                    $order['volume'] = 0;
                    $cart_l_clid = $this->cart->config->get('config_length_class_id');
                    $cart_w_clid = $this->cart->config->get('config_weight_class_id');
                    foreach ($products as $product) {
                        if ($product['shipping']) {
                            
                            $order['weight'] += $this->weight->convert($product['weight'] * $product['quantity'], $product['weight_class_id'], $cart_w_clid);
                            $order['volume'] += $this->length->convert( $product['length'], $product['length_class_id'], $cart_l_clid) * 
                                                $this->length->convert($product['width'], $product['length_class_id'], $cart_l_clid) *
                                                $this->length->convert($product['height'], $product['length_class_id'], $cart_l_clid)
                                                * $product['quantity']; //cubic unit
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
                            $t_order['weight'] =  $this->weight->convert($order['weight'], $cart_w_clid, $pt_w_clid);
                            $t_order['volume'] =  $order['volume'] ; //remember it's cubic unit!
                            //convert from default mm to whatever the cart measurement unit class is - assume there's a cofiguration in place!
                            $parcel_template_volume = $this->length->convert($parcel_template['max_height']-5, $pt_l_clid , $cart_l_clid ) * 
                                                    $this->length->convert($parcel_template['max_width'] - 5, $pt_l_clid , $cart_l_clid) * 
                                                    $this->length->convert($parcel_template['max_length'] - 5, $pt_l_clid , $cart_l_clid);
                            if ($parcel_template_volume >= $t_order['volume'] ) {
                                $pt_volumes[$parcel_template['id']] = $parcel_template_volume - $t_order['volume'];
                            }
                            //$this->log->write(print_r('order volume: '.print_r($order['volume'],true), true));
                            //$this->log->write(print_r('t_order volume: '.print_r($t_order['volume'],true), true));
                            //$this->log->write(print_r('$parcel_template_volume: '. $parcel_template_volume, true));
                        }
                    }
                    if(!empty($pt_volumes)) {
                        //finds closest parcel_template['id'] va min on volume difference
                        $selected_template = $this->getParcelTemplate(array_search(min($pt_volumes), $pt_volumes,true));
                    } else {
                        // wooah, too big or non-standard to be sent via this service - break the loop
                        $this->log->write(print_r($this->language->get('error_quote_template'), true));
                        $this->log->write(print_r($products, true));
                        break;
                    }
                    
                    //$this->log->write(print_r('selected_template:', true));
                    //$this->log->write(print_r($selected_template, true));

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

    public function getRoutes($filter) {
        $result = null;
        $sql = "
        SELECT * FROM `inpostoc3_services_routing`
        ";
        $allowed_keys = array ("id", "service_id", "sender_country_iso_code_3", "receiver_country_iso_code_3","sender_country_iso_code_2", "receiver_country_iso_code_2");

        $sql = $sql . $this->sqlBuildSimpleWhere($filter, $allowed_keys) . ";";
        //$this->log->write(__METHOD__ . ' $sql: ' .$sql);

        $query = $this->db->query ($sql);

        //$this->log->write(__METHOD__ . ' $query: ' . print_r($query,true));
        $result = array();
        foreach($query->rows as $row){           
            $result[]=$row;
        }
        return $result;
    }

    protected function sqlBuildSimpleWhere($filter, $keys = array()  ) {
        $sql ='';
        $where = array();
        if ( !empty($filter) && is_array($filter) ) {
            foreach ( $filter as $key => $value ) {
                if ( empty($keys) ) {
                    $where[] = $key . " = '" . $value ."'";
                } else if ( in_array($key, $keys) ) {
                    $where[] = $key . " = '" . $value ."'";
                }
            }
        }
        if ( !empty($where) ) {
            if ( empty($filter["where_operator"]) ){
                $filter["where_operator"] = "AND";
            }
            $sql = " WHERE (" . implode(" ".$filter["where_operator"]." ", $where) . ")";
        }
        return $sql;
    }

}