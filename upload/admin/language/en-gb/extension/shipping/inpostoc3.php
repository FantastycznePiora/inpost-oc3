<?php
// Heading
$_['heading_title'] = 'InPost for OpenCart3 Shipping Extension';

// Text
$_['text_extension']   = 'Extensions';
$_['text_shipping']    = 'Shipping';
$_['text_success']     = 'Success: You have modified Inpost OC3 shipping!';
$_['text_edit']        = 'Edit Inpost OC3 Shipping';
$_['text_please_note'] = 'Be advised: API integration for Geo Zone should be enabled only if receiver Geo Zone can be handled completely by specific InPost API endpoint - e.g. check if it is possible to use service for international shipment or between zones. Use Sandbox API to test it first! Right now extension handles Parcel Locker shipments in Poland (all zones).';
$_['text_enabled']     = 'Enabled';
$_['text_disabled']    = 'Disabled';
$_['text_yes']         = 'Yes';
$_['text_no']          = 'No';
$_['text_service_name']     = 'ParcelLocker Standard'; //'Paczkomatowa standardowa'
$_['text_service_description']     = 'ParcelLocker standard shipment'; // 'Przesyłka paczkomatowa standardowa'
$_['text_size_a']      = 'Size A'; // Gabaryt A  
$_['text_size_b']      = 'Size B'; // Gabaryt B  
$_['text_size_c']      = 'Size C'; // Gabaryt C  

// Entry
$_['entry_status']               = 'Status';
$_['entry_status_parcel_locker'] = 'Parcel Locker Service Status';
$_['entry_sort_order']           = 'Sort Order';  
$_['entry_rate']                 = 'Rates';
$_['entry_tax_class']  = 'Tax Class';
//$_['entry_geo_zone']   = 'Geo Zone';
$_['entry_please_note']          = 'PLEASE NOTE!';   
$_['entry_use_api']              = 'Enable automation via InPost API?';
$_['entry_use_sandbox_api']      = 'Use Sandbox API?';
$_['entry_sandbox_api_endpoint'] = 'Sandbox API address';
$_['entry_sandbox_api_token']    = 'Sandbox API Token';
$_['entry_api_endpoint']         = 'API address';
$_['entry_api_token']            = 'API Token';

// Help
$_['help_rate']        = 'Example: 5:10.00,7:12.00 Weight:Cost,Weight:Cost, etc..';
$_['help_use_api']       = 'It will create shipments at InPost automatically and will allow to download PDF labels via OpenCart';
$_['help_use_sandbox_api']       = 'If selected, Sandbox API settings will be used';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify InPosst OC3 shipping!';