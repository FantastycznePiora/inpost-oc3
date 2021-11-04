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
                `template_identifier` varchar(100) UNIQUE COMMENT 'https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/11731062/1.9.1+Rozmiary+i+us+ugi+dla+przesy+ek',
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
                `number` varchar(100) NULL,
                `tracking_number` varchar(100) NULL,  
                `status` varchar(100) NULL,
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
                `number` varchar(100) NULL,
                `tracking_number` varchar(100) NULL,  
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

    public function getParcelTemplates( $filter = array() ) {
        $result = null;
        $sql = "
            SELECT * FROM `inpostoc3_parcel_templates` 
        ";
        $allowed_keys = array ("id", "service_id", "template_identifier", "min_height", "max_height", "max_width", "max_length", "max_weight");

        $sql = $sql . $this->sqlBuildSimpleWhere($filter, $allowed_keys) . ";";

        $query = $this->db->query ($sql);
        $result = array();
        foreach($query->rows as $row){
            $result[]=$row;
        }
        return $result; 
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

    public function getParcels( $filter=array() ) {
        $result = null;
        $sql = "
        SELECT * FROM `inpostoc3_parcels`
        ";
        $allowed_keys = array ("id", "shipment_id", "number", "tracking_number", "template_id");

        $sql = $sql . $this->sqlBuildSimpleWhere($filter, $allowed_keys) . ";";

        $query = $this->db->query ($sql);
        $result = array();
        foreach($query->rows as $row){
            $result[$row['id']]=$row;
        }
        return $result;
    }

    public function getCustomAttributes( $filter=array() ) {
        $result = null;
        $sql = "
        SELECT * FROM `inpostoc3_custom_attributes`
        ";
        $allowed_keys = array ("id", "shipment_id", "target_point", "dropoff_point","dispatch_order_id","allegro_user_id","allegro_transaction_id");

        $sql = $sql . $this->sqlBuildSimpleWhere($filter, $allowed_keys) . ";";

        $query = $this->db->query ($sql);
        $result = array();
        foreach($query->rows as $row){
            $result[]=$row;
        }
        return $result;
    }

    public function getShipments($filter) {
        $result = null;
        $sql = "
        SELECT * FROM `inpostoc3_shipments`
        ";
        $allowed_keys = array ("id", "order_id", "number", "tracking_number", "receiver_id", "service_id", "is_return");

        $sql = $sql . $this->sqlBuildSimpleWhere($filter, $allowed_keys) . ";";
        $this->log->write(__METHOD__ . ' $sql: ' .$sql);

        $query = $this->db->query ($sql);

        $this->log->write(__METHOD__ . ' $query: ' . print_r($query,true));
        $result = array();
        foreach($query->rows as $row){
            
            $filter2['shipment_id'] = $row['id'];
            $row['parcels'] = $this->getParcels($filter2);
            $row['custom_attributes'] = $this->getCustomAttributes($filter2)[0]; // one set per shipment
            $result[$row['id']]=$row;

            $this->log->write(__METHOD__ . ' $row: ' . print_r($row,true));

        }
        return $result;
    }

    public function createParcel($parcel) {

        $sql = "
            INSERT INTO `inpostoc3_parcels`
            (`shipment_id`,`template_id`)
            VALUES
            ( 
                '". $parcel['shipment_id'] ."',
                '". $parcel['template_id'] ."'
            );
        ";
        $this->db->query($sql);
        $parcel_id = $this->db->getLastId();
        return $parcel_id;
    }

    public function createCustomAttributes($cattr) {
        $sql = "
            INSERT INTO `inpostoc3_custom_attributes` 
            (`shipment_id`,`target_point`)
            VALUES
            (
                '". $cattr['shipment_id']."',
                '". $cattr['target_point']."'
            );
        ";
        $this->db->query($sql);
        $cattr_id = $this->db->getLastId();
        return $cattr_id;
    }

    public function createShipment($shipment) {
        
        $sql ="
            INSERT INTO `inpostoc3_shipments` 
            (`order_id`,`service_id`,`status`)
            VALUES 
            (
                '". $shipment['order_id']."',
                '". $shipment['service_id']."',
                '". $shipment['status']."'
            );
        ";
        $this->db->query($sql);
        $shipment_id = $this->db->getLastId();
        
        if($shipment_id) {
            foreach( $shipment['parcels'] as $parcel ) {
                $parcel['shipment_id'] = $shipment_id;
                if( !empty($parcel['template_id']) ) {
                    $this->createParcel($parcel);
                }
            }
            if ( !empty($shipment['custom_attributes']) ) {
                $shipment['custom_attributes']['shipment_id'] = $shipment_id;
                $this->createCustomAttributes($shipment['custom_attributes']);
            }
        }
        
        return $shipment_id;
    }

    public function saveParcel($parcel) {

        /*$defaultParcel = array(
            "number" => null,
            "tracking_number" => null,
            "is_non_standard" -> 0
        );
        $save = array_merge($defaultParcel,$parcel);
        */

        $sql = "
            INSERT INTO `inpostoc3_parcels` 
            (`id`,`shipment_id`,`template_id`,`number`,`tracking_number`,`is_non_standard`)
            VALUES 
            ( '". $parcel['id']."','". $parcel['shipment_id']."','". $parcel['template_id']."','". $parcel['number']."','". $parcel['tracking_number']."','". $parcel['is_non_standard']."')
            ON DUPLICATE KEY UPDATE
              shipment_id = ". $parcel['shipment_id'] .",
              template_id = ". $parcel['template_id'] .",
              number = ". $parcel['tracking_number'] .",
              tracking_number = ". $parcel['tracking_number'] .",
              is_non_standard = ". $parcel['is_non_standard'] .";
        ";
        $this->db->query($sql);
        $parcel_id = $this->db->getLastId();
        return $parcel_id;
    }

    public function saveCustomAttributes($cattr) {

        $sql = "
            INSERT INTO `inpostoc3_custom_attributes` 
            (`id`,`shipment_id`,`target_point`,`dropoff_point`,`sending_method`,`dispatch_order_id`,`allegro_user_id`,`allegro_transaction_id`)
            VALUES 
            ( 
                '". $cattr['id']."',
                '". $cattr['shipment_id']."',
                '". $cattr['target_point']."',
                '". $cattr['dropoff_point']."',
                '". $cattr['sending_method']."',
                '". $cattr['dispatch_order_id']."',
                '". $cattr['allegro_user_id']."',
                '". $cattr['allegro_transaction_id']."'
                )
            ON DUPLICATE KEY UPDATE
              shipment_id = ". $cattr['shipment_id'] .",
              target_point = ". $cattr['target_point'] .",
              dropoff_point = ". $cattr['dropoff_point'] .",
              sending_method = ". $cattr['sending_method'] .",
              dispatch_order_id = ". $cattr['dispatch_order_id'] .",
              allegro_user_id = ". $cattr['allegro_user_id'] .",
              allegro_transaction_id = ". $cattr['allegro_transaction_id'] ."
              ;
        ";
        $this->db->query($sql);
        $cattr_id = $this->db->getLastId();
        return $cattr_id;
    }

    public function saveShipment ($shipment) {

        $sql = "
            INSERT INTO `inpostoc3_shipments` 
            (`id`,`order_id`,`service_id`,`number`,`tracking_number`,`status`,`receiver_id`,`additional_services`,`is_return`)
            VALUES 
            ( 
            '". $shipment['id']."',
            '". $shipment['order_id']."',
            '". $shipment['service_id']."',
            '". $shipment['number']."',
            '". $shipment['tracking_number']."',
            '". $shipment['status']."',
            '". $shipment['receiver_id']."',
            '". $shipment['additional_services']."',
            '". $shipment['is_return']."'
            )
            ON DUPLICATE KEY UPDATE
              service_id = ". $shipment['service_id'] .",
              number = ". $shipment['tracking_number'] .",
              tracking_number = ". $shipment['tracking_number'] .",
              status = ". $shipment['status'] .",
              receiver_id = ". $shipment['receiver_id'] .",
              additional_services = ". $shipment['additional_services'] .",
              is_return = ". $shipment['is_return'] ."
              ;
        ";
        $this->db->query($sql);
        $shipment_id = $this->db->getLastId();

        foreach ( $shipment['parcels'] as $parcel ) {
            $parcel['shipment_id'] = $shipment_id;
            $this->saveParcel($parcel);
        }
        foreach ( $shipment['custom_attributes'] as $cattr ) {
            $cattr['shipment_id'] = $shipment_id;
            $this->saveCustomAttributes($cattr);
        }

        return $shipment_id;
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