<?php

class ModelExtensionShippingInPostOC3 extends Model {
    
    public function install() {
        //$this->log->write(print_r('model\extension\shipping\inpostoc3 install before db install', true));
        $this->load->language('extension/shipping/inpostoc3');

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_services` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `service_identifier` VARCHAR(100) UNIQUE NOT NULL,
                INDEX(`service_identifier`),
                PRIMARY KEY(`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("       
            INSERT IGNORE INTO `inpostoc3_services` (`id`,`service_identifier`) VALUES
            ( 1 , 'inpost_locker_standard' );
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_services_routing` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `service_id` INT(11) NOT NULL,
                `sender_country_iso_code_3` CHAR(3) NOT NULL COMMENT 'ISO 3166-1 alfa-3 code',
                `receiver_country_iso_code_3` CHAR(3) NOT NULL COMMENT 'ISO 3166-1 alfa-3 code',
                PRIMARY KEY(`id`),
                FOREIGN KEY (`service_id`) REFERENCES `inpostoc3_service`(`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        $this->db->query("
            INSERT IGNORE INTO `inpostoc3_services_routing` (`id`,`service_id`,`sender_country_iso_code_3`,`receiver_country_iso_code_3`) VALUES
            (1,1,'POL','POL');
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_sending_method` (
            `id` INT(11) UNIQUE AUTO_INCREMENT,
            `sending_method_identifier` VARCHAR(100) UNIQUE NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY(`id`),
            INDEX(`sending_method_identifier`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        $this->db->query("
            INSERT IGNORE INTO `inpostoc3_sending_method` (`id`,`sending_method_identifier`) VALUES
            (1, 'parcel_locker' );
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_services_sending_method` (
            `id` INT(11) UNIQUE AUTO_INCREMENT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
            `service_id` INT(11) NOT NULL,
            `sending_method_id` INT(11) NOT NULL,
            PRIMARY KEY(`id`),
            FOREIGN KEY (`service_id`) REFERENCES `inpostoc3_service`(`id`),
            FOREIGN KEY (`sending_method_id`) REFERENCES `inpostoc3_sending_method`(`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci; 
        ");
        $this->db->query("
            INSERT IGNORE INTO `inpostoc3_services_sending_method` (`id`,`service_id`, `sending_method_id`) VALUES
            (1, 1, 1 );
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_parcel_templates` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `service_id` INT(11) NOT NULL,
                `template_identifier` varchar(100) NULL COMMENT 'https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/11731062/1.9.1+Rozmiary+i+us+ugi+dla+przesy+ek',
                `min_height` INT(3) NULL COMMENT 'mm',
                `max_height` INT(3) NULL COMMENT 'mm',
                `max_width` INT(3) NULL COMMENT 'mm',  
                `max_length` INT(3) NULL COMMENT 'mm',
                `max_weight` INT(3) NULL COMMENT 'kg',
                PRIMARY KEY(`id`),
                INDEX (`service_id`),
                FOREIGN KEY (`service_id`) REFERENCES `inpostoc3_services`(`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
          
        ");                
        $this->db->query("
            INSERT IGNORE INTO `inpostoc3_parcel_templates`(
                `id`,
                `service_id`,
                `template_identifier`,
                `min_height`,
                `max_height`,
                `max_width`, 
                `max_length`,
                `max_weight`
            ) VALUES 
            (1,1,'small',1,80,380,640,25),
            (2,1,'medium',81,190,380,640,25),
            (3,1,'large',191,410,380,640,25);
        ");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_shipments` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `order_id` INT(11) NOT NULL,
                `service_id` INT(11) NOT NULL,
                `shipment_number` varchar(100) NULL,
                `tracking_number` varchar(100) NULL,  
                `shipment_status` varchar(100) NULL,
                `tracking_status` varchar(100) NULL,
                `receiver_id` varchar(100) NULL,
                `additional_services` tinyint(1) DEFAULT 0,
                `is_return` tinyint(1) DEFAULT 0,
                PRIMARY KEY(`id`),
                INDEX (`order_id`),
                FOREIGN KEY (`order_id`) REFERENCES `" . DB_PREFIX . "oc_order`(`id`),
                FOREIGN KEY (`service_id`) REFERENCES `inpostoc3_services`(`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_custom_attributes` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `shipment_id` INT(11) NOT NULL,
                `target_point` varchar(100) NULL,
                `dropoff_point` varchar(100) NULL,  
                `sending_method` varchar(100) NULL,
                `dispatch_order_id` varchar(100) NULL,
                `allegro_user_id` varchar(100) NULL,
                `allegro_transaction_id` varchar(100) NULL,
                PRIMARY KEY(`id`),
                INDEX (`shipment_id`),
                FOREIGN KEY (`shipment_id`) REFERENCES `inpostoc3_shipments`(`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_parcels` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `shipment_id` INT(11) NOT NULL,
                `template_id` INT(11) NOT NULL,
                `parcel_number` varchar(100) NULL,
                `parcel_tracking_number` varchar(100) NULL,  
                `is_non_standard` tinyint(1) DEFAULT 0,
                PRIMARY KEY(`id`),
                INDEX (`shipment_id`),
                INDEX (`template_id`),
                FOREIGN KEY (`shipment_id`) REFERENCES `inpostoc3_shipments`(`id`),
                FOREIGN KEY (`template_id`) REFERENCES `inpostoc3_parcel_templates`(`id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");  
        
        // check if length class for mm is defined, if not - add one/show warning
        // check if weight class for kg is defined, if not - add one/show warning

    }
    
    public function uninstall() {
        //$this->log->write(print_r('model\extension\shipping\inpostoc3 uninstall before db uninstall', true));
        // do nothing, preserve data 
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

    public function getParcelTemplates() {
        
        $query = $this->db->query("
            SELECT * FROM `inpostoc3_parcel_templates`;
        ");
        $results = array();
        foreach($query->rows as $row){
            $results[]=$row;
        }
        return $results; 
    }

    public function getServicesToSendingMethods($service_id=null) {
        $sql = "
        SELECT
            t1.id AS service_id,
            t1.service_identifier,
            t3.id as sending_method_id,
            t3.sending_method_identifier
        FROM `inpostoc3_services` t1
        INNER JOIN `inpostoc3_services_sending_method` AS t2
            ON t1.id = t2.service_id
        INNER JOIN `inpostoc3_sending_method` AS t3
            ON t2.sending_method_id = t3.id";

        if($service_id) {
            $sql = $sql . "
            WHERE t1.id = '". int($service_id) ."'
            "; 
        }
        $sql = $sql . ";";
        $query = $this->db->query ($sql);
        $results = array();
        foreach($query->rows as $row){
            $results[]=$row;
        }
        return $results; 
    }

    public function getServicesAllowedRoutes($service_id=null) {
        $sql ="
        SELECT 
            t1.id,
            t1.service_id,
            t1.sender_country_iso_code_3,
            t1.receiver_country_iso_code_3
        FROM `inpostoc3_services_routing` t1
        ";
        if($service_id) {
            $sql = $sql . "
            WHERE `service_id` = '" . (int)$service_id . "'
            ";
        }
        $sql = $sql . ";";
        $query = $this->db->query ($sql);
        $results = array();
        foreach($query->rows as $row){
            $results[]=$row;
        }
        return $results; 
    }

    public function getShippingCodeFromOrder($order_id) {
        //$this->log->write(__METHOD__ .' with order id: '.$order_id);
        $result = null;
        if($order_id) {
            $query = $this->db->query("
            SELECT `shipping_code` FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'
            ");

            if($query->num_rows == 1){ // there shall be only one!
                $result = $query->row['shipping_code'];
            }
        }
        return $result; 
    }

    public function getServiceIdentifier($service_id) {
        //$this->log->write(__METHOD__ .' with service id: '.$service_id);
        $result = null;
        if($service_id){
            $query = $this->db->query("
            SELECT `service_identifier` FROM `inpostoc3_services` WHERE `id` = '" . (int)$service_id . "';
            ");

            if($query->num_rows == 1) { // there shall be only one!
                $result = $query->row['service_identifier'];
            }
        }
        return $result; 
    }

    public function getParcelTemplateIdentifier($parcel_template_id) {
        $result = null;
        if($parcel_template_id){
            $query = $this->db->query("
            SELECT `template_identifier` FROM `inpostoc3_parcel_templates` WHERE `id` = '" . (int)$parcel_template_id . "';
            ");

            if($query->num_rows == 1) { // there shall be only one!
                $result = $query->row['template_identifier'];
            }
        }
        return $result; 
    }

    public function getServicesWithAssocAttributes() {
        $services = $this->getServices();
        $parcel_templates = $this->getParcelTemplates();
        $services_sending_methods = $this->getServicesToSendingMethods();
        $services_allowed_routes = $this->getServicesAllowedRoutes();

        foreach( $services as $serviceK => $serviceV ){
            foreach($parcel_templates as $parcel_template)  {
                if($parcel_template['service_id']===$services[$serviceK]['id']){
                   $services[$serviceK]['parcel_templates'][$parcel_template['id']]=$parcel_template; 
                }
            }  
            foreach( $services_sending_methods as $service_sending_methods ){
                if ( $service_sending_methods['service_id']===$services[$serviceK]['id'] ) {
                    $services[$serviceK]['sending_methods'][$service_sending_methods['sending_method_id']] = array(
                        "sending_method_id" => $service_sending_methods['sending_method_id'],
                        "sending_method_identifier" => $service_sending_methods['sending_method_identifier']
                    );
                }
            }
            foreach ( $services_allowed_routes as $service_allowed_routes ) {
                if ( $service_allowed_routes['service_id']===$services[$serviceK]['id'] ) {
                    $services[$serviceK]['allowed_routes'][$service_allowed_routes['id']] = $service_allowed_routes;
                }
            }
        }

        return $services;
    }

    public function getShipments($order_id) {
        
    }
}