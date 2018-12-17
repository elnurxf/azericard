<?php

namespace elnurxf\AzeriCard;

use elnurxf\AzeriCard\Exceptions\EmptyRequiredParametersException;
use elnurxf\AzeriCard\Exceptions\FailedTransactionException;
use elnurxf\AzeriCard\Exceptions\LogException;
use elnurxf\AzeriCard\Exceptions\NoConfigException;
use elnurxf\AzeriCard\Exceptions\NoParametersException;
use elnurxf\AzeriCard\Exceptions\WrongHashException;
use GuzzleHttp\Client;

/**
 * Class AzeriCard
 *
 * The main class for payment processing and reversal
 *
 * @package elnurxf\AzeriCard
 */

class AzeriCard
{
    const TIMEZONE = 'Asia/Baku';

    private $config             = [];
    private $callbackParameters = [];
    private $logPath            = null;
    private $testMode           = false;
    private $prodURL            = 'https://mpi.3dsecure.az/cgi-bin/cgi_link';
    private $testURL            = 'https://testmpi.3dsecure.az/cgi-bin/cgi_link';

    private $callbackRequiredParameters = [
        'TERMINAL',
        'TRTYPE',
        'ORDER',
        'AMOUNT',
        'CURRENCY',
        'ACTION',
        'RC',
        'APPROVAL',
        'RRN',
        'INTREF',
        'TIMESTAMP',
        'NONCE',
        'P_SIGN',
    ];

    private $paymentFormRequiredParameters = [
        'URL',
        'BUTTON_LABEL',
        'BUTTON_CLASS',
        'AMOUNT',
        'CURRENCY',
        'ORDER',
        'DESC',
        'MERCH_NAME',
        'MERCH_URL',
        'LANG',
        'TERMINAL',
        'EMAIL',
        'TRTYPE',
        'COUNTRY',
        'MERCH_GMT',
        'BACKREF',
        'KEY_FOR_SIGN',
    ];

    private $reversalFormRequiredParameters = [
        'URL',
        'BUTTON_LABEL',
        'BUTTON_CLASS',
        'AMOUNT',
        'CURRENCY',
        'ORDER',
        'RRN',
        'INT_REF',
        'TERMINAL',
        'TRTYPE',
        'KEY_FOR_SIGN',
    ];

    public function __construct($config = [], $testMode = false)
    {
        date_default_timezone_set(self::TIMEZONE);

        if (!is_array($config) || (is_array($config) && count($config) == 0)) {
            throw new NoConfigException;
        }

        $this->testMode      = $testMode;
        $this->config        = $config;
        $this->config['URL'] = $this->testMode ? $this->testURL : $this->prodURL;
    }

    public function setLogPath($path = null)
    {
        $this->logPath = $path;
    }

    public function setCallBackParameters($parameters = [])
    {
        $this->callbackParameters = $parameters;
    }

    public function getCallBackParameters()
    {
        return $this->callbackParameters;
    }

    public function paymentForm()
    {
        foreach ($this->paymentFormRequiredParameters as $key) {
            if (!array_key_exists($key, $this->config)) {
                throw new EmptyRequiredParametersException($key);
            }
        }

        $form_params              = $this->config;
        var_dump($this->config);
        $form_params['ORDER']     = str_pad($form_params['ORDER'], 6, '0', STR_PAD_LEFT);
        $form_params['OPER_TIME'] = gmdate("YmdHis");
        $form_params['NONCE']     = substr(md5(rand()), 0, 16);

        $to_sign = strlen($form_params['AMOUNT']) . $form_params['AMOUNT']
        . strlen($form_params['CURRENCY']) . $form_params['CURRENCY']
        . strlen($form_params['ORDER']) . $form_params['ORDER']
        . strlen($form_params['DESC']) . $form_params['DESC']
        . strlen($form_params['MERCH_NAME']) . $form_params['MERCH_NAME']
        . strlen($form_params['MERCH_URL']) . $form_params['MERCH_URL']
        . ($this->testMode ? '-' : '')
        . strlen($form_params['TERMINAL']) . $form_params['TERMINAL']
        . strlen($form_params['EMAIL']) . $form_params['EMAIL']
        . strlen($form_params['TRTYPE']) . $form_params['TRTYPE']
        . strlen($form_params['COUNTRY']) . $form_params['COUNTRY']
        . strlen($form_params['MERCH_GMT']) . $form_params['MERCH_GMT']
        . strlen($form_params['OPER_TIME']) . $form_params['OPER_TIME']
        . strlen($form_params['NONCE']) . $form_params['NONCE']
        . strlen($form_params['BACKREF']) . $form_params['BACKREF'];

        $form_params['P_SIGN'] = hash_hmac('sha1', $to_sign, hex2bin($this->config['KEY_FOR_SIGN']));

        $html = '<form action="' . $form_params['URL'] . '" name="form__azericard" method="POST">';
        $html .= '<input name="AMOUNT" value="' . $form_params['AMOUNT'] . '" type="hidden">';
        $html .= '<input name="CURRENCY" value="' . $form_params['CURRENCY'] . '" type="hidden">';
        $html .= '<input name="LANG" value="' . $form_params['LANG'] . '" type="hidden">';
        $html .= '<input name="ORDER" value="' . $form_params['ORDER'] . '" type="hidden">';
        $html .= '<input name="DESC" value="' . $form_params['DESC'] . '" type="hidden">';
        $html .= '<input name="MERCH_NAME" value="' . $form_params['MERCH_NAME'] . '" type="hidden">';
        $html .= '<input name="MERCH_URL" value="' . $form_params['MERCH_URL'] . '" type="hidden">';
        $html .= '<input name="TERMINAL" value="' . $form_params['TERMINAL'] . '" type="hidden">';
        $html .= '<input name="EMAIL" value="' . $form_params['EMAIL'] . '" type="hidden">';
        $html .= '<input name="TRTYPE" value="' . $form_params['TRTYPE'] . '" type="hidden">';
        $html .= '<input name="COUNTRY" value="' . $form_params['COUNTRY'] . '" type="hidden">';
        $html .= '<input name="MERCH_GMT" value="' . $form_params['MERCH_GMT'] . '" type="hidden">';
        $html .= '<input name="BACKREF" value="' . $form_params['BACKREF'] . '" type="hidden">';
        $html .= '<input name="TIMESTAMP" value="' . $form_params['OPER_TIME'] . '" type="hidden">';
        $html .= '<input name="NONCE" value="' . $form_params['NONCE'] . '" type="hidden">';
        $html .= '<input name="P_SIGN" value="' . $form_params['P_SIGN'] . '" type="hidden">';
        $html .= '<button type="submit" class="' . $form_params['BUTTON_CLASS'] . '">' . $form_params['BUTTON_LABEL'] . '</button>';
        $html .= '</form>';

        return $html;
    }

    public function reversalForm()
    {
        foreach ($this->reversalFormRequiredParameters as $key) {
            if (!array_key_exists($key, $this->config)) {
                throw new EmptyRequiredParametersException($key);
            }
        }

        $form_params              = $this->config;
        $form_params['ORDER']     = str_pad($form_params['ORDER'], 6, '0', STR_PAD_LEFT);
        $form_params['OPER_TIME'] = gmdate("YmdHis");
        $form_params['NONCE']     = substr(md5(rand()), 0, 16);

        $to_sign = strlen($form_params["ORDER"]) . $form_params["ORDER"]
        . strlen($form_params["AMOUNT"]) . $form_params["AMOUNT"]
        . strlen($form_params["CURRENCY"]) . $form_params["CURRENCY"]
        . strlen($form_params["RRN"]) . $form_params["RRN"]
        . strlen($form_params["INT_REF"]) . $form_params["INT_REF"]
        . strlen($form_params["TRTYPE"]) . $form_params["TRTYPE"]
        . strlen($form_params["TERMINAL"]) . $form_params["TERMINAL"]
        . strlen($form_params["TIMESTAMP"]) . $form_params["TIMESTAMP"]
        . strlen($form_params["NONCE"]) . $form_params["NONCE"];

        $form_params['P_SIGN'] = hash_hmac('sha1', $to_sign, hex2bin($this->config['KEY_FOR_SIGN']));

        $html = '<form action="' . $form_params['URL'] . '" name="form__azericard" method="POST">';
        $html .= '<input name="AMOUNT" value="' . $form_params['AMOUNT'] . '" type="hidden">';
        $html .= '<input name="CURRENCY" value="' . $form_params['CURRENCY'] . '" type="hidden">';
        $html .= '<input name="ORDER" value="' . $form_params['ORDER'] . '" type="hidden">';
        $html .= '<input name="RRN" value="' . $form_params['RRN'] . '" type="hidden">';
        $html .= '<input name="INT_REF" value="' . $form_params['INT_REF'] . '" type="hidden">';
        $html .= '<input name="TERMINAL" value="' . $form_params['TERMINAL'] . '" type="hidden">';
        $html .= '<input name="TRTYPE" value="' . $form_params['TRTYPE'] . '" type="hidden">';
        $html .= '<input name="TIMESTAMP" value="' . $form_params['OPER_TIME'] . '" type="hidden">';
        $html .= '<input name="NONCE" value="' . $form_params['NONCE'] . '" type="hidden">';
        $html .= '<input name="P_SIGN" value="' . $form_params['P_SIGN'] . '" type="hidden">';
        $html .= '<button type="submit" class="' . $form_params['BUTTON_CLASS'] . '">' . $form_params['BUTTON_LABEL'] . '</button>';
        $html .= '</form>';

        return $html;
    }

    public function handleCallback()
    {
        $parameters = $this->getCallBackParameters();

        if (!is_null($this->logPath)) {
            $this->logCallback();
        }

        if (!is_array($parameters) || (is_array($parameters) && count($parameters) == 0)) {
            throw new NoParametersException;
        }

        foreach ($this->callbackRequiredParameters as $key) {
            if (!array_key_exists($key, $parameters)) {
                throw new EmptyRequiredParametersException($key);
            }
        }

        $to_sign = strlen($parameters['TERMINAL']) . $parameters['TERMINAL']
        . strlen($parameters['TRTYPE']) . $parameters['TRTYPE']
        . strlen($parameters['ORDER']) . $parameters['ORDER']
        . strlen($parameters['AMOUNT']) . $parameters['AMOUNT']
        . strlen($parameters['CURRENCY']) . $parameters['CURRENCY']
        . strlen($parameters['ACTION']) . $parameters['ACTION']
        . strlen($parameters['RC']) . $parameters['RC']
        . strlen($parameters['APPROVAL']) . $parameters['APPROVAL']
        . strlen($parameters['RRN']) . $parameters['RRN']
        . strlen($parameters['INTREF']) . $parameters['INTREF']
        . strlen($parameters['TIMESTAMP']) . $parameters['TIMESTAMP']
        . strlen($parameters['NONCE']) . $parameters['NONCE'];

        $hash = hash_hmac('sha1', $to_sign, hex2bin($this->config['KEY_FOR_SIGN']));

        if ($parameters['ACTION'] != '0' || $parameters['RC'] != '00') {
            throw new FailedTransactionException($parameters['RC']);
        }

        if (strtoupper($hash) != strtoupper($parameters['P_SIGN'])) {
            throw new WrongHashException;
        }

        return true;
    }

    public function completeCheckout()
    {
        $parameters = $this->getCallBackParameters();

        if (!is_array($parameters) || (is_array($parameters) && count($parameters) == 0)) {
            throw new NoParametersException;
        }

        if ($parameters['ACTION'] != '0' || $parameters['RC'] != '00') {
            throw new FailedTransactionException($parameters['RC']);
        }

        $form_params              = [];
        $form_params['AMOUNT']    = $parameters['AMOUNT'];
        $form_params['CURRENCY']  = $parameters['CURRENCY'];
        $form_params['ORDER']     = $parameters['ORDER'];
        $form_params['RRN']       = $parameters['RRN'];
        $form_params['INT_REF']   = $parameters['INT_REF'];
        $form_params['TERMINAL']  = $parameters['TERMINAL'];
        $form_params['TRTYPE']    = '21';
        $form_params['TIMESTAMP'] = gmdate('YmdHis');
        $form_params['NONCE']     = substr(md5(rand()), 0, 16);

        $to_sign = strlen($parameters['ORDER']) . $parameters['ORDER']
        . strlen($parameters['AMOUNT']) . $parameters['AMOUNT']
        . strlen($parameters['CURRENCY']) . $parameters['CURRENCY']
        . strlen($parameters['RRN']) . $parameters['RRN']
        . strlen($parameters['INT_REF']) . $parameters['INT_REF']
        . strlen($parameters['TRTYPE']) . $parameters['TRTYPE']
        . strlen($parameters['TERMINAL']) . $parameters['TERMINAL']
        . strlen($parameters['TIMESTAMP']) . $parameters['TIMESTAMP']
        . strlen($parameters['NONCE']) . $parameters['NONCE'];

        $form_params['P_SIGN'] = hash_hmac('sha1', $to_sign, hex2bin($this->config['KEY_FOR_SIGN']));

        $client = new Client([
            'verify'  => false,
            'headers' => [
                'Accept'       => 'text/html',
                'Content-Type' => 'text/html',
            ],
        ]);

        $response = $client->request('POST', $this->config['URL'], [
            'form_params' => $form_params,
        ]);

        $reponseCode = $response->getBody()->getContents();

        if ($reponseCode == '0') {
            return true;
        } else {
            throw new FailedTransactionException($reponseCode);
        }

    }

    private function logCallback()
    {
        $logPath = rtrim($this->logPath, DIRECTORY_SEPARATOR);

        if (!is_dir($logPath)) {
            throw new LogException($logPath . ' is not a directory.');
        }

        $logDate = date('d-M-Y');
        $logTime = date('H-i-s');
        $logFile = 'AzeriCard-Callback-' . $logDate . '.log';
        $logText = '*** ' . $logTime . ' ' . $logDate . ' ***' . "\n";

        $parameters = $this->getCallBackParameters();

        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $logText .= $key . ': ' . $value . "\n";
            }
        } elseif (is_string($parameters)) {
            $logText .= $parameters . "\n";
        }

        $logText .= str_repeat('*', 28) . "\n\n";

        if (file_put_contents($logPath . DIRECTORY_SEPARATOR . $logFile, $logText, FILE_APPEND | LOCK_EX) === false) {
            throw new LogException('Can\'t write log file.');
        }
    }

}
