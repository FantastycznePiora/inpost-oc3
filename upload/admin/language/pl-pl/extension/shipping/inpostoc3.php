<?php
// Heading
$_['heading_title'] = 'InPost dla OpenCart3 - rozszerzenie dostawcze';
$_['heading_title_order_shipping'] = 'InPost: Wyślij zamówienie';
$_['heading_title_orders'] = 'Zamówienia';

// Text
$_['text_extension']   = 'Rozszerzenia';
$_['text_shipping']    = 'Dostawa';
$_['text_success']     = 'Sukces: zmodyfikowałeś ustawienia InPost dla OC3!';
$_['text_edit']        = 'Edytuj rozszerzenie InPost dla OC3';
$_['text_please_note'] = 'Uwaga: Integracja przez API może być włączona tylko, jeżeli Geo Strefa (Geo Zone) odbiorcy jest pokryta w całości przez API endpoint kuriera.  Np. należy sprawdzić, czy dana usługa pokrywa przesyłki międzynatodowe lub między określonymi strefami (w rozumieniu stref OC3). Użyj Sandbox API aby przetestować integrację! Ta wersja rozszerzenia wspiera przesyłkę krajową do paczkomatów w Polsce (wszystkie strefy OC3).';
$_['text_enabled']     = 'Włączone';
$_['text_disabled']    = 'Wyłączone';
$_['text_yes']         = 'Tak';
$_['text_no']          = 'Nie';
$_['text_inpost_locker_standard_name']              = 'Paczkomatowa standardowa'; 
$_['text_inpost_locker_standard_description']       = 'Przesyłka paczkomatowa standardowa'; 
$_['text_template_description']                     = 'Szablon paczki';
$_['text_template_description_small']               = 'Mała'; // Gabaryt A  
$_['text_template_description_medium']              = 'Średnia'; // Gabaryt B  
$_['text_template_description_large']               = 'Duża'; // Gabaryt C  
$_['text_template_description_size_small']          = 'Szablon paczki: mała (small)'; // Gabaryt A  
$_['text_template_description_size_medium']         = 'Szablon paczki: średnia (medium)'; // Gabaryt B  
$_['text_template_description_size_large']          = 'Szablon paczki: duża (large)'; // Gabaryt C  
$_['text_selected_target_point']                    = 'Wybrany punkt docelowy';
$_['text_selected_sending_method']                  = 'Wybrana metoda nadania';
$_['text_selected_sending_point']                   = 'Wybrany punkt nadawczy';
$_['text_selected_sending_address']                 = 'Wybrany adres nadawcy';
$_['text_service']                                  = 'Usługa';
$_['text_service_identifier']                       = 'Identyfikator usługi';
$_['text_sending_method']                           = 'Metoda nadania'; // 'Sposób nadania'
$_['text_sending_method_parcel_locker']             = 'Nadam przesyłkę w Paczkomacie'; // 'Nadam przesyłkę w Paczkomacie'
$_['text_shipments']                                = 'Przesyłki';
$_['text_send_from']                                = 'Nadanie z'; // 'Nadanie z'
$_['text_sending_method_details']                   = 'Szczegóły usługi i metody nadawczej';
$_['text_send_to']                                  = 'Wyślij do';
$_['text_shipment_number']                          = 'Numer przesyłki (wewn.)';
$_['text_shipment_tracking_number']                 = 'Numer śledzenia przesyłki';
$_['text_parcel_number']                            = 'Numer paczki (wewn.)';
$_['text_parcel_tracking_number']                   = 'Numer śledzenia paczki';
$_['text_name']                                     = 'Nazwa';
$_['text_company_name']                             = 'Nazwa firmy';
$_['text_first_name']                               = 'Imię';
$_['text_last_name']                                = 'Nazwisko';
$_['text_email']                                    = 'Email';
$_['text_phone']                                    = 'Telefon';
$_['text_addr_street']                              = 'Ulica';
$_['text_addr_building_number']                     = 'Numer budynku';
$_['text_addr_line_1']                              = 'Adres linia 1';
$_['text_addr_line_2']                              = 'Adres linia 2';
$_['text_addr_city']                                = 'Miasto';
$_['text_addr_post_code']                           = 'Kod pocztowy';
$_['text_addr_country_code']                        = 'Kraj';
$_['text_addr_new']                                 = '-- Wrpowadź nowego nadawcę';
$_['text_click_to_select']                          = 'Kliknij aby wybrać...';
$_['text_order']                                    = 'Zamówienie';
$_['text_shipment_saved']                           = 'Przesyłka zapisana!';

// Entry
$_['entry_status']                                  = 'Status';
$_['entry_service_status']                          = 'Status usługi';
$_['entry_sort_order']                              = 'Kolejność sortowania';  
$_['entry_rate']                                    = 'Stawki';
$_['entry_tax_class']                               = 'Klasa podatku (OC3)';
//$_['entry_geo_zone']   = 'Geo Zone';
$_['entry_please_note']                             = 'UWAGA!';   
$_['entry_use_api']                                 = 'Czy włączyć integrację z InPost API?';
$_['entry_use_sandbox_api']                         = 'Użyj Sandbox API?';
$_['entry_sandbox_api_endpoint']                    = 'Sandbox API Adres';
$_['entry_sandbox_api_token']                       = 'Sandbox API Token';
$_['entry_sandbox_api_org_id']                      = 'Sandbox API ID Organizacji';
$_['entry_api_endpoint']                            = 'API Adres';
$_['entry_api_token']                               = 'API Token';
$_['entry_api_org_id']                              = 'API ID Organizacji';
$_['entry_sending_method_parcel_locker']            = 'Nadanie w Paczkomacie';  // 
$_['entry_sendfrom']                                = 'Nadanie z kraju';
$_['entry_name']                                    = '* Wprowadź \'Nazwa\' w formularzu';
$_['entry_show_in_checkout']                        = 'Pokaż tę metodę wysyłki klientowi przy kasie?';

// Help
$_['help_rate']                                     = 'Przykład: small:10.561,medium:11.3740,large:12.5935 (angielskie nazwy szablonów są domyślne, zachowaj je). Rozmiar szablonu:Koszt.Miejscadziesiętne,Rozmiar szablonu:Koszt.Miejscadziesiętne, etc...';
$_['help_use_api']                                  = 'Pozwoli na tworzenie etykiet i pobieranie ich automatycznie z poziomu OpenCart. Komunikacja via InPost API.';
$_['help_use_sandbox_api']                          = 'Jeżeli zaznaczysz TAK, będziesz używac Sandbox API.';
$_['help_parcel_template_weight_class']             = 'Klasa ciężaru dla szablonu paczki InPost musi być wyrażona w kilogramach. Jeśli nie ma takiej klasy, dodaj ją w ustawieniach OC3., zanim zacnziesz używac rozszerzenia InPost dla OC3.';
$_['help_parcel_template_length_class']             = 'Klasa długości dla szablonu paczki InPost musi być wyrażona w milimetrach. Jeśli nie ma takiej klasy, dodaj ją w ustawieniach OC3., zanim zacnziesz używac rozszerzenia InPost dla OC3.';
$_['help_sendfrom']                                 = 'Wybierz kod ISO alfa 3 dla kraju, z którego przesyłki sa nadawane do odbiorców w tej Geo Streie (OC3 Geo Zone).';
$_['help__show_in_checkout']                        = 'Ustala czy ta opcja pojawi się na liście metod wysyłki podczas zamówienia.';

// Button
$_['button_shipping_print']                         = 'Pobierz etykietę z InPost API. Po pobraniu etykiety nie można edytować przesyłki. Zapisuje bieżący stan edycji przed pobraniem etykiety.';
$_['button_shipping_save']                          = 'Zapisz przesyłkę. Możesz ją edytowac ponownie.';

// Error
$_['error_permission']                              = 'Błąd: Nie masz uprawnien do edycji InPost dla OC3!';
$_['error_insufficient_shipment_data']              = 'Niewystarczające dane przesyłki!!';
$_['error_no_service']                              = 'Brak ID usługi!';
$_['error_no_sending_method']                       = 'Brak ID metody nadania!';
$_['error_no_sender']                               = 'Brak ID nadawcy!';
$_['error_curl_not_installed']                      = 'cURL nie jest zainstalowany na serwerze!';
$_['error_shipment_already_created]']               = 'Przykro mi, nie zapisano zmian. Przesyłka została w międzyczasie zapisana, a etykieta wygenerowana przez InPost API przez kogoś innego.';