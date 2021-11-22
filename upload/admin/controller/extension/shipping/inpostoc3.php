<?php
class ControllerExtensionShippingInPostOC3 extends Controller {
    private $error = array();
  
    public function index() {
        $this->load->language('extension/shipping/inpostoc3');
        $this->document->setTitle($this->language->get('heading_title'));
		
        //save extension settings into DB
        $this->load->model('setting/setting');

        //specific inpostoc3 model
        $this->load->model('extension/shipping/inpostoc3');
        $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocAttributes();       
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('shipping_inpostoc3', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}

        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
        // assign language labels into the $data array so that we can access those in the view template file
        // and set up the proper breadcrumb links
		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/shipping/inpostoc3', 'user_token=' . $this->session->data['user_token'], true)
		);
        // set up the action variable to make sure that the form is submitted to our index method. 
        // take users back to the list of shipping methods if they click on the Cancel button.
        $data['action'] = $this->url->link('extension/shipping/inpostoc3', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true);



        // populate the default values of the configuration form fields either in add or edit mode.
        // configure InPost services in context of GeoZones in order to provide different rates and services enabled - framework for future extensions
        $this->load->model('localisation/geo_zone');
		$geo_zones = $this->model_localisation_geo_zone->getGeoZones();
        // to get country iso codes later in the loop
        $this->load->model('localisation/country');
        
        $filter = array();
        $filter['order']='DESC';
        $filter['sort']='value';
        $this->load->model('localisation/weight_class');
        $data['weight_classes'] = $this->model_localisation_weight_class->getWeightClasses($filter);

        $this->load->model('localisation/length_class');
        $data['length_classes'] = $this->model_localisation_length_class->getLengthClasses($filter);


        foreach ($geo_zones as $geo_zone) {
            /*if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_status'])) {
				$data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_status'];
			} else {
				$data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_status');
			}*/

            // Is API integration allowed in this geo zone?
            $zones_to_gz = $this->model_localisation_geo_zone->getZoneToGeoZones($geo_zone['geo_zone_id']);
            
            $data['shipping_inpostoc3_geo_zone_hide_api'][$geo_zone['geo_zone_id']] = false;
            //country check for this specific geozone in order to hide/show API options
            $inpost_allowed_api_countries = array( // TODO - instead of hardcoding, put it somewhere in DB
                array (
                    "iso_code_3" => "POL",
                    "iso_code_2" => "PL"
                )
            );
            // in order to have API integration enabled, all countries configured in zone->geozone must match allowed api countries list
            foreach ($zones_to_gz as $zone)
            {
                foreach($inpost_allowed_api_countries as $inpost_allowed_api_country)
                {
                    $country = $this->model_localisation_country->getCountry( (int)$zone['country_id'] );
                    //$data['']
                    if ( isset($country['iso_code_3']) && ($country['iso_code_3'] != $inpost_allowed_api_country['iso_code_3']) ) {
                        $data['shipping_inpostoc3_geo_zone_hide_api'][$geo_zone['geo_zone_id']] = true;
                        break 2; // break immediately both loop levels, no point checking further - define geozone with allowed countries only or don't use InPost API
                    }
                }
            }
            // endof Is API integration allowed in this geo zone

            foreach($inpost_services as $inpost_service){

                if (isset($this->request->post['shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status'])) {
                    $data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->request->post['shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->config->get('shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status');
                }
                //$this->log->write('Allowed routes: '. print_r($inpost_service['allowed_routes'],true) );                
                
                if ( isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_sendfrom']) ) {
                    $data['shipping_inpostoc3_geo_zone_sendfrom'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_sendfrom'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_sendfrom'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_sendfrom');
                }
                /*$this->log->write('
                    Geo zone id: ' .$geo_zone['geo_zone_id'] .',
                    Inpost service (id, identifier): .(' .$inpost_service['id'] .', '. $inpost_service['service_identifier'] . '),
                    Send from array: '. print_r($data['shipping_inpostoc3_geo_zone_sendfrom'],true)
                );*/

                // TODO: split into ABC size classess & rates or figure out system to check dimensions & weight < 25kg and do text input here
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_locker_standard_rate'])) {
                    $data['shipping_inpostoc3_geo_zone_locker_standard_rate'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_locker_standard_rate'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_locker_standard_rate'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] .'_locker_standard_rate');
                }

                foreach ($inpost_service['parcel_templates'] as $parcel_template) {
                    if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id'])) {
                        $data['shipping_inpostoc3_geo_zone_weight_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id'];
                    } else {
                        $data['shipping_inpostoc3_geo_zone_weight_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id');
                    }
                    
                    
                    if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_length_class_id'])) {
                        $data['shipping_inpostoc3_geo_zone_length_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_length_class_id'];
                    } else {
                        $data['shipping_inpostoc3_geo_zone_length_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_length_class_id');
                    }
                }
                        // TODO
                        // add max_height, max_width, max_length for mm
                        // add max_weight for kg
                        // these will determine wheter to use template small/medium/large and usually should be ca 2cm under default limits

            }
                       
            // hide api options if cannot be used with particular geozone 
            if ($data['shipping_inpostoc3_geo_zone_hide_api'][$geo_zone['geo_zone_id']] == true){
                $data['shipping_inpostoc3_geo_zone_use_api'][$geo_zone['geo_zone_id']] = 0;
                $data['shipping_inpostoc3_geo_zone_use_sandbox_api'][$geo_zone['geo_zone_id']] = 0;
            } else {
                // enable API-based integration
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_api'])) {
                    $data['shipping_inpostoc3_geo_zone_use_api'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_api'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_use_api'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_api');
                }
                // which API settings to use
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_sandbox_api'])) {
                    $data['shipping_inpostoc3_geo_zone_use_sandbox_api'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_sandbox_api'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_use_sandbox_api'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_use_sandbox_api');
                }
                // sandbox API settings
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_endpoint'])) {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_endpoint'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_endpoint'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_endpoint'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_endpoint');
                }
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_token'])) {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_token'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_token'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_token'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_token');
                }
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_org_id'])) {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_org_id'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_org_id'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_sandbox_api_org_id'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sandbox_api_org_id');
                }
                // production API settings
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_endpoint'])) {
                    $data['shipping_inpostoc3_geo_zone_api_endpoint'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_endpoint'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_api_endpoint'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_endpoint');
                }
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_token'])) {
                    $data['shipping_inpostoc3_geo_zone_api_token'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_token'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_api_token'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_token');
                }
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_org_id'])) {
                    $data['shipping_inpostoc3_geo_zone_api_org_id'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_org_id'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_api_org_id'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_api_org_id');
                }
                // set default sending method
                if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sending_method'])) {
                    $data['shipping_inpostoc3_geo_zone_sending_method'][$geo_zone['geo_zone_id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sending_method'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_sending_method'][$geo_zone['geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_sending_method');
                }
            }
        }
        // expose variables for template
        $data['geo_zones'] = $geo_zones;
        $data['inpost_services'] =  $inpost_services;
        
        // general extension status
        if (isset($this->request->post['shipping_inpostoc3_status'])) {
			$data['shipping_inpostoc3_status'] = $this->request->post['shipping_inpostoc3_status'];
		} else {
			$data['shipping_inpostoc3_status'] = $this->config->get('shipping_inpostoc3_status');
		}
        // general sort order for frontend
        if (isset($this->request->post['shipping_inpostoc3_sort_order'])) {
            $data['shipping_inpostoc3_sort_order'] = $this->request->post['shipping_inpostoc3_sort_order'];
        } else {
            $data['shipping_inpostoc3_sort_order'] = $this->config->get('shipping_inpostoc3_sort_order');
        }
        //general tax class
        if (isset($this->request->post['shipping_inpostoc3_tax_class_id'])) {
			$data['shipping_inpostoc3_tax_class_id'] = $this->request->post['shipping_inpostoc3_tax_class_id'];
		} else {
			$data['shipping_inpostoc3_tax_class_id'] = $this->config->get('shipping_inpostoc3_tax_class_id');
		}

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();
        

        // assign the children templates and the main template of the view
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/shipping/inpostoc3', $data));

    }
  
    public function validate() {
        //TODO 
        // add ParcelLocker  size/class validations
        // add SendFrom field validation
        if (!$this->user->hasPermission('modify', 'extension/shipping/inpostoc3')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;


    }
 
    public function install() {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('inpostoc3', ['inpostoc3_status'=>1]);*/

        $this->load->model('extension/shipping/inpostoc3');
        $this->model_extension_shipping_inpostoc3->install();
        
        $this->load->model('setting/event');
        //event for injecting a piece of js into route=checkout/checkout
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventCatalogCheckoutShippingMethodAfter');
        $this->model_setting_event->addEvent('inpostoc3_eventCatalogCheckoutShippingMethodAfter', 
                                                'catalog/view/checkout/checkout/after', 
                                                'extension/shipping/inpostoc3/eventCatalogCheckoutShippingMethodAfter');

        //event for modifying Admin view/sale/order_info
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderInfoBefore');
        $this->model_setting_event->addEvent('inpostoc3_eventAdminViewOrderInfoBefore', 
                                                'admin/view/sale/order_info/before', 
                                                'extension/shipping/inpostoc3/eventAdminViewOrderInfoBefore');

        //event for modifying Admin view/sale/order_shipping
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderShippingBefore');
        $this->model_setting_event->addEvent('inpostoc3_eventAdminViewOrderShippingBefore', 
                                                'admin/view/sale/order_shipping/before', 
                                                'extension/shipping/inpostoc3/eventAdminViewOrderShippingBefore');

        //event for intercepting Admin controller/sale/order/shipping/before
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminControllerShippingBefore');
        $this->model_setting_event->addEvent('inpostoc3_eventAdminControllerShippingBefore', 
                                                'admin/controller/sale/order/shipping/before', 
                                                'extension/shipping/inpostoc3/eventAdminControllerShippingBefore');
        
    }
 
    public function uninstall() {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting(‘inpostoc3’);*/

        $this->load->model('extension/shipping/inpostoc3');
        $this->model_extension_shipping_inpostoc3->uninstall();

        //cleanup events registered in install
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventCatalogCheckoutShippingMethodAfter');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderInfoBefore');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderShippingBefore');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminControllerShippingBefore');
    }
    
    // handle single order shipping
    public function orderShipping() {
        //$this->log->write(__METHOD__);

        $this->load->language('extension/shipping/inpostoc3');
        $this->document->setTitle($this->language->get('heading_title_order_shipping'));
        $this->document->addScript('view/javascript/inpostoc3.js');
             
        /*
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			//$this->model_setting_setting->editSetting('shipping_inpostoc3', $this->request->post);
            // save data ^^
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&type=shipping', true));
		}*/
        
        $data = array ();
        
        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

        // preserve url parameters if any present
        $url = $this->preserveUrlParams();

        // build breadcrumbs for easy go back to orders
        $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title_orders'),
			'href' => $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

        // set up the action_ variables to make sure that the form is submitted to proper method. 
        // take users back to the order/list of orders if they click on the Cancel button.
        $data['action_save'] = $this->url->link('extension/shipping/inpostoc3/ordershipping', 'user_token=' . $this->session->data['user_token'], true);
        /* redo for each associated shipment
        $data['action_dispatch'] = $this->url->link('extension/shipping/inpostoc3/dispatchShipment', 'user_token=' . $this->session->data['user_token'], true);
        $data['button_shipping_print'] = $this->language->get('button_shipping_print');
        */
        if(!isset($this->request->get['order_id'])) {
            $data['cancel'] = $data['breadcrumbs'][1]['href'];
        } else {
            $data['order_id'] = $this->request->get['order_id'];
            $data['cancel'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $data['order_id'], true);
        }

        $this->load->model('extension/shipping/inpostoc3');
        $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocAttributes();
        
        // expose variable for template
        $data['inpost_services'] =  $inpost_services;    
        //TODO replace/remove below flags once shipment parsing done      
        $data["inpostoc3_can_edit"]["shipment"] = true;
        $data["inpostoc3_can_edit"]["sending_method_details"] = true;


        if( isset($this->request->get['order_id']) ) {
            
            $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($data['order_id']);
            
            $data['shipping_code'] = $shipping_code;

            if( $this->isItInPostOC3Shipping($shipping_code) ) {
                // $data gets filled in with service & crucial settings details
                $shipping_code_details = explode('.',$shipping_code);
                $this->fillDataWithDetailsFromShippingCodeDetails($shipping_code_details, $data);

                // prep order data and receiver data
                $this->load->model('sale/order');
                $order_info = $this->model_sale_order->getOrder($this->request->get['order_id']);
                $this->load->model('localisation/country');

                if (!empty($order_info)) {
                    $data['store_id'] = $order_info['store_id'];
                    $data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
        
                    $data['receiver']['name'] = $order_info['customer'].' (#'.$order_info['customer_id'].')';
                    //$data['customer_id'] = $order_info['customer_id'];
                    //$data['customer_group_id'] = $order_info['customer_group_id'];
                    //$data['firstname'] = $order_info['firstname'];
                    //$data['lastname'] = $order_info['lastname'];
                    $data['receiver']['email'] = $order_info['email'];
                    $data['receiver']['phone'] = $order_info['telephone'];
                    //$data['account_custom_field'] = $order_info['custom_field'];
        
                    //$this->load->model('customer/customer');
        
                    //$data['addresses'] = $this->model_customer_customer->getAddresses($order_info['customer_id']);

                    $data['receiver']['first_name'] = $order_info['shipping_firstname'];
                    $data['receiver']['last_name'] = $order_info['shipping_lastname'];
                    $data['receiver']['company_name'] = $order_info['shipping_company'];
                    $data['receiver']['line1'] = $order_info['shipping_address_1'];
                    $data['receiver']['line2'] = $order_info['shipping_address_2'];
                    $data['receiver']['city'] = $order_info['shipping_city'];
                    $data['receiver']['post_code'] = $order_info['shipping_postcode'];

                    $country = $this->model_localisation_country->getCountry( (int)$order_info['shipping_country_id'] );
                    $data['receiver']['country_iso_code_2'] = $country['iso_code_2'];
                    $data['receiver']['country_iso_code_3'] = $country['iso_code_3'];

                    //$data['shipping_country_id'] = $order_info['shipping_country_id'];
                    //$data['shipping_zone_id'] = $order_info['shipping_zone_id'];
                    //$data['shipping_custom_field'] = $order_info['shipping_custom_field'];
                    //$data['shipping_method'] = $order_info['shipping_method'];
                    //$data['shipping_code'] = $order_info['shipping_code'];

                    // Products - assign products to a parcel for multiparcel shipments (FUTURE TODO)
                    $data['order_products'] = array();

                    $products = $this->model_sale_order->getOrderProducts($this->request->get['order_id']);

                    foreach ($products as $product) {
                        $data['order_products'][] = array(
                            'product_id' => $product['product_id'],
                            'name'       => $product['name'],
                            'model'      => $product['model'],
                            'option'     => $this->model_sale_order->getOrderOptions($this->request->get['order_id'], $product['order_product_id']),
                            'quantity'   => $product['quantity'],
                            'price'      => $product['price'],
                            'total'      => $product['total'],
                            'reward'     => $product['reward']
                        );
                    }

                    $data['order_status_id'] = $order_info['order_status_id'];
                    $data['comment'] = $order_info['comment'];
                    $data['affiliate_id'] = $order_info['affiliate_id'];
                    $data['affiliate'] = $order_info['affiliate_firstname'] . ' ' . $order_info['affiliate_lastname'];
                    $data['currency_code'] = $order_info['currency_code'];
                     
                } else {
                    $data['store_id'] = 0;
                    $data['store_url'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
                    
                    /*
                    $data['customer'] = '';
                    $data['customer_id'] = '';
                    $data['customer_group_id'] = $this->config->get('config_customer_group_id');
                    $data['firstname'] = '';
                    $data['lastname'] = '';
                    $data['email'] = '';
                    $data['telephone'] = '';
                    $data['customer_custom_field'] = array();
                    
                    $data['addresses'] = array();
                    
                    $data['shipping_firstname'] = '';
                    $data['shipping_lastname'] = '';
                    $data['shipping_company'] = '';
                    $data['shipping_address_1'] = '';
                    $data['shipping_address_2'] = '';
                    $data['shipping_city'] = '';
                    $data['shipping_postcode'] = '';
                    $data['shipping_country_id'] = '';
                    $data['shipping_zone_id'] = '';
                    $data['shipping_custom_field'] = array();
                    $data['shipping_method'] = '';
                    $data['shipping_code'] = '';
                    */ 

                    $data['receiver']['name'] = '';
                    $data['receiver']['email'] = '';
                    $data['receiver']['phone'] = '';
                    $data['receiver']['first_name'] = '';
                    $data['receiver']['last_name'] = '';
                    $data['receiver']['company_name'] = '';
                    $data['receiver']['line1'] = '';
                    $data['receiver']['line2'] = '';
                    $data['receiver']['city'] = '';
                    $data['receiver']['post_code'] = '';
                    $data['receiver']['country_iso_code_2'] = '';
                    $data['receiver']['country_iso_code_3'] = '';

                    $data['order_products'] = array();

                    $data['order_status_id'] = $this->config->get('config_order_status_id');
                    $data['comment'] = '';
                    $data['affiliate_id'] = '';
                    $data['affiliate'] = '';
                    $data['currency_code'] = $this->config->get('config_currency');
                }

                $this->load->model('setting/setting');

                if ($order_info && $order_info['shipping_code']) {
                    $store_info = $this->model_setting_setting->getSetting('config', $order_info['store_id']);
                    
                    if ($store_info) {
                        $data["sender"]["company_name"] = $store_info["config_owner"];
                        $data["sender"]["email"] = $store_info['config_email'];
                        $data["sender"]["phone"] = $store_info['config_telephone'];
                    } else {
                        $data["sender"]["company_name"] = $this->config->get('config_owner');
                        $data["sender"]["email"] = $this->config->get('config_email');
                        $data["sender"]["phone"] = $this->config->get('config_telephone');
                    }
                    $find['id'] = $this->config->get('shipping_inpostoc3_' . $data['shipping_code_inpostoc3_geo_zone_id'] . '_' . $data['shipping_code_inpostoc3_service_id'] . '_sendfrom');
                    $find['result'] = $this->model_extension_shipping_inpostoc3->getRoutes($find);
                    $this->log->write(__METHOD__ .' $find: ' . print_r($find,true)); 
                    if (count($find['result'] ) == 1) {
                        $data["sender"]["country_iso_code_2"] = $find['result'][0]['sender_country_iso_code_2'];
                        $data["sender"]["country_iso_code_3"] = $find['result'][0]['sender_country_iso_code_3'];
                    }  
                }

                $filter['order_id'] = $data['order_id'];
                $data['shipments'] = $this->model_extension_shipping_inpostoc3->getShipments($filter);
                //$this->log->write('Got shipments: ' . print_r($data['shipments'],true));
                if ( empty($data['shipments']) || !isset($data['shipments']) ) {
                    // means no draft stuff was even created, need to save one before serving the view
                    $new_shipment['status'] = 'draft';
                    $new_shipment['order_id'] = $data['order_id'];
                    $new_shipment['service_id'] = $data['shipping_code_inpostoc3_service_id'];
                    $new_shipment['receiver'] = $data['receiver'];
                    $new_shipment['sender'] = $data['sender'];
                    $new_shipment['parcels'][0]['template_id'] = $data['shipping_code_inpostoc3_parcel_template_id'];
                    $new_shipment['custom_attributes']['target_point'] = $data['shipping_code_inpostoc3_target_point'];
                    //$this->log->write(__METHOD__ . 'New shipment: ' . print_r($new_shipment,true));
                    if ( $this->validateNewShipment($new_shipment) ) {
                        $new_shipment['id'] = $this->model_extension_shipping_inpostoc3->createShipment($new_shipment);
                        $filter['order_id'] = $data['order_id']; //just in case
                        $data['shipments'] = $this->model_extension_shipping_inpostoc3->getShipments($filter);
                        //$this->log->write(__METHOD__ .' Created new shipment, $data[\'shipments\']: ' . print_r($data['shipments'],true));     
                    }
                }
                
                // can edit shipment, fill in receiver & sender if empty
                foreach ( $data['shipments'] as $o_shipment ) {
                    if ($o_shipment['status'] == 'draft' ) {
                        $data['shipments'][$o_shipment['id']]['can_edit']['sending_method_details'] = true;
                    } else {
                        $data['shipments'][$o_shipment['id']]['can_edit']['sending_method_details'] = false;
                    }
                    empty($data['shipments'][$o_shipment['id']]['receiver']) ? $data['shipments'][$o_shipment['id']]['receiver'] = $data['receiver'] : '';
                    empty($data['shipments'][$o_shipment['id']]['sender']) ? $data['shipments'][$o_shipment['id']]['sender'] = $data['sender'] : '';
                }

                
                /*foreach( $data['inpostoc3_services'] as $service ) {
                    if ( isset($this->request->post['inpostoc3_service_id_'.$service['service_identifier']]) ) {
                        $data['shipping_code_inpostoc3_service_id'] = $this->request->post['inpostoc3_service_id_'.$service['service_identifier']];
                        $data['inpostoc3_service_id_'.$service['service_identifier']] = $this->request->post['inpostoc3_service_id_'.$service['service_identifier']];
                    } else {

                    }
                

                    //$data['inpostoc3_'.$service['service_identifier'].'_id'] = $service['id'];
                }*/
                /*
                foreach ($inpost_service['parcel_templates'] as $parcel_template) {
                    if (isset($this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id'])) {
                        $data['shipping_inpostoc3_geo_zone_weight_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->request->post['shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id'];
                    } else {
                        $data['shipping_inpostoc3_geo_zone_weight_class_id'][$geo_zone['geo_zone_id']][$parcel_template['id']] = $this->config->get('shipping_inpostoc3_' . $geo_zone['geo_zone_id'] . '_' . $parcel_template['id'] . '_weight_class_id');
                    }
                */
                // API integration enabled?
                if( $data['shipping_inpostoc3_geo_zone_use_api'][$data['shipping_code_inpostoc3_geo_zone_id']] ) {
                    // check if shipment present in db already in 'draft' state. If not - disable truck ('dispatch' action/link) via setting a flag/manipulating truck url here
                    // get sending methods for service                    
                    // prep all fields required to build an InPost API request later - prep for saving in DB any updates

                }

            }


        }

        // assign the children templates and the main template of the view
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/shipping/inpostoc3_order_shipping', $data));
    }


    // === Event handlers
    // admin/view/sale/order_info/before
    public function eventAdminViewOrderInfoBefore(&$route,&$data,&$template_code=null) {
        //$this->log->write(__METHOD__ .' event handler');
        //$this->log->write('Route: ' . $route .', Args: ' . print_r($data,true));
        //$this->log->write(print_r($data,true));
        $template_buffer = $this->getTemplateBuffer( $route, $template_code );
        $replace_order_details = $this->getTemplateBuffer('extension/shipping/inpostoc3_order_details',null);

        // order details part to be modified
        // regex to match multiline part correctly
        $search_pattern = '/{% if shipping_method %}(.*)\s*<tr>(.*)\s*<td><button data-toggle="tooltip" title="{{ text_shipping_method }}" class="btn btn-info btn-xs">(.*)\s*<td>{{ shipping_method }}(.*)\s*<\/tr>\s*{% endif %}/m';

        $template_buffer = preg_replace($search_pattern ,$replace_order_details, $template_buffer);

        // now inject proper data for displaying in modified twig
        if($data['order_id']) {
            $this->load->model('extension/shipping/inpostoc3');
            $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($data['order_id']);
            $data['shipping_code'] = $shipping_code;
            
            if( $this->isItInPostOC3Shipping($shipping_code) ){
                
                // $data gets filled in with service & crucial settings details
                $shipping_code_details = explode('.',$shipping_code);
                $this->fillDataWithDetailsFromShippingCodeDetails($shipping_code_details, $data);
                
                
                // Button for printing dispatch note - replace target attribute if InPost API Integration enabled
                if( $data['shipping_inpostoc3_geo_zone_use_api'][$data['shipping_code_inpostoc3_geo_zone_id']] ) {
                    $search_pattern ='/<a href="{{ shipping }}"\s*target="_blank"\s*data-toggle="tooltip"\s*title="{{ button_shipping_print }}"/m';
                    $replace_button_target ='<a href="{{ shipping }}" data-toggle="tooltip" title="{{ button_shipping_print }}"';
                    $template_buffer = preg_replace($search_pattern ,$replace_button_target, $template_buffer);
                }    
            }
        }

        $template_code = $template_buffer; 

        return null;
    }

    // admin/view/sale/order_shipping/before
    public function eventAdminViewOrderShippingBefore(&$route,&$data) {
        
        if(isset($data['orders']) && !empty($data['orders']) ) {

            $this->load->model('extension/shipping/inpostoc3');
            $this->load->language('extension/shipping/inpostoc3');
            // arm standard dispatch note with some handful info for manual parcel sending
            foreach($data['orders'] as &$order) {
                //$this->log->write('order id found: '.$order['order_id']);
                $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($order['order_id']);
                
                if($this->isItInPostOC3Shipping($shipping_code)) {
                    $shipping_code_details = explode('.',$shipping_code);
                    $service_details = explode('_',$shipping_code_details[1]);

                    $str = "<p style=\"margin-left: 10px\">
                    <span style=\"font-weight: lighter\">".$this->language->get('text_selected_target_point').":</span> ".$shipping_code_details[2] ."<br />
                    <span style=\"font-weight: lighter\">".$this->language->get('text_template_description').":</span> ". $this->language->get('text_template_description_'.$service_details[2]) ."<br />
                    <span style=\"font-weight: lighter\">".$this->language->get('text_service_identifier').":</span> ". $this->language->get('text_'. $this->model_extension_shipping_inpostoc3->getServiceIdentifier($service_details[1]) .'_description') ."<br />
                    </p>";

                    $order['shipping_method'] = $order['shipping_method'] . $str;
                }
                
            }

        }
    }

    // admin/controller/sale/order/shipping/before
    public function eventAdminControllerShippingBefore(&$route,&$data) {
        //$this->log->write('Route: ' . $route .', Args: ' . print_r($data,true));
        $ret = null;

        if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}
        // GET == single order dispatch note action
        if( isset($this->request->get['order_id']) ) {
            
            $order_id = $this->request->get['order_id'];
            $this->load->model('extension/shipping/inpostoc3');
            $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($order_id);
            
            if( $this->isItInPostOC3Shipping($shipping_code) ) {
                
                // $data gets filled in with service & crucial settings details
                $shipping_code_details = explode('.',$shipping_code);
                $this->fillDataWithDetailsFromShippingCodeDetails($shipping_code_details, $data);
                
                // API integration enabled?
                if( $data['shipping_inpostoc3_geo_zone_use_api'][$data['shipping_code_inpostoc3_geo_zone_id']] ) {
                    // break controller chain - return non-null value
                    $ret = $data;
                    // redirect to extension/shipping/inpostoc3/ordershipping
                    $url = '';
                    $url .= '&order_id=' . $order_id;
                    $this->response->redirect($this->url->link('extension/shipping/inpostoc3/ordershipping','user_token=' . $this->session->data['user_token'] . $url, true));
                }
            }
        }
        // else continue with default behaviour
        return $ret;
    }


    // TODO: druk listu przewozowego
    // 1. wpiąc się na before event dla view/sale/order_shipping +
    // 2. sprawdzić, czy metoda to inpostoc3, serwis do paczek && API włączone +
    // 2.1 NIE -> dorzucić do template twig z danymi docelowego paczkomatu, na razie to bedzie wszystko - done
    // 2.2 TAK -> podmienić całościowo twig! Musi ładowac geowidget do wyboru punktów
    // 2.2.1 - Jesli shipment do danego ordera jeszcze nie istnieje w tabelkach inpost, czy dispatch_order (2) czy parcel_locker (1)
    // 2.2.1.1 (parcel_locker) 
    // - punkt nadania musi być wskazany, można zmienić punkt odbioru jeszcze
    // - rozmiar paczki można jeszcze ręcznie zmienić, podczytac wybrany + waga
    // - powiadomienie mailem, default yes, cod = false, 
    // - dane odbiorcy: nazwisko, mail, telefon, adres (z order), refrence to numer zamowienia
    // - SUBMIT:
    //  a) zapis danych w tabelkach inpostoc3
    //  b) request przez wewn. API z id shipmentu -> tam zbudowany zostanie finalny request zakładający paczkę w InPost (jesli dany shipment jest procesowany pierwszy raz)
    //  c) na error wyswietlic komunikat, na sukces -> strzal do wewn. API -> api InPost, pobranie labela w formie PDF i wyswietlenie na ekranie 
    // 2.2.1.2 (dispatch_order) - na razie zostawić w spokoju, w przyszlosci selekcja adresu store jako punktu, do ktorego ma przyjechac kurier po paczke
    // 2.2.2 jesli shipment do danego ordera juz istnieje, to tylko strzal via API po PDF z labelem

    public function validateNewShipment($shipment) {

        if ( empty($shipment['status']) || empty($shipment['order_id']) ||  empty($shipment['service_id']) ) {
            $this->error['warning'] = $this->language->get('error_insufficient_shipment_data');
            $this->log->write(__METHOD__ . ' ' . $this->error['warning']);
        }

        return !$this->error;
    }

    // for AJAX calls and dynamic dropdown filling
    // expecting ?route=extension/shipping/inpostoc3/sendingmethods&service_id=1&user_token=...
    public function sendingMethods() {
        $json = array();

        if ( !isset($this->request->get['service_id']) ) {
            $json['error']['warning'] = $this->language->get('error_no_service');
        } else {
            $this->load->model('extension/shipping/inpostoc3');
            $this->load->language('extension/shipping/inpostoc3');
            $filter['id'] = $this->request->get['service_id'];
            $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocAttributes($filter);
            $this->log->write(__METHOD__ . ' service: ' . print_r($inpost_services, true));
            
            foreach ( $inpost_services as $service ) {

                $this->log->write(__METHOD__ . ' !empty: ' . print_r(!empty($service['sending_methods']), true));
            
                if ( !empty($service['sending_methods']) ) {
                    
                    foreach ( $service['sending_methods'] as $sending_method ) {
                        $service['sending_methods'][$sending_method['sending_method_id']]['description'] = $this->language->get('text_sending_method_' . $sending_method['sending_method_identifier'] );
                    }
                    
                    $json[$service['id']] = $service['sending_methods'];
                    $this->log->write(__METHOD__ . ' json: ' . print_r($json, true));  
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // == general helpers
    protected function preserveUrlParams() {
        $url = '';

        // Sale/Order section params
		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
		}
	
		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}
			
		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
        // endof Sale/Order section params

        return $url;
    }

    protected function isItInPostOC3Shipping($shipping_code) {
        $ret = false;
        $shipping_code_details = explode('.',$shipping_code);
        if($shipping_code_details[0] === 'inpostoc3') {
            $ret = true;
        }
        return $ret;
    }

    protected function fillDataWithDetailsFromShippingCodeDetails($shipping_code_details, &$data) {
        
        $ret = null;
        if(isset($shipping_code_details) && count($shipping_code_details) >=3) {
            $service_details = explode('_',$shipping_code_details[1]);
            $data['shipping_code_inpostoc3_used'] = true;
            $data['shipping_code_inpostoc3_geo_zone_id'] = $service_details[0];
            $data['shipping_code_inpostoc3_service_id'] = $service_details[1];
            // hate to reload model multiple times, anyway to set it once as a class property?
            $this->load->model('extension/shipping/inpostoc3');
            $data['shipping_code_inpostoc3_service_identifier'] = $this->model_extension_shipping_inpostoc3->getServiceIdentifier($service_details[1]);
            ( !empty($service_details[2]) ) ? $data['shipping_code_inpostoc3_parcel_template_identifier'] = $service_details[2] : $data['shipping_code_inpostoc3_parcel_template_identifier'] = '';           
            if ( !empty($data['shipping_code_inpostoc3_service_id']) && !empty($data['shipping_code_inpostoc3_parcel_template_identifier']) ) {
                $filter['service_id'] = $data['shipping_code_inpostoc3_service_id'];
                $filter['template_identifier'] = $data['shipping_code_inpostoc3_parcel_template_identifier'];
                $pt = $this->model_extension_shipping_inpostoc3->getParcelTemplates($filter); // for this $filter setup there must be only one
                $data['shipping_code_inpostoc3_parcel_template_id'] = $pt[0]['id'];
            }
             
            $data['shipping_code_inpostoc3_target_point'] = $shipping_code_details[2];
            $data['shipping_inpostoc3_geo_zone_use_api'][$data['shipping_code_inpostoc3_geo_zone_id']] = $this->config->get('shipping_inpostoc3_' . $data['shipping_code_inpostoc3_geo_zone_id'] . '_use_api');
            
            // todo: 
            // read sending_method and sending_point if applicable;
            // read target point address details if API enabled
            $this->load->language('extension/shipping/inpostoc3');
            $data['text_selected_target_point'] = $this->language->get('text_selected_target_point');
            $data['text_'. $data['shipping_code_inpostoc3_service_identifier'] .'_description'] = $this->language->get('text_'. $data['shipping_code_inpostoc3_service_identifier'] .'_description');
            $data['text_selected_sending_method'] = $this->language->get('text_selected_sending_method');
            $data['text_selected_sending_point'] = $this->language->get('text_selected_sending_point');
            $data['text_selected_sending_address'] = $this->language->get('text_selected_sending_address');
            $data['text_template_description_size_'. $data['shipping_code_inpostoc3_parcel_template_identifier']] = $this->language->get('text_template_description_size_'. $data['shipping_code_inpostoc3_parcel_template_identifier']);
        
            $ret = $service_details;
        }
        return $ret;  
    }

    // == helper functions as per https://forum.opencart.com/viewtopic.php?f=144&t=221533 to modify original twig
    //
    // return template file contents as a string
	protected function getTemplateBuffer( $route, $event_template_buffer ) {
		// if there already is a modified template from view/*/before events use that one
		if ($event_template_buffer) {
			return $event_template_buffer;
		}

		// load the template file (possibly modified by ocmod and vqmod) into a string buffer
		if ($this->isAdmin()) {
			$dir_template = DIR_TEMPLATE;
		} else {
			if ($this->config->get('config_theme') == 'default') {
				$theme = $this->config->get('theme_default_directory');
			} else {
				$theme = $this->config->get('config_theme');
			}
			$dir_template = DIR_TEMPLATE . $theme . '/template/';
		}
		$template_file = $dir_template . $route . '.twig';
		if (file_exists( $template_file ) && is_file( $template_file )) {
			$template_file = $this->modCheck( $template_file );
			return file_get_contents( $template_file );
		}
		if ($this->isAdmin()) {
			trigger_error("Cannot find template file for route '$route'");
			exit;
		}
		$dir_template = DIR_TEMPLATE . 'default/template/';
		$template_file = $dir_template . $route . '.twig';
		if (file_exists( $template_file ) && is_file( $template_file )) {
			$template_file = $this->modCheck( $template_file );
			return file_get_contents( $template_file );
		}
		trigger_error("Cannot find template file for route '$route'");
		exit;
	}


	protected function isAdmin() {
		return defined( 'DIR_CATALOG' ) ? true : false;
	}


	protected function modCheck( $file ) {
		// return a PHP file possibly modified by OpenCart's system/storage/modification,
		//   and then possibly modified by vqmod (see also https://github.com/vqmod/vqmod)

		// Use OpenCart's modified file is available
		$original_file = $file;
		if (defined('DIR_MODIFICATION')) {
			if ($this->startsWith($file,DIR_APPLICATION)) {
				if ($this->isAdmin()) {
					if (file_exists( DIR_MODIFICATION . 'admin/' . substr($file,strlen(DIR_APPLICATION)) )) {
						$file = DIR_MODIFICATION . 'admin/' . substr($file,strlen(DIR_APPLICATION));
					}
				} else {
					if (file_exists( DIR_MODIFICATION . 'catalog/' . substr($file,strlen(DIR_APPLICATION)) )) {
						$file = DIR_MODIFICATION . 'catalog/' . substr($file,strlen(DIR_APPLICATION));
					}
				}
			} else if ($this->startsWith($file,DIR_SYSTEM)) {
				if (file_exists( DIR_MODIFICATION . 'system/' . substr($file,strlen(DIR_SYSTEM)) )) {
					$file = DIR_MODIFICATION . 'system/' . substr($file,strlen(DIR_SYSTEM));
				}
			}
		}

		// Don't use VQmod 2.3.2 or earlier if available
		if (array_key_exists('vqmod', get_defined_vars())) {
			trigger_error( "You are using an old VQMod version '2.3.2' or earlier, please upgrade your VQMod!" );
			exit;
		}

		// Use modification through VQmod 2.4.0 or later if available
		if (class_exists('VQMod',false)) {
			if (VQMod::$directorySeparator) {
				if (strpos($file,'vq2-')!==FALSE) {
					return $file;
				}
				if (version_compare(VQMod::$_vqversion,'2.5.0','<')) {
					trigger_error( "You are using an old VQMod version '".VQMod::$_vqversion."', please upgrade your VQMod!" );
					exit;
				}
				if ($original_file != $file) {
					return VQMod::modCheck($file,$original_file);
				}
				return VQMod::modCheck($original_file);
			}
		}

		// no VQmod
		return $file;
	}


	protected function startsWith( $haystack, $needle ) {
		if (strlen( $haystack ) < strlen( $needle )) {
			return false;
		}
		return (substr( $haystack, 0, strlen($needle) ) == $needle);
	}

    
}