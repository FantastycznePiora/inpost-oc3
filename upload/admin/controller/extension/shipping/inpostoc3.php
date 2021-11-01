<?php
class ControllerExtensionShippingInPostOC3 extends Controller {
    private $error = array();
  
    public function index() {
        $this->load->language('extension/shipping/inpostoc3');
        $this->document->setTitle($this->language->get('heading_title'));
		
        //save extension settings into DB
        $this->load->model('setting/setting');

        //specific inpostoc3 model
        //$this->log->write(print_r('controller\extension\shipping\inpostoc3 index before model load', true));
        $this->load->model('extension/shipping/inpostoc3');
        $inpost_services = $this->model_extension_shipping_inpostoc3->getServicesWithAssocParcelTemplates();
        //$inpost_parcel_templates = $this->model_extension_shipping_inpostoc3->getParcelTemplates();
        //$this->log->write(print_r($this->model_extension_shipping_inpostoc3->getServices(), true));
        //$this->log->write(print_r($this->model_extension_shipping_inpostoc3->getParcelTemplates(), true));
        
        
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
            foreach($inpost_services as $inpost_service){

                if (isset($this->request->post['shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status'])) {
                    $data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->request->post['shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status'];
                } else {
                    $data['shipping_inpostoc3_geo_zone_status'][$geo_zone['geo_zone_id']][$inpost_service['id']] = $this->config->get('shipping_inpostoc3_'. $geo_zone['geo_zone_id'] . '_' . $inpost_service['id'] . '_status');
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

                        // add max_height, max_width, max_length for mm
                        // add max_weight for kg
                        // these will determine wheter to use template small/medium/large and usually should be ca 2cm under default limits

            }
                       
            //country check for this specific geozone in order to hide/show API options
            $data['shipping_inpostoc3_geo_zone_hide_api'][$geo_zone['geo_zone_id']] = false;
            $zones_to_gz = $this->model_localisation_geo_zone->getZoneToGeoZones($geo_zone['geo_zone_id']);

            foreach ($zones_to_gz as $zone)
            {
                if((int)$zone['country_id'] != 170 ) {
                    $data['shipping_inpostoc3_geo_zone_hide_api'][$geo_zone['geo_zone_id']] = true;
                }
            }
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
            }
        }
        // expose variables for template
        $data['geo_zones'] = $geo_zones;
        $data['inpost_services'] =  $inpost_services;
        //$data['inpost_parcel_templates'] = $inpost_parcel_templates;
        
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
        //TODO maybe add ParcelLocker  size/class validations here?
        if (!$this->user->hasPermission('modify', 'extension/shipping/inpostoc3')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;


    }
 

    public function install() {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->editSetting('inpostoc3', ['inpostoc3_status'=>1]);*/
        $this->log->write(print_r('controller\extension\shipping\inpostoc3 install before db install', true));
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
        
    }
 
    public function uninstall() {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting(‘inpostoc3’);*/
        $this->log->write(print_r('controller\extension\shipping\inpostoc3 uninstall before db uninstall', true));
        $this->load->model('extension/shipping/inpostoc3');
        $this->model_extension_shipping_inpostoc3->uninstall();

        //cleanup events registered in install
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventCatalogCheckoutShippingMethodAfter');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderInfoBefore');
        $this->model_setting_event->deleteEventByCode('inpostoc3_eventAdminViewOrderShippingBefore');
    }


    // Event handlers

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
        
        $matches = array();
        preg_match_all($search_pattern, $template_buffer, $matches);        

        $template_buffer = preg_replace($search_pattern ,$replace_order_details, $template_buffer);

        // TODO: shipping button printing dispatch note replace

        $template_code = $template_buffer;

        // now inject proper data
        if($data['order_id']) {
            $this->load->model('extension/shipping/inpostoc3');
            $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($data['order_id']);
            $data['shipping_code'] = $shipping_code;
            
            $shipping_code_details = explode('.',$shipping_code);
            if($shipping_code_details[0] === 'inpostoc3') {
                $service_details = explode('_',$shipping_code_details[1]);
                $data['shipping_code_inpostoc3_used'] = true;
                $data['shipping_code_inpostoc3_geo_zone_id'] = $service_details[0];
                $data['shipping_code_inpostoc3_service_id'] = $service_details[1];
                $data['shipping_code_inpostoc3_service_identifier'] = $this->model_extension_shipping_inpostoc3->getServiceIdentifier($service_details[1]);
                $data['shipping_code_inpostoc3_parcel_template_identifier'] = $service_details[2];
                $data['shipping_code_inpostoc3_target_point'] = $shipping_code_details[2];
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
                
                // jeśli jest API, podmienić button do printowania na submita i przekierować na kontroler do wysłania paczki, ale w sale/order/inpostoc3
            }

        }

        return null;
    }

    // admin/view/sale/order_shipping/before
    public function eventAdminViewOrderShippingBefore(&$route,&$data) {
        
        if(isset($data['orders']) && !empty($data['orders']) ) {

            $this->load->model('extension/shipping/inpostoc3');
            $this->load->language('extension/shipping/inpostoc3');
            // arm standard dispatch note with some handful info for manual parcel sending
            foreach($data['orders'] as &$order) {
                $this->log->write('order id found: '.$order['order_id']);
                $shipping_code = $this->model_extension_shipping_inpostoc3->getShippingCodeFromOrder($order['order_id']);
                $shipping_code_details = explode('.',$shipping_code);
                if($shipping_code_details[0] === 'inpostoc3') {

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


    // TODO: druk listu przewozowego
    // 1. wpiąc się na before event dla view/sale/order_shipping
    // 2. sprawdzić, czy metoda to inpostoc3, serwis do paczek && API włączone
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