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
    }
 
    public function uninstall() {
        /*$this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting(‘inpostoc3’);*/
        $this->log->write(print_r('controller\extension\shipping\inpostoc3 uninstall before db uninstall', true));
        $this->load->model('extension/shipping/inpostoc3');
        $this->model_extension_shipping_inpostoc3->uninstall();
    }
}