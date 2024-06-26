<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 *
 * @package     Paysley
 * @copyright   Copyright (c) 2020 Paysley
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
namespace Paysley\Paysley\Helper;

class Curl extends \Magento\Framework\App\Helper\AbstractHelper
{
    private $http;
    private $logger;
    private $curlFactory;
    protected $response;

    /**
     * [__construct description]
     * @param \Magento\Framework\App\Helper\Context       $context
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Paysley\Paysley\Helper\Logger                $logger
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Paysley\Paysley\Helper\Logger $logger,
        \Magento\Framework\App\Response\Http $response
    ) {
        parent::__construct($context);
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->response = $response;
    }

    /**
     * get a response from the gateway
     * @param  boolean $isJsonDecoded
     * @return string | boolean
     */
    public function getResponse($isJsonDecoded = false)
    {
        try {
            $response = $this->http->read();
            $responseCode = $this->response->getHttpResponseCode($response);
            list($headers, $responseBody) = explode("\r\n\r\n", $response, 2);
            $this->http->close();
            if ($responseCode == 200 || $responseCode == 202 || $responseCode == 400) {
                $this->logger->info(
                    'response from gateway : '.
                    json_encode($responseBody)
                );
                if ($isJsonDecoded) {
                    return json_decode($responseBody, true);
                }
                return $responseBody;
    
            } elseif ($responseCode == 422) {
                return json_decode($responseBody, true);
            }
            $this->logger->error('Error response from gateway: ' . $responseBody);
            return [];
        } catch (\Exception $e) {
            // Exception occurred while reading response
            $this->logger->error('Exception occurred while reading response: ' . $e->getMessage());
            return [];
        }
      
    }

    /**
     * send request to the gateway
     *
     * @param string $url
     * @param string $request
     * @param boolean $isJsonDecoded
     * @return string | boolean
     */
    public function sendRequest($url, $request, $accessKey, $method = 'GET', $isJsonDecoded = true)
    { 
        $headers = ["Authorization:Bearer ".$accessKey];
        $this->http = $this->curlFactory->create();
        $this->http->setConfig(['verifypeer' => false]);
        if ($method == 'POST') {
            array_push($headers, "content-type: application/json");
            $this->http->write(\Laminas\Http\Request::METHOD_POST, $url, $http_ver = '1.1', $headers, json_encode($request));
        } elseif ($method == 'PUT') {
            array_push($headers, "content-type: application/json");
            $this->http->write(\Laminas\Http\Request::METHOD_PUT, $url, $http_ver = '1.1', $headers, json_encode($request));
        } else {
            $this->http->write(\Laminas\Http\Request::METHOD_GET, $url, $http_ver = '1.1', $headers, json_encode($request));
        }
        return $this->getResponse($isJsonDecoded);
    }
}
