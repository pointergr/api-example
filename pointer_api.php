<?php

class pointer_api {

    var $login_username = 'YOUR_API_USERNAME';
    var $login_password = 'YOUR_API_PASSWORD';
    var $key;

    function index() {
        $this->login();
        $this->domainCheck('pointerdemo.gr');
        $this->logout();
    }

    function request($xml) {
        $url = "https://www.pointer.gr/api";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, Array("Content-Type:text/xml", "testserver: 0")); // testserver 0=Normal registry, 1=test registry
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $xml); 
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        return curl_exec($curl);
    }

    function login($username = null, $password = null) {
    	
		if( ! is_null($username)) {
            $this->login_username = $username;    
        }

        if( ! is_null($password)) {
            $this->login_password = $password;    
        }

        $chksum = md5($this->login_username . $this->login_password . 'login');
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>
            <pointer>
                <login>
                    	<password>" . md5($this->login_password) . "</password>
                </login>
                <username>" . $this->login_username . "</username>
                <chksum>$chksum</chksum>
            </pointer>";

        $result = $this->request($xml);

        $xml = $this->_parseRequest($result);
        $tmp = $xml->xpath('/pointer/login/key');
        $this->key = (string) $tmp[0];
        
    }

    function logout() {
        $chksum = md5($this->login_username . $this->login_password . 'logout' . $this->key);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>
            <pointer>
                <logout>
                </logout>
                <key>" . $this->key . "</key>
                <username>" . $this->login_username . "</username>
                <chksum>" . $chksum . "</chksum>
            </pointer>";

        $result = $this->request($xml);
    }

    function domainCheck($domain, $tlds = NULL) {

        $default_tlds = array(".gr", ".eu", ".com", ".net");
        if (!is_array($tlds))
            $tlds = array();

        $tlds = array_merge($default_tlds, $tlds);
        $tld_xml = '';
        foreach ($tlds as $tld) {
            $tld_xml .= "<tld>" . $tld . "</tld>";
        }

        $chksum = md5($this->login_username . $this->login_password . 'domainCheck' . $this->key);
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <pointer>
                <domain-check>
                    <tlds>
                        " . $tld_xml . "
                    </tlds>
                    <domains>
                        <domain>" . $domain . "</domain>
                    </domains>
                </domain-check>
                <username>" . $this->login_username . "</username>
                <chksum>" . $chksum . "</chksum>
            </pointer>";

        $result = $this->request($xml);
        $xml = $this->_parseRequest($result);
        $xml_result = $xml->xpath('/pointer/login/key');
        $tmp = $xml->xpath('/pointer/domain-check/result/item');
        $arr = array();
        foreach($tmp as $tld_result) {
            $arr[(string) $tld_result->domain] = (string) $tld_result->available;
        }
        return $arr;
    }

    protected function _parseRequest($request_string) {
        try {
            $xml = new SimpleXMLElement($request_string);
            if (!is_a($xml, 'SimpleXMLElement'))
                throw new Exception("Invalid request", 106);
        } catch (Exception $e) {
            throw new Exception("Invalid request", 106);
        }
        return $xml;
    }

}
