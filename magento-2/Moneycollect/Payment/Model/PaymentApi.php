<?php


namespace Moneycollect\Payment\Model;



class PaymentApi extends \Magento\Framework\HTTP\Client\Curl
{

    /**
     * Moneycollect\Payment\view\base\requirejs-config.js:MoneyCollectSdk
     * Moneycollect\Payment\view\frontend\web\js\mcsdk_init.js:mode
     */

    const ENV_PRO = 'https://api.moneycollect.com/api/services/v1';
    const ENV_TEST = 'https://sandbox.moneycollect.com/api/services/v1';

    protected $_ssl = false;
    protected $_error = '';
    protected $_helper;
    protected $_logger;

    public function __construct(
        PaymentHelper $helper,
        PaymentLogger $logger,
        ?int $sslVersion = null
    ) {
        parent::__construct($sslVersion);
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_headers['Content-type'] = "application/json";
        $this->_headers['Authorization'] = $this->_helper->getPrKey();
    }

    public function addHeader($name, $value){
        parent::addHeader($name, $value);
    }

    public function request($uri,$params = '',$method = 'POST')
    {
        $this->_ch = curl_init();
        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);

        $url = $this->getEnv().$uri;
        $this->curlOption(CURLOPT_URL, $url);

        if ($method == 'POST') {
            $this->curlOption(CURLOPT_POST, 1);
            if($params == ''){
                $value = '';
            }else{
                $value = json_encode($params);
            }
            $this->curlOption(CURLOPT_POSTFIELDS, $value);
        } elseif ($method == "GET") {
            $this->curlOption(CURLOPT_HTTPGET, 1);
        } else {
            $this->curlOption(CURLOPT_CUSTOMREQUEST, $method);
        }

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if (count($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $k => $v) {
                $cookies[] = "{$k}={$v}";
            }
            $this->curlOption(CURLOPT_COOKIE, implode(";", $cookies));
        }

        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        if( !$this->_ssl ){
            $this->curlOption(CURLOPT_SSL_VERIFYPEER, false);
            $this->curlOption(CURLOPT_SSL_VERIFYHOST, false);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlOption(CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);

        if (count($this->_curlUserOptions)) {
            foreach ($this->_curlUserOptions as $k => $v) {
                $this->curlOption($k, $v);
            }
        }

        $this->_responseBody = curl_exec($this->_ch);

        $err = curl_errno($this->_ch);

        if ($err) {
            $this->_error = curl_error($this->_ch);
        }

        curl_close($this->_ch);

        return [
            'error' => $this->_error,
            'headers' => $this->_responseHeaders,
            'body' => json_decode($this->_responseBody,true)
        ];

    }


    protected function getEnv(){
        if( $this->_helper->getBasicConfigData('pay_model') === '0' ){
            return self::ENV_TEST;
        }else{
            return self::ENV_PRO;
        }

    }
}