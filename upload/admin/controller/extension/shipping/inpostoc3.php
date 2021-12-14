<?php

class ControllerExtensionShippingInPostOC3 extends Controller {
    private $error = array();
    const HTTP_CODE_OK = 200;
    const HTTP_CODE_CREATED = 201;
    const STATUSES_WO_LABEL = array(
        "draft",
        "created",
        "offers_prepared",
        "offer_selected"
    );
  
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
    
    // == handle single order shipping ================================================================================
    public function orderShipping() {
        //$this->log->write(__METHOD__);

        $this->load->language('extension/shipping/inpostoc3');
        $this->document->addScript('view/javascript/inpostoc3.js');
             
        
        if ( ($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateShipmentsInOrdersOnPost($this->request->post)) {
			$this->saveShipments($this->request->post);
            // save data ^^

			$this->response->redirect($this->url->link('extension/shipping/inpostoc3/ordershipping', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $this->request->get['order_id'], true));
		}

        $data = array ();
        
        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
            $this->error['warning'] = null;
		} else {
			$data['error_warning'] = '';
		}
        if ( isset($this->session->data['success']) ) {
			$data['success'] = $this->session->data['success'];
            $this->session->data['success'] = null;
		} else {
			$data['success'] = '';
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
        $docTitle = $this->language->get('heading_title_order_shipping');
        if(!isset($this->request->get['order_id'])) {
            $data['cancel'] = $data['breadcrumbs'][1]['href'];
        } else {
            $data['order_id'] = $this->request->get['order_id'];
            $data['cancel'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $data['order_id'], true);
            $docTitle = $docTitle .' #' . $this->request->get['order_id'];
        }
        $this->document->setTitle($docTitle );

        $this->load->model('extension/shipping/inpostoc3');
        $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocAttributes();
        
        // expose variable for template
        $data['inpost_services'] =  $inpost_services;         

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
        
                    if ( $order_info['customer_id'] ) {
                        $data['receiver']['name'] = $order_info['customer'].' (#'.$order_info['customer_id'].')';
                    } 
                    $data['receiver']['email'] = $order_info['email'];
                    $data['receiver']['phone'] = $order_info['telephone'];
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
                    //$this->log->write(__METHOD__ .' $find: ' . print_r($find,true)); 
                    if (count($find['result'] ) == 1) {
                        $data["sender"]["country_iso_code_2"] = $find['result'][0]['sender_country_iso_code_2'];
                        $data["sender"]["country_iso_code_3"] = $find['result'][0]['sender_country_iso_code_3'];
                    } 
                    
                    
                }

                $filter['order_id'] = $data['order_id'];
                $data['shipments'] = $this->model_extension_shipping_inpostoc3->getShipments($filter);
                //$this->log->write(__METHOD__ .' $data[\'shipments\']: ' . print_r($data['shipments'],true));

                if ( empty($data['shipments']) || !isset($data['shipments']) ) {
                    // means no draft stuff was even created, need to create & save one before serving the view
                    $this->createShipment($data);
                }
                
                // can edit shipment, fill in receiver & sender if empty and additional control items for view
                $data["inpostoc3_can_edit_order"] = false;
                foreach ( $data['shipments'] as $o_shipment ) {
                    if ($o_shipment['status'] == $this->model_extension_shipping_inpostoc3->getSHIPMENT_STATUS_DRAFT() ) {
                        $data['shipments'][$o_shipment['id']]['can_edit']['sending_method_details'] = true;
                    } else {
                        $data['shipments'][$o_shipment['id']]['can_edit']['sending_method_details'] = false;
                    }
                    $data["inpostoc3_can_edit_order"] = $data["inpostoc3_can_edit_order"] || $data['shipments'][$o_shipment['id']]['can_edit']['sending_method_details'];
                    
                    empty($data['shipments'][$o_shipment['id']]['receiver']) ? $data['shipments'][$o_shipment['id']]['receiver'] = $data['receiver'] : '';
                    empty($data['shipments'][$o_shipment['id']]['sender']) ? $data['shipments'][$o_shipment['id']]['sender'] = $data['sender'] : '';
                    empty($o_shipment["sender"]) ? $o_shipment["sender"] = $data['sender'] : '';

                    $data['sender_country_postcode_required'] = $this->isPostCodeRequired($o_shipment["sender"]);
                    $data['receiver_country_postcode_required'] = $this->isPostCodeRequired($o_shipment["receiver"]);

                    $data['action_save_' . $o_shipment['id']] = $this->url->link('extension/shipping/inpostoc3/ordershipping', 'user_token=' . $this->session->data['user_token']  . '&order_id=' . $o_shipment['order_id'], true);
                    $data['action_dispatch_' . $o_shipment['id']] = $this->url->link('extension/shipping/inpostoc3/ship2inpostapi', 'user_token=' . $this->session->data['user_token'] . '&inpostoc3_shipment_id=' .$o_shipment['id'] . '&geo_zone_id=' .$data['shipping_code_inpostoc3_geo_zone_id'], true);

                    // is label via inpost api available?
                    

                    if ( !empty($o_shipment["number"]) ) {
                        $api_config = $this->getApiConfig($data['shipping_code_inpostoc3_geo_zone_id']);
                        $resp = $this->apiGetShipment($api_config, $o_shipment);
                        $this->handleShipmentPostGetResponse($o_shipment,$resp);
                    }

                    if ( empty($o_shipment["number"]) || 
                        ( !empty($o_shipment["number"]) && in_array($o_shipment['status'],self::STATUSES_WO_LABEL) ) 
                    ) {
                        $data['shipments'][$o_shipment['id']]['label_via_api_ready'] = false;
                    } else {
                        $data['shipments'][$o_shipment['id']]['label_via_api_ready'] = true;
                    }
                }
                //$this->log->write(__METHOD__ .' $data[\'shipments\']: ' . print_r($data['shipments'],true));
                $data['senders'] = $this->model_extension_shipping_inpostoc3->getUniqueSenders();
                $data['parcel_templates'] = $this->model_extension_shipping_inpostoc3->getParcelTemplates();
            }
        }

        // assign the children templates and the main template of the view
        $data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
        $this->response->setOutput($this->load->view('extension/shipping/inpostoc3_order_shipping', $data));
    }

    public function createShipment(&$data) {
        $new_shipment['status'] = $this->model_extension_shipping_inpostoc3->getSHIPMENT_STATUS_DRAFT();
        $new_shipment['order_id'] = $data['order_id'];
        $new_shipment['service_id'] = $data['shipping_code_inpostoc3_service_id'];
        $new_shipment['receiver'] = $data['receiver'];
        //$new_shipment['sender'] = $data['sender'];
        $new_shipment['parcels'][0]['template_id'] = $data['shipping_code_inpostoc3_parcel_template_id'];
        $new_shipment['custom_attributes']['target_point'] = $data['shipping_code_inpostoc3_target_point'];
        //$this->log->write(__METHOD__ . 'New shipment: ' . print_r($new_shipment,true));
        if ( $this->validateNewShipment($new_shipment) ) {
            $new_shipment['id'] = $this->model_extension_shipping_inpostoc3->saveShipment($new_shipment);
            $filter['order_id'] = $data['order_id']; //just in case
            $data['shipments'] = $this->model_extension_shipping_inpostoc3->getShipments($filter);
            //$this->log->write(__METHOD__ .' Created new shipment, $data[\'shipments\']: ' . print_r($data['shipments'],true));     
        }
    }

    public function saveShipments($data) {
 
        //$this->log->write(__METHOD__ .' Saving shipments from $data: ' . print_r($data,true));
        $this->load->model('extension/shipping/inpostoc3');
        $this->error['warning'] ='';
        if ( !isset($this->session->data['success']) ) {
            $this->session->data['success']='';
        }

        foreach ($data['input-inpostoc3'] as $order ) {
            
            foreach ($order['shipments'] as $shipment ) {
                //$this->log->write(__METHOD__ .' shipment to save struct: ' . print_r($shipment,true));
                // grab shipment status - if it's been sent already, can't save
                unset($filter);
                $filter['id'] = $shipment['id'];
                $shpmnts = $this->model_extension_shipping_inpostoc3->getShipments( $filter );
                $s = reset($shpmnts); //there must be one or none
                if (empty($s) || $s['status'] == $this->model_extension_shipping_inpostoc3->getSHIPMENT_STATUS_DRAFT() ) {
                    if ( $this->model_extension_shipping_inpostoc3->saveShipment($shipment) ) {
                        $this->session->data['success'] .=  $this->language->get('text_shipment_saved'). ' (internal id: '.$shipment['id'].")\n";;
                    }

                } else {
                    $this->error['warning'] .= $this->language->get('error_shipment_already_saved'). ' (internal id: '.$shipment['id'].")\n";
                }
            }
        }
        if ( !empty($this->error['warning']) && !empty($this->session->data['success']) ) {
            $this->error['warning'] .= $this->session->data['success'];
        }
        return !$this->error;
    }


    // === Event handlers =============================================================================================
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
        // TODO (perhaps): in case of POST there's a list of order ids
        /*
        $this->request->post["selected"] zawiera numerki
        Dla kazdego sprawdzic, czy jest shipment w module. Jesli tak
        - status wyzszy niz draft - unset tu i do nowej zmiennej, pobrac w petli labele i do zmiennej, wstrzyknąc twig z labelami do sale/order/shipping twig
        - status draft - walidacja, czy są kompletne do wysylki (wszystkie wymagane dane) i stworzyc label lub olac, dostrzyknąć tylko link 'GenerateLabel'
        - pomysł: zapisać otrzymane labele w DB, kwestia prędkości działania
        */
        return $ret;
    }

    public function validateNewShipment($shipment) {

        if ( empty($shipment['status']) || empty($shipment['order_id']) ||  empty($shipment['service_id']) ) {
            $this->error['warning'] = $this->language->get('error_insufficient_shipment_data');
            $this->log->write(__METHOD__ . ' ' . $this->error['warning']);
        }
        return !$this->error;
    }

    public function validateShipmentsInOrdersOnPost($data) {

        $noerr = true ;

        $this->load->language('extension/shipping/inpostoc3'); 
        $this->load->model('extension/shipping/inpostoc3');

        foreach ($data['input-inpostoc3'] as $order ) {    
            foreach ($order['shipments'] as $shipment ) {
                //$this->log->write(__METHOD__ .' shipment to validate struct: ' . print_r($shipment,true));
                $noerr = $noerr && $this->validateShipmentOnPost($shipment);
            }
        }

        return $noerr;
    }

    public function validateShipmentOnPost($shipment) {
        $noerr = true ;
        $errlog ='';

        $this->load->language('extension/shipping/inpostoc3'); 
        $this->load->model('extension/shipping/inpostoc3');
        // as per https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/11731043/1.6.0+Walidacja+formularzy
        if ( empty($shipment['order_id']) ) {
            $noerr = false;
            $errlog .= " [ERROR: Order id missing!]\n";
        }
        if ( empty($shipment['service_id']) || $shipment['service_id'] == $this->model_extension_shipping_inpostoc3->getNONE() )
        { 
            $noerr = false;
            $errlog .= " [ERROR: Service required (*) details missing!]\n";
        }
        if (  empty($shipment['custom_attributes']['sending_method']) || $shipment['custom_attributes']['sending_method'] == $this->model_extension_shipping_inpostoc3->getNONE() ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sending Method required (*) details missing!]\n";
        }
        //check only address details - make shipment save before a call to API
        if ( empty($shipment['sender']['first_name']) || empty($shipment['sender']['last_name']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender first or last name missing!]\n";
        }
        if ( empty($shipment['sender']['email']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender email missing!]\n";
        }
        if ( empty($shipment['sender']['phone']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender phone missing!]\n";
        }
        if (  (empty($shipment['sender']['street']) || empty($shipment['sender']['building_number'])) && ( empty($shipment['sender']['line1']) && empty($shipment['sender']['line2']) ) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender street+building number or address line1+line2 missing!]\n";
        }
        if ( empty($shipment['sender']['city']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender city missing!]\n";
        }
        if ( empty($shipment['sender']['post_code']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  Sender post code missing!]\n";
        }
        if ( empty($shipment['receiver']['first_name']) || empty($shipment['receiver']['last_name']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver first or last name missing!]\n";
        }
        if ( empty($shipment['receiver']['email']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver email missing!]\n";
        }
        if ( empty($shipment['receiver']['phone']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver phone missing!]\n";
        }
        if (  (empty($shipment['receiver']['street']) || empty($shipment['receiver']['building_number'])) && ( empty($shipment['receiver']['line1']) && empty($shipment['receiver']['line2']) ) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver street+building number or address line1+line2 missing!]\n";
        }
        if ( empty($shipment['receiver']['city']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver city missing!]\n";
        }
        if ( empty($shipment['receiver']['post_code']) ) {
            $noerr = false;
            $errlog .= " [ERROR:  receiver post code missing!]\n";
        }
        if ( empty($shipment['parcels'] )
        ) {
            $noerr = false;
            $errlog .= " [ERROR: Parcel details missing!]\n";
        }
        if ( !preg_match("/[0-9]{9}/",$shipment['receiver']['phone']) || !preg_match("/[0-9]{9}/",$shipment['sender']['phone']) ) {
            $noerr = false;
            $errlog .= " [ERROR: Wrong phone number format - must be 9 digits!]\n";
        }
        if ( !filter_var($shipment['receiver']['email'], FILTER_VALIDATE_EMAIL) || !filter_var($shipment['sender']['email'], FILTER_VALIDATE_EMAIL) ) {
            $noerr = false;
            $errlog .= " [ERROR: Invalid email format!]\n";
        }
        if ( $noerr 
            && $shipment['custom_attributes']['sending_method'] == $this->model_extension_shipping_inpostoc3->getSENDING_METHODS()['parcel_locker']['id'] 
            && ( empty($shipment['custom_attributes']['dropoff_point']) || empty($shipment['custom_attributes']['target_point']) ) 
            ) { 
            $noerr = false; 
            $errlog .= " [ERROR: Send via Parcel locker vs. dropofff/target point failed!]\n";
        }
        if (!$noerr) {
            if ( empty( $this->error['warning'] ) ) { $this->error['warning'] = $this->language->get('error_insufficient_shipment_data') ."\n".$errlog;  }
            $this->log->write(__METHOD__ . ' ' . $this->error['warning'] . ' ' . $errlog . ' $shipment: ' . print_r($shipment,true));
        }
        return $noerr;

    }

    // ==== for AJAX calls and dynamic dropdown filling ===============================================================
    // sendingMethods: expecting ?route=extension/shipping/inpostoc3/sendingmethods&service_id=1&user_token=...
    public function sendingMethodsForService() {
        $json = array();

        if ( !isset($this->request->get['service_id']) ) {
            $json['error']['warning'] = $this->language->get('error_no_service');
        } else {
            $this->load->model('extension/shipping/inpostoc3');
            $this->load->language('extension/shipping/inpostoc3');
            $filter['id'] = $this->request->get['service_id'];
            $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocAttributes($filter);
            //$this->log->write(__METHOD__ . ' service: ' . print_r($inpost_services, true));
            
            foreach ( $inpost_services as $service ) {

                //$this->log->write(__METHOD__ . ' !empty: ' . print_r(!empty($service['sending_methods']), true));
            
                if ( !empty($service['sending_methods']) ) {
                    
                    foreach ( $service['sending_methods'] as $sending_method ) {
                        $service['sending_methods'][$sending_method['sending_method_id']]['description'] = $this->language->get('text_sending_method_' . $sending_method['sending_method_identifier'] );
                    }
                    
                    $json[$service['id']] = $service['sending_methods'];
                    //$this->log->write(__METHOD__ . ' json: ' . print_r($json, true));  
                }
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // get details of specific sending method
    public function sendingMethod() {
        $json = array();
        $this->load->model('extension/shipping/inpostoc3');
        $this->load->language('extension/shipping/inpostoc3');

        if ( !isset($this->request->get['sending_method_id']) || $this->request->get['sending_method_id'] == $this->model_extension_shipping_inpostoc3->getNONE() ) {
            $json['error']['warning'] = $this->language->get('error_no_sending_method');
        } else {

            $filter['id'] = $this->request->get['sending_method_id'];
            $sending_methods = $this->model_extension_shipping_inpostoc3->getSendingMethods($filter);
            $sending_method = $sending_methods[0]; // dereference from multi-row structure, ought to be just one but if multiple entries present, as a rule of thumb, pick first one
            if ( !empty($sending_method) ) {
                $sending_method['description'] = $this->language->get('text_sending_method_' . $sending_method['sending_method_identifier'] );
                $json['sending_method'] = $sending_method;
            } else {
                $json['error']['warning'] = $this->language->get('error_no_sending_method');
            }
            
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    // senders: expecting ?route=extension/shipping/inpostoc3/senders&sender_id=1&user_token=...
    public function senders() {
        $json = array();
        if ( !isset($this->request->get['sender_id']) ) {
            $json['error']['warning'] = $this->language->get('error_no_sender');
            //$this->log->write(__METHOD__ . ' json: ' . print_r($json, true)); 
        } else {
            $this->load->model('extension/shipping/inpostoc3');
            $filter['s.sender_id'] = $this->request->get['sender_id'];
            $senders = $this->model_extension_shipping_inpostoc3->getUniqueSenders($filter);
            if (!empty($senders)) {
               $json['sender_id']=$senders;
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    // == general helpers =============================================================================================
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

    protected function isPostCodeRequired($address) {

        $result = false;

        $cfilter['iso_code_2'] = $address["country_iso_code_2"];
        $cfilter['iso_code_3'] = $address["country_iso_code_3"];
        $sender_countries = $this->model_extension_shipping_inpostoc3->getCountriesByFilter($cfilter); //there ought to be one
        if ( count($sender_countries) == 1 ) {
            $result = $sender_countries[0]["postcode_required"];
        }

        return $result;
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

    // ==================== ShipX API part 
    // guzzleHttp in default version 5.x in oc 3.0.3.6 - breaks on SSL connection while regular, up to date curl - not; to the hell with outdated Guzzle then

    // shipping - entry point, API comms & grabbing labels (maybe save them to DB)
    public function ship2InpostApi() {
        //$this->log->write(__METHOD__ );
        $data = array ();
        
        if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
            $this->error['warning'] = null;
		} else {
			$data['error_warning'] = '';
		}
        
        if (!$this->isUserAuthorized()) {
            $this->response->redirect( $data['home'] );
        }
        if (!$this->_is_curl_installed()) {
            $this->error["warning"] = $this->language->get('error_curl_not_installed');
            $this->log->write(__METHOD__ .' '.$this->error["warning"]);
            return $this->error["warning"];
        }

        $this->error['shipments_errors'] ='';
        
        $orders = array();
        $this->load->model('extension/shipping/inpostoc3');
        $data['entry_method'] = $this->request->server['REQUEST_METHOD'];
        
        if ( isset($this->request->post['input-inpostoc3'])  ) {
            
            $orders=$this->request->post['input-inpostoc3']; //orders under 'input-inpostoc3'
            $data["posted"]=print_r($orders,true);
            $this->error['shipments_errors']='';
            
            foreach($orders as $order) {
                $api_config = $this->getApiConfig($order['geo_zone_id']);
                
                foreach($order['shipments'] as $shipment) {
                    $shipment['error'] = !$this->validateShipmentOnPost($shipment);
                    //$this->log->write(__METHOD__ .' POST from form for $shipment: '.print_r($shipment,true));
                    if ( $shipment['error']) {
                        $shipment['error_warning'] = $shipment['id'].': '.$this->error['warning']."\n";   // record error for displaying later, continue
                        $this->error['warning'] = null;
                        $this->log->write(__METHOD__ .' $shipment[\'error_warning\']'.print_r($shipment['error_warning'],true));
                        $this->error['shipments_errors'] .= $shipment['error_warning'];
                    } else {
                        if ( (empty($shipment['status']) || $shipment['status'] == $this->model_extension_shipping_inpostoc3->getSHIPMENT_STATUS_DRAFT() )
                            && empty($shipment['number']) ) {
                            
                            unset($filter);
                            //save shipment and validate succesful save
                            $filter['id'] = $this->model_extension_shipping_inpostoc3->saveShipment($shipment);
                            if ($filter['id'] > 0 ) {
                                $svcs = $this->model_extension_shipping_inpostoc3->getShipments( $filter );
                                $shipment = reset($svcs); //there must be one or none, so refresh data
                                //$this->log->write(__METHOD__ .' $shipment saved & re-read: '.print_r($shipment,true));
                                // create shipment via api
                                $resp = $this->apiPostShipment($api_config, $shipment);
                                $this->handleShipmentPostGetResponse($shipment,$resp);
                            }
                        }
                        sleep(1); // just delay before updating shipment status
                        if ( !empty($shipment['number']) ) {
                            //shipment already sent via API, just get & update the shipment & get label
                            $resp = $this->apiGetShipment($api_config, $shipment);
                            $this->handleShipmentPostGetResponse($shipment,$resp);
                        }
                        $data['orders'][$shipment['order_id']]['order_id'] = $shipment['order_id'];
                        $data['orders'][$shipment['order_id']]['labels'][$shipment['number']]['get_label_url'] = $this->url->link('extension/shipping/inpostoc3/ship2inpostapi', 'user_token=' . $this->session->data['user_token'] . '&inpostoc3_shipment_id=' .$shipment['id'] . '&geo_zone_id=' .$order['geo_zone_id'], true);
                    }
                }
            }    
            //$this->log->write(__METHOD__ .' there\'s a POST received!');
        }
        if ( isset($this->request->get['inpostoc3_shipment_id']) && isset($this->request->get['geo_zone_id']) ) {
            
            $filter['id'] = $this->request->get['inpostoc3_shipment_id'];
            $svcs = $this->model_extension_shipping_inpostoc3->getShipments( $filter );
            $s = reset($svcs); //there must be one or none
            //$this->log->write(__METHOD__ .' there\'s a GET received! $s = '.print_r($s,true));

            $api_config = $this->getApiConfig($this->request->get['geo_zone_id']);

            if ( !empty($s['number']) ) {
                $resp = $this->apiGetShipment($api_config, $s);
                $this->handleShipmentPostGetResponse($s,$resp);
                unset($resp);
                $resp = $this->apiGetShipmentLabel($api_config,$s);
                if ( $resp['info']['http_code'] == $resp['request']['expected_http_code'] ) {
                    header("Content-type: ".$resp['pdf_headers']['content-type']."");
                    header("Content-Disposition: inline; filename=".$s['service_identifier']."_".$s['number'].".pdf");
                    $data['get_label'] = print($resp['body']);
                } else {
                    $this->error["shipments_errors"] = "Label not available. Error details: \n";
                    $this->error["shipments_errors"] .= $resp['body'];
                    $this->log->write(__METHOD__.' GET PATH, '.$this->error["warning"].' $resp  : '.print_r($resp,true));
                }   
            }
            //$this->log->write(__METHOD__ .' there\'s a GET received!');
        } 
        $data['error_warning'] = $this->error['shipments_errors'];
        $data["orders"] = $orders;
        
        $this->displayApiLabels($data);
    }

    // display labels in standalone view
    public function displayApiLabels($data) {
        $this->response->setOutput($this->load->view('extension/shipping/inpostoc3_api_shipping', $data));
    }
  
    // POST Create Shipment
    protected function apiPostShipment($api_config,$shipment) {
        
        // test env wickery due to limited points available in api sandbox
        /*
        if ($api_config['use_sandbox_api']) {
            $shipment['custom_attributes']['dropoff_point'] = "GDA008";
            $shipment['custom_attributes']['target_point'] = "ZGO171";
        }*/
        
        $req = array(
            'baseurl' => ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_endpoint'] : $api_config['production']['api_endpoint'] ) , //plus route
            'route' => "/v1/organizations/" .  ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_org_id'] : $api_config['production']['api_org_id'] ) . "/shipments",
            'method' => 'POST' ,
            'body' => $this->mapShipmentToJsonRequestBody($shipment),
            'queryParams' => null,
            'expected_http_code' => self::HTTP_CODE_CREATED
        );        
        //$this->log->write(__METHOD__.'  $req  : '.print_r($req,true));

        $response = $this->sendRequestViaCurl($api_config,$req);
        //$this->log->write(__METHOD__.'  $response  : '.print_r($response,true));

        return $response;
    }

    // Get Shipment data - update shipment data
    protected function apiGetShipment($api_config,$shipment) {
        $req = array(
            'baseurl' => ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_endpoint'] : $api_config['production']['api_endpoint'] ) , //plus route
            'route' => "/v1/shipments/" .  $shipment['number'],
            'method' => 'GET' ,
            'body' => null,
            'queryParams' => null,
            'expected_http_code' => self::HTTP_CODE_OK
        );
        //$this->log->write(__METHOD__.' the $req: '.print_r($req,true));

        $response = $this->sendRequestViaCurl($api_config,$req);
        //$this->log->write(__METHOD__.'  $response  : '.print_r($response,true));

        return $response;
    }

    // GET Label for shipment
    protected function apiGetShipmentLabel($api_config,$shipment) {
        $req = array(
            'baseurl' => ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_endpoint'] : $api_config['production']['api_endpoint'] ) , //plus route
            'route' => "/v1/shipments/" .  $shipment['number'] .'/label',
            'method' => 'GET' ,
            'body' => null,
            'queryParams' => 'format=pdf',
            'expected_http_code' => self::HTTP_CODE_OK
        );
        //$this->log->write(__METHOD__.' the $req: '.print_r($req,true));

        $response = $this->sendRequestViaCurl($api_config,$req);
        //$this->log->write(__METHOD__.'  $response  : '.print_r($response,true));

        return $response;
    }

    // GET tracking status for manually trigerred tracking statuses refreshes - TODO webhook in future for automatic notifications from InPost

    // GET organization - a test call
    protected function apiGetOrganization($api_config) {
        $req = array(
            'baseurl' => ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_endpoint'] : $api_config['production']['api_endpoint'] ) , //plus route
            'route' => "/v1/organizations/" .  ($api_config['use_sandbox_api'] ? $api_config['sandbox']['api_org_id'] : $api_config['production']['api_org_id'] ) ,
            'method' => 'GET' ,
            'body' => null,
            'queryParams' => null,
            'expected_http_code' => self::HTTP_CODE_OK
        );
        //$this->log->write(__METHOD__.' the $req: '.print_r($req,true));

        $response = $this->sendRequestViaCurl($api_config,$req);
        //$this->log->write(__METHOD__.'  $resp  : '.print_r($response,true));

        return $response;
    }


    // helper: get API configuration from settings (need to read geozone from order/shipping code and use settings for proper geozone)
    protected function getApiConfig($geozone_id) {
        
        $api_config = array ();

        $this->load->model('setting/setting');

        $api_config['geozone_id'] = $geozone_id;
        $api_config['use_api'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_use_api');
        //$this->log->write(__METHOD__.' the $api_config: '.print_r($api_config,true));
        if ( !$api_config['use_api'] ) {
        return null;
        }

        $api_config['use_sandbox_api'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_use_sandbox_api');
        $api_config['sandbox']['api_endpoint'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_sandbox_api_endpoint');
        $api_config['sandbox']['api_token'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_sandbox_api_token');
        $api_config['sandbox']['api_org_id'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_sandbox_api_org_id');
        $api_config['production']['api_endpoint'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_api_endpoint');
        $api_config['production']['api_token'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_api_token');
        $api_config['production']['api_org_id'] = $this->config->get('shipping_inpostoc3_' . $geozone_id . '_api_org_id');

        //$this->log->write(__METHOD__.' the $api_config: '.print_r($api_config,true));
        return $api_config;
    }

    // helper: is user authorized, double check - the controller may be called outside of OC3 web pages, injecting parameters into call
    protected function isUserAuthorized() {
        $unauthorized = true;

        $unauthorized = (!isset($this->request->get['user_token']) || !isset($this->session->data['user_token']) || ($this->request->get['user_token'] != $this->session->data['user_token']));

        if ($unauthorized) { // erase session data and force login via web interface in case it's a case of url with wrong token
        $data['logged'] = '';
                $data['home'] = $this->url->link('common/dashboard', '', true);
        }

        return !$unauthorized;
    }

    // helper: build options for curl
    protected function buildCurlOpts($api_config,$req) {

        $options = array();
        switch($req['method']) {
            case 'GET' : {
                $options[CURLOPT_RETURNTRANSFER] = true;
                break;
            }
            case 'POST' : {
                $options[CURLOPT_POSTFIELDS] = $req['body'];
                $options[CURLOPT_RETURNTRANSFER] = true;
                break;
            }
        }
        
        $options[CURLOPT_URL] = $req['baseurl'] . $req['route'] . '?' . $req['queryParams'];
        //$options[CURLOPT_HEADER] = true;
        $options[CURLINFO_HEADER_OUT] = true;
        $options[CURLOPT_HTTPHEADER] = array(
            'Authorization: Bearer '. ( $api_config['use_sandbox_api'] ? $api_config['sandbox']['api_token'] : $api_config['production']['api_token'] ),
            'Host: ' . preg_replace("/.+:\/\//", "",( $api_config['use_sandbox_api'] ? $api_config['sandbox']['api_endpoint'] : $api_config['production']['api_endpoint'] )),
            'Content-type: application/json',
            'X-Request-ID: ' . $this->GUID()
        );
        $options[CURLOPT_USERAGENT] = "curl";

        //$this->log->write(__METHOD__.' the $options: '.print_r($options,true));
        return $options;
    }

    protected function sendRequestViaCurl($api_config,$req) {

        $resp['headers'] = array();
        
        $ch = curl_init();
        curl_setopt_array($ch, $this->buildCurlOpts($api_config,$req));
        // this function is called by curl for each header received
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$resp)
            {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) // ignore invalid headers
                { return $len; }

                $resp['headers'][strtolower(trim($header[0]))][] = trim($header[1]);

            return $len;
            }
        );
        $resp['request'] = $req;
        $resp['body'] = curl_exec($ch);
        foreach($resp['headers']['content-type'] as $content_type) {
            if( strpos($content_type, 'application/json') !== false ) {
                $resp['body_json_decode'] = json_decode($resp['body'],true);
            }
            if( strpos($content_type, 'application/pdf') !== false ) {
                $resp['pdf_headers']['content-type'] = $content_type;
                $resp['pdf_headers']['content-disposition'] = $resp['headers']['content-disposition'][0];
                $resp['pdf_headers']['content-transfer-encoding'] = $resp['headers']['content-transfer-encoding'][0];
                $resp['pdf_headers']['content-length'] = strlen($resp['body']);
                $resp['pdf_headers']['accept-ranges'] = 'bytes';
            }
        }
        $resp['info'] = curl_getinfo($ch);
        //$this->log->write(__METHOD__.' $resp: '.print_r($resp,true));
        if ( $resp['info']['http_code'] != $req['expected_http_code'] ) {
            $this->log->write(__METHOD__.' Error! Expected code: '. $req['expected_http_code'] .', received code: '.  $resp['info']['http_code'] .' $resp: '.print_r($resp,true));
            $this->error["warning"] = $resp['body'];
        }
        // TODO: write request and response to DB as log if respective setting in configuration enabled
        curl_close($ch);

        return $resp;
    }
    // helper - handle response for post/get shipment
    protected function handleShipmentPostGetResponse(&$shipment,$resp) {
        //$this->log->write(__METHOD__.' $resp  : '.print_r($resp,true));
        if ($resp['info']['http_code'] == $resp['request']['expected_http_code'] ) {
            $this->mapResponseToShipment($shipment,$resp);
            $this->model_extension_shipping_inpostoc3->saveShipment($shipment) ;
            $shipment["error"] = false;
         } else {
             $this->error["warning"] = "Problem with action on shipment via API! Try again later.";
             $this->log->write(__METHOD__.' '.$this->error["warning"].' $resp  : '.print_r($resp,true));
             $shipment["error"] = true;
             $shipment['error_warning'] = $resp['body'];
         }
    }


    // helper: serialize data to JSON to quickly build requests
    protected function mapShipmentToJsonRequestBody($shipment) {
        $json = array();
        $this->load->model('extension/shipping/inpostoc3');

        $this->fillInGapsInShipment($shipment);
        //$this->log->write(__METHOD__.'  $shipment  : '.print_r($shipment,true));
        
        $json['reference'] = $shipment['order_id'];
        $json['service'] = $shipment['service_identifier'];
        $json['comments'] = 'Created by user id: '. $this->session->data['user_id'];
        if ( !empty($shipment['custom_attributes']) ) {
            $json['custom_attributes']['sending_method'] = $shipment['custom_attributes']['sending_method_identifier'];
            $json['custom_attributes']['target_point'] = $shipment['custom_attributes']['target_point'];
            if ( $shipment['custom_attributes']['sending_method'] ==  $this->model_extension_shipping_inpostoc3->getSENDING_METHODS()['parcel_locker']['id'] ) {
                $json['custom_attributes']['dropoff_point'] = $shipment['custom_attributes']['dropoff_point'];
            }     
        }
        $index = 0;
        foreach ($shipment['parcels'] as $parcel) {
            $json['parcels'][$index]['id']          = $parcel['id'];
            $json['parcels'][$index]['template']    = $parcel['template_identifier'];
            $index++;
        }

        $peers = array("sender", "receiver");
        foreach($peers as $peer) {
            $json[$peer]['name'] =                       $shipment[$peer]['name'];
            $json[$peer]['company_name'] =               $shipment[$peer]['company_name'];
            $json[$peer]['first_name'] =                 $shipment[$peer]['first_name'];
            $json[$peer]['last_name'] =                  $shipment[$peer]['last_name'];
            $json[$peer]['email'] =                      $shipment[$peer]['email'];
            $json[$peer]['phone'] =                      $shipment[$peer]['phone'];
            // as per https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/11731043/1.6.0+Walidacja+formularzy
            // their api performs same checks
            if ( !empty($shipment[$peer]['street']) || !empty($shipment[$peer]['building_number']) ) {
                $json[$peer]['address']['street'] =          $shipment[$peer]['street'];
                $json[$peer]['address']['building_number'] = $shipment[$peer]['building_number'];
            } 
            if ( !empty($shipment[$peer]['line1']) ) {
                $json[$peer]['address']['line1'] =           $shipment[$peer]['line1'];
                $json[$peer]['address']['line2'] =           $shipment[$peer]['line2'];
            }
            $json[$peer]['address']['city'] =            $shipment[$peer]['city'];
            $json[$peer]['address']['post_code'] =       $shipment[$peer]['post_code'];
            $json[$peer]['address']['country_code'] =    $shipment[$peer]['country_iso_code_2'];
        }

        return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
    }

    // helper: grab string identifiers if missing - it shouldn't, but just in case something didn't arrive within structure
    protected function fillInGapsInShipment(&$shipment) {
        if ( empty($shipment['service_identifier']) ){
            unset($filter);
            $filter['id'] = $shipment['service_id'];
            $services = $this->model_extension_shipping_inpostoc3->getServices( $filter );
            $service = reset($services); //get only one
            $shipment['service_identifier'] = $service['service_identifier'];
        }
        if ( !empty($shipment['custom_attributes']) && empty($shipment['custom_attributes']['sending_method_identifier'] )   ) {
            unset($filter);
            $filter['id'] = $shipment['custom_attributes']['sending_method'];
            $sm = $this->model_extension_shipping_inpostoc3->getSendingMethods( $filter );
            $sending_method = reset($sm); //get only one
            $shipment['custom_attributes']['sending_method_identifier'] = $sending_method['sending_method_identifier'];
        }
        foreach ($shipment['parcels'] as $parcel) {
            if ( empty($parcel['template_identifier']) ) {
                $filter['id'] = $parcel['template_id'];
                $ts = $this->model_extension_shipping_inpostoc3->getParcelTemplates( $filter);
                $t = reset ($ts ); //get only one - first one
                $parcel['template_identifier'] = $t['template_identifier']; 
            }
        }
    }

    // helper: deserialize synchronous responses to have data to update shipment entity
    protected function mapResponseToShipment(&$shipment,$resp) {
        $shipment['status']             = $resp['body_json_decode']['status'];
        $shipment['number']             = $resp['body_json_decode']['id'];
        $shipment['tracking_number']    = $resp['body_json_decode']['tracking_number'];
        foreach ($resp['body_json_decode']['parcels'] as $parcel ) {
            //unfortunately nearly random sequence, as inpost api doesn't return sent parcel id
            // match by sent data only :-(
            $filter['template_identifier'] = $parcel['template'];
            $ts = $this->model_extension_shipping_inpostoc3->getParcelTemplates( $filter);
            $t = reset ($ts ); //get only one - first one
            foreach ($shipment['parcels'] as $key => $s_parcel ) {
                // settle for first match, sorry, original s_parcel.id is returned in response
                if ($s_parcel['template_id'] == $t['id'] ) {
                    $shipment['parcels'][$key]['number']             = $parcel['id'];
                    $shipment['parcels'][$key]['tracking_number']    = $parcel['tracking_number'];
                }
            }
        }
    }

    // helper: check for curl installation
    protected function _is_curl_installed() {
        if  (in_array  ('curl', get_loaded_extensions())) {
            return true;
        }
        else {
            return false;
        }
    }
    
    // helper: GUID generator
    protected function GUID() {
        if (function_exists('com_create_guid') === true)
        {
            return trim(com_create_guid(), '{}');
        }

        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
    
}