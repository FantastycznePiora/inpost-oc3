<?php
class ModelExtensionShippingInPostOC3 extends Model {
    public function getQuote($address){
        $this->load->language('extension/shipping/inpostoc3');

        $quote_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "geo_zone ORDER BY name");

        foreach ($query->rows as $result) {
            //if enabled for this geo zone
            $status = false;
            if ($this->config->get('shipping_inpostoc3_' . $result['geo_zone_id'] . '_status')) {
                $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$result['geo_zone_id'] . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
                if ($query->num_rows) {
					$status = true;
				}
            }
            if ($status) {
                $cost = '';

                /* TODO:
                    - grab weight: $weight = $this->cart->getWeight(); or replace it in one loop below
                    - grab product dimensions and calculate maxium of cumulative legnth, width and height to be able to match to shipment class:  $products = $this->cart->getProducts();
                    - !lenght conversion sits in system\library\cart\length.php but is not an element of cart class, just sits in this namespace
                    try creating it or copy conversion logic from this lib

                    maybe $lengthobj = $this->length->convert() will work out of the box much like in usps extension?

                    $order_weight =0;
                    $order_length =0;
                    $order_width =0;
                    $order_heigth =0;
                    foreach ($products as $product) {
                        if ($product['shipping']) {
                            $order_weight += $this->weight->convert($product['weight'], $product['weight_class_id'], $this->cart->config->get('config_weight_class_id'));
                            $order_length = max($this->lenght->convert($product['length'], $product['length_class_id'], $this->cart->config->get('config_lenght_class_id')),$order_length);
                            $order_width = max($this->lenght->convert($product['width'], $product['length_class_id'], $this->cart->config->get('config_lenght_class_id'),$order_width);
                            $order_heigth += $this->lenght->convert($product['length'], $product['length_class_id'], $this->cart->config->get('config_lenght_class_id'));
                        }
                    }
                    - summarize the data somehow to have easy comparison
                */

                $rates = explode(',', $this->config->get('shipping_inpostoc3_' . $result['geo_zone_id'] . '_parcel_locker_rate'));

                foreach ($rates as $rate) {
                    $data = explode(':', $rate); //assuming it'll be A:wgthxlengthxwidhtxheight
                    // TODO: - based on calculated w, lxwxh - assign one of the rates, for now stub with constant
                    if (isset($data[1])) {
                        $cost = $data[1];
                    }
                    break;
                }
                
                if ((string)$cost != '') {
					$quote_data['inpostoc3_' . $result['geo_zone_id']] = array(
						'code'         => 'inpostoc3.inpostoc3_' . $result['geo_zone_id'] . 'NONE',
						'title'        => $result['name'] . '  (' . $this->language->get('text_description') /*. ' ' . $this->weight->format($weight, $this->config->get('config_weight_class_id'))*/ . ')',
						'cost'         => $cost,
						'tax_class_id' => $this->config->get('shipping_inpostoc3_tax_class_id'),
						'text'         => $this->currency->format($this->tax->calculate($cost, $this->config->get('shipping_inpostoc3_tax_class_id'), $this->config->get('config_tax')), $this->session->data['currency'])
					);
				}
                
                
            }

        }
        
        $method_data = array();

		if ($quote_data) {
			$method_data = array(
				'code'       => 'inpostoc3',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_inpostoc3_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
        
    }
}