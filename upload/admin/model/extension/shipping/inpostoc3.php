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
                `service_identifier` VARCHAR(100) NOT NULL,
                `service_name` VARCHAR(250) NULL,
                `service_description` VARCHAR(500) NULL,
                `language_id` INT(11) DEFAULT 1 COMMENT 'OpenCart language_id',
                INDEX(`service_identifier`),
                PRIMARY KEY(`id`)
          ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
        ");
        $this->db->query("       
            INSERT IGNORE INTO `inpostoc3_services` (`id`,`service_identifier`,`service_name`,`service_description`) VALUES
            ( 1 , 'inpost_locker_standard' , '" .$this->language->get('text_service_name'). "' , '" .$this->language->get('text_service_description'). "');
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_parcel_templates` (
                `id` INT(11) UNIQUE AUTO_INCREMENT,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
                `service_id` INT(11) NOT NULL,
                `template_identifier` varchar(100) NULL COMMENT 'https://dokumentacja-inpost.atlassian.net/wiki/spaces/PL/pages/11731062/1.9.1+Rozmiary+i+us+ugi+dla+przesy+ek',
                `template_description` varchar(100) NULL,  
                `template_comments` varchar(2000) NULL,  
                `language_id` INT(11) DEFAULT 1 COMMENT 'OpenCart language_id',
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
                `template_description`,
                `template_comments`
            ) VALUES 
            (1,1,'small','" .$this->language->get('text_size_a'). "', NULL),
            (2,1,'medium','" .$this->language->get('text_size_b'). "', NULL),
            (3,1,'large', '" .$this->language->get('text_size_c'). "', NULL)
        ");
        
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `inpostoc3_shipment` (
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

    }
    
    public function uninstall() {
        //$this->log->write(print_r('model\extension\shipping\inpostoc3 uninstall before db uninstall', true));
        // do nothing, preserve data 
    }
}