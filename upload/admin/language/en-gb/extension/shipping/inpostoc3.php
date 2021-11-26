<?php
// Heading
$_['heading_title'] = 'InPost for OpenCart3 Shipping Extension';
$_['heading_title_order_shipping'] = 'InPost: Ship Order';
$_['heading_title_orders'] = 'Orders';

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
$_['text_inpost_locker_standard_name']              = 'ParcelLocker Standard'; //'Paczkomatowa standardowa'
$_['text_inpost_locker_standard_description']       = 'ParcelLocker standard shipment'; // 'Przesyłka paczkomatowa standardowa'
$_['text_template_description']                     = 'Parcel template';
$_['text_template_description_small']               = 'Small'; // Gabaryt A  
$_['text_template_description_medium']              = 'Medium'; // Gabaryt B  
$_['text_template_description_large']               = 'Large'; // Gabaryt C  
$_['text_template_description_size_small']          = 'Parcel template size: small'; // Gabaryt A  
$_['text_template_description_size_medium']         = 'Parcel template size: medium'; // Gabaryt B  
$_['text_template_description_size_large']          = 'Parcel template size: large'; // Gabaryt C  
$_['text_selected_target_point']                    = 'Selected target point';
$_['text_selected_sending_method']                  = 'Selected sending method';
$_['text_selected_sending_point']                   = 'Selected dropoff point';
$_['text_selected_sending_address']                 = 'Selected sending address';
$_['text_service']                                  = 'Service';
$_['text_service_identifier']                       = 'Service identifier';
$_['text_sending_method']                           = 'Sending method'; // 'Sposób nadania'
$_['text_sending_method_parcel_locker']             = 'I will post a package at parcel locker'; // 'Nadam przesyłkę w Paczkomacie'
$_['text_shipments']                                = 'Shipments';
$_['text_send_from']                                = 'Send from'; // 'Nadanie z'
$_['text_sending_method_details']                   = 'Sending method details';
$_['text_send_to']                                  = 'Send to';
$_['text_shipment_number']                          = 'Shipment number';
$_['text_shipment_tracking_number']                 = 'Shipment tracking number';
$_['text_parcel_number']                            = 'Parcel number';
$_['text_parcel_tracking_number']                   = 'Parcel tracking number';
$_['text_name']                                     = 'Name';
$_['text_company_name']                             = 'Company name';
$_['text_first_name']                               = 'First name';
$_['text_last_name']                                = 'Last name';
$_['text_email']                                    = 'Email';
$_['text_phone']                                    = 'Phone';
$_['text_addr_street']                              = 'Street';
$_['text_addr_building_number']                     = 'Building number';
$_['text_addr_line_1']                              = 'Address line 1';
$_['text_addr_line_2']                              = 'Address line 2';
$_['text_addr_city']                                = 'City';
$_['text_addr_post_code']                           = 'Post code';
$_['text_addr_country_code']                        = 'Country';
$_['text_addr_new']                                 = '-- Enter new sender details';
$_['text_click_to_select']                          = 'Click to select...';
$_['text_order']                                    = 'Order';

// Entry
$_['entry_status']                                  = 'Status';
$_['entry_service_status']                          = 'Service Status';
$_['entry_sort_order']                              = 'Sort Order';  
$_['entry_rate']                                    = 'Rates';
$_['entry_tax_class']                               = 'Tax Class';
//$_['entry_geo_zone']   = 'Geo Zone';
$_['entry_please_note']                             = 'PLEASE NOTE!';   
$_['entry_use_api']                                 = 'Enable automation via InPost API?';
$_['entry_use_sandbox_api']                         = 'Use Sandbox API?';
$_['entry_sandbox_api_endpoint']                    = 'Sandbox API address';
$_['entry_sandbox_api_token']                       = 'Sandbox API Token';
$_['entry_sandbox_api_org_id']                      = 'Sandbox API organization id';
$_['entry_api_endpoint']                            = 'API address';
$_['entry_api_token']                               = 'API Token';
$_['entry_api_org_id']                              = 'API organization id';
$_['entry_sending_method_parcel_locker']            = 'Post at parcel locker';  // 'Nadanie w Paczkomacie'
$_['entry_sendfrom']                                = 'Sending from country';
$_['entry_name']                                    = '* Enter \'Name\' in the form';

// Help
$_['help_rate']                                     = 'Example: small:10.00,medium:12.00 Parcel template size:Cost,Parcel template size:Cost, etc...';
$_['help_use_api']                                  = 'It will create shipments at InPost automatically and will allow to download PDF labels via OpenCart';
$_['help_use_sandbox_api']                          = 'If selected, Sandbox API settings will be used';
$_['help_parcel_template_weight_class']             = 'Weight class for InPost parcel templates must be in kilograms. If not available, create weight class before using this extension.';
$_['help_parcel_template_length_class']             = 'Length class for InPost parcel templates must be in milimeters. If not available, create length class before using this extension.';
$_['help_sendfrom']                                 = 'Select ISO alpha 3 code for country, from which shipments are sent to receivers in this Geo Zone';

// Button
$_['button_shipping_print']                         = 'Get Label from InPost. Once done, shipment cannot be edited.';
$_['button_shipping_save']                          = 'Save draft. Shipment can be edited later.';

// Error
$_['error_permission']                              = 'Warning: You do not have permission to modify InPosst OC3 shipping!';
$_['error_insufficient_shipment_data']              = 'Insufficient shipment data!';
$_['error_no_service']                              = 'No service id!';
$_['error_no_sending_method']                       = 'No sending method id!';
$_['error_no_sender']                               = 'No sender id!';