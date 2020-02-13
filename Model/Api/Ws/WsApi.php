<?php
/**
 * PayZen V2-Payment Module version 2.4.4 for Magento 2.x. Support contact : support@payzen.eu.
 *
 * @category  Payment
 * @package   Payzen
 * @author    Lyra Network (http://www.lyra-network.com/)
 * @copyright 2014-2019 Lyra Network and contributors
 * @license   
 */

namespace Lyranetwork\Payzen\Model\Api\Ws;

class WsApi extends \SoapClient
{
    const HEADER_NAMESPACE = 'http://v5.ws.vads.lyra.com/Header/';
    const DATE_FORMAT = 'Y-m-d\TH:i:s\Z';
    const TIMEOUT = 30; // In seconds.

    /**
     * @var array $classes The defined classes
     */
    private static $classes = array(
        'cancelCapturedPayment',
        'commonRequest',
        'queryRequest',
        'cancelCapturedPaymentResponse',
        'cancelCapturedPaymentResult',
        'commonResponse',
        'wsResponse',
        'capturePayment',
        'settlementRequest',
        'capturePaymentResponse',
        'capturePaymentResult',
        'createTokenByIban',
        'ibanRequest',
        'customerRequest',
        'billingDetailsRequest',
        'shippingDetailsRequest',
        'extraDetailsRequest',
        'createTokenByIbanResponse',
        'createTokenByIbanResult',
        'paymentResponse',
        'orderResponse',
        'extInfo',
        'cardResponse',
        'authorizationResponse',
        'captureResponse',
        'customerResponse',
        'billingDetailsResponse',
        'shippingDetailsResponse',
        'extraDetailsResponse',
        'markResponse',
        'threeDSResponse',
        'authenticationRequestData',
        'authenticationResultData',
        'extraResponse',
        'subscriptionResponse',
        'fraudManagementResponse',
        'riskControl',
        'riskAnalysis',
        'riskAssessments',
        'shoppingCartResponse',
        'cartItemInfo',
        'reactivateToken',
        'reactivateTokenResponse',
        'reactivateTokenResult',
        'duplicatePayment',
        'paymentRequest',
        'orderRequest',
        'duplicatePaymentResponse',
        'duplicatePaymentResult',
        'cancelPayment',
        'cancelPaymentResponse',
        'cancelPaymentResult',
        'cancelRefund',
        'cancelRefundResponse',
        'cancelRefundResult',
        'checkThreeDSAuthentication',
        'threeDSRequest',
        'mpiExtensionRequest',
        'checkThreeDSAuthenticationResponse',
        'checkThreeDSAuthenticationResult',
        'updatePayment',
        'updatePaymentResponse',
        'updatePaymentResult',
        'updatePaymentDetails',
        'shoppingCartRequest',
        'updatePaymentDetailsResponse',
        'updatePaymentDetailsResult',
        'getPaymentDetails',
        'extendedResponseRequest',
        'getPaymentDetailsResponse',
        'getPaymentDetailsResult',
        'tokenResponse',
        'updateToken',
        'cardRequest',
        'tokenRequest',
        'updateTokenResponse',
        'updateTokenResult',
        'updateRefund',
        'updateRefundResponse',
        'updateRefundResult',
        'cancelSubscription',
        'cancelSubscriptionResponse',
        'cancelSubscriptionResult',
        'refundPayment',
        'refundPaymentResponse',
        'refundPaymentResult',
        'createTokenFromTransaction',
        'createTokenFromTransactionResponse',
        'createTokenFromTransactionResult',
        'verifyThreeDSEnrollment',
        'techRequest',
        'verifyThreeDSEnrollmentResponse',
        'verifyThreeDSEnrollmentResult',
        'validatePayment',
        'validatePaymentResponse',
        'validatePaymentResult',
        'getPaymentUuid',
        'legacyTransactionKeyRequest',
        'getPaymentUuidResponse',
        'legacyTransactionKeyResult',
        'createPayment',
        'createPaymentResponse',
        'createPaymentResult',
        'createSubscription',
        'subscriptionRequest',
        'createSubscriptionResponse',
        'createSubscriptionResult',
        'getSubscriptionDetails',
        'getSubscriptionDetailsResponse',
        'getSubscriptionDetailsResult',
        'updateSubscription',
        'updateSubscriptionResponse',
        'updateSubscriptionResult',
        'cancelToken',
        'cancelTokenResponse',
        'cancelTokenResult',
        'createToken',
        'createTokenResponse',
        'createTokenResult',
        'findPayments',
        'findPaymentsResponse',
        'findPaymentsResult',
        'transactionItem',
        'getTokenDetails',
        'getTokenDetailsResponse',
        'getTokenDetailsResult'
    );

    private $shopId;
    private $mode;
    private $key;

    /**
     * @param string $wsdl The WSDL file URI to use.
     * @param array $options An array of config values.
     */
    public function __construct($wsdl, array $options = array())
    {
        foreach (self::$classes as $class) {
            if (!isset($options['classmap'][$class])) {
                $options['classmap'][$class] = __NAMESPACE__ . '\\' . ucfirst($class);
            }
        }

        $ssl = array();
        if (isset($options['sni.enabled']) && $options['sni.enabled']) {
            $url = parse_url($wsdl);
            $ssl = array('SNI_enabled' => true, 'SNI_server_name' => $url['host']);

            unset($options['sni.enabled']);
        }

        $options = array_merge(array(
            'trace' => true,
            'exceptions' => true,
            'soapaction' => '',
            'cache_wsdl' => WSDL_CACHE_NONE,
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            'connection_timeout' => self::TIMEOUT,
            'encoding' => 'UTF-8',
            'soap_version' => SOAP_1_2,
            'stream_context' => stream_context_create(
                array('ssl' => $ssl, 'http' => array('user_agent' => 'PHPSoapClient'))
            )
        ), $options);

        parent::__construct($wsdl, $options);
    }

    public function init($shopId, $mode, $keyTest, $keyProd)
    {
        $this->mode = $mode;
        $this->shopId = $shopId;
        $this->key = ($mode === 'PRODUCTION') ? $keyProd : $keyTest;
    }

    public function getAuthToken($data1, $data2)
    {
        $authToken = base64_encode(hash_hmac('sha256', $data1 . $data2, $this->key, true));
        return $authToken;
    }

    public function genUuid()
    {
        if ($data = $this->genRandomBytes()) {
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Set version to 100.
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Set bits 6 & 7 to 10.

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } else {
            return sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            );
        }
    }

    private function genRandomBytes()
    {
        if (function_exists('random_bytes')) {
            // PHP 7 code.
            try {
                return random_bytes(16);
            } catch(\Exception $e) {
                // Try something else below.
            }
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            // PHP 5.3 code but needs OpenSSL library.
            return openssl_random_pseudo_bytes(16);
        }

        return null;
    }

    public function setHeaders()
    {
        $this->__setSoapHeaders(null);

        $requestId = $this->genUuid();
        $timestamp = gmdate(self::DATE_FORMAT);
        $authToken = $this->getAuthToken($requestId, $timestamp);

        // Create headers for shopId, requestId, timestamp, mode and authToken.
        $headers = array();

        $headers[] = new \SOAPHeader(self::HEADER_NAMESPACE, 'shopId', $this->shopId);
        $headers[] = new \SOAPHeader(self::HEADER_NAMESPACE, 'requestId', $requestId);
        $headers[] = new \SOAPHeader(self::HEADER_NAMESPACE, 'timestamp', $timestamp);
        $headers[] = new \SOAPHeader(self::HEADER_NAMESPACE, 'mode', $this->mode);
        $headers[] = new \SOAPHeader(self::HEADER_NAMESPACE, 'authToken', $authToken);

        // Set headers to soap client.
        $this->__setSoapHeaders($headers);

        return $requestId;
    }

    public function getJsessionId()
    {
        // Retrieve header of the last response.
        $header = $this->__getLastResponseHeaders();

        $matches = array();
        if (!preg_match('#JSESSIONID=([A-Za-z0-9\._]+)#', $header, $matches)) {
            // No session created by gateway.
            throw new \SoapFault('PayzenSID', 'No session ID returned by gateway.' . $header);
        }

        return $matches[1];
    }

    public function setJsessionId($sid)
    {
        $this->__setCookie('JSESSIONID', $sid);
    }

    public function checkAuthenticity()
    {
        // Retrieve SOAP header to check response authenticity.
        $dom = new \DOMDocument();
        $dom->loadXML($this->__getLastResponse(), LIBXML_NOWARNING);

        $path = new \DOMXPath($dom);
        $xmlHeaders = $path->query('//*[local-name()="Header"]/*');

        $headers = array();
        foreach ($xmlHeaders as $xmlHeader) {
            $headers[$xmlHeader->nodeName] = $xmlHeader->nodeValue;
        }

        if ($this->shopId !== $headers['shopId']) {
            throw new \UnexpectedValueException("Inconsistent returned shopId {$headers['shopId']}.", -1);
        }

        if ($this->mode !== $headers['mode']) {
            throw new \UnexpectedValueException("Inconsistent returned mode {$headers['mode']}.", -1);
        }

        $authToken = $this->getAuthToken($headers['timestamp'], $headers['requestId']);
        if ($authToken !== $headers['authToken']) {
            throw new \UnexpectedValueException('Authentication failed.', -1);
        }
    }

    public function checkResult(CommonResponse $commonResponse, array $expectedStatuses = array())
    {
        if ($commonResponse->getResponseCode() !== 0) {
            throw new \UnexpectedValueException(
                $commonResponse->getResponseCodeDetail(),
                $commonResponse->getResponseCode()
            );
        }

        if (!empty($expectedStatuses) && !in_array($commonResponse->getTransactionStatusLabel(), $expectedStatuses)) {
            throw new \UnexpectedValueException(
                "Unexpected transaction status returned ({$commonResponse->getTransactionStatusLabel()})."
            );
        }
    }

    /**
     * @param CancelCapturedPayment $parameters
     * @return CancelCapturedPaymentResponse
     */
    public function cancelCapturedPayment(CancelCapturedPayment $parameters)
    {
        return $this->__soapCall('cancelCapturedPayment', array($parameters));
    }

    /**
     * @param CapturePayment $parameters
     * @return CapturePaymentResponse
     */
    public function capturePayment(CapturePayment $parameters)
    {
        return $this->__soapCall('capturePayment', array($parameters));
    }

    /**
     * @param CreateTokenByIban $parameters
     * @return CreateTokenByIbanResponse
     */
    public function createTokenByIban(CreateTokenByIban $parameters)
    {
        return $this->__soapCall('createTokenByIban', array($parameters));
    }

    /**
     * @param ReactivateToken $parameters
     * @return ReactivateTokenResponse
     */
    public function reactivateToken(ReactivateToken $parameters)
    {
        return $this->__soapCall('reactivateToken', array($parameters));
    }

    /**
     * @param DuplicatePayment $parameters
     * @return DuplicatePaymentResponse
     */
    public function duplicatePayment(DuplicatePayment $parameters)
    {
        return $this->__soapCall('duplicatePayment', array($parameters));
    }

    /**
     * @param CancelPayment $parameters
     * @return CancelPaymentResponse
     */
    public function cancelPayment(CancelPayment $parameters)
    {
        return $this->__soapCall('cancelPayment', array($parameters));
    }

    /**
     * @param CancelRefund $parameters
     * @return CancelRefundResponse
     */
    public function cancelRefund(CancelRefund $parameters)
    {
        return $this->__soapCall('cancelRefund', array($parameters));
    }

    /**
     * @param CheckThreeDSAuthentication $parameters
     * @return CheckThreeDSAuthenticationResponse
     */
    public function checkThreeDSAuthentication(CheckThreeDSAuthentication $parameters)
    {
        return $this->__soapCall('checkThreeDSAuthentication', array($parameters));
    }

    /**
     * @param UpdatePayment $parameters
     * @return UpdatePaymentResponse
     */
    public function updatePayment(UpdatePayment $parameters)
    {
        return $this->__soapCall('updatePayment', array($parameters));
    }

    /**
     * @param UpdatePaymentDetails $parameters
     * @return UpdatePaymentDetailsResponse
     */
    public function updatePaymentDetails(UpdatePaymentDetails $parameters)
    {
        return $this->__soapCall('updatePaymentDetails', array($parameters));
    }

    /**
     * @param GetPaymentDetails $parameters
     * @return GetPaymentDetailsResponse
     */
    public function getPaymentDetails(GetPaymentDetails $parameters)
    {
        return $this->__soapCall('getPaymentDetails', array($parameters));
    }

    /**
     * @param UpdateToken $parameters
     * @return UpdateTokenResponse
     */
    public function updateToken(UpdateToken $parameters)
    {
        return $this->__soapCall('updateToken', array($parameters));
    }

    /**
     * @param UpdateRefund $parameters
     * @return UpdateRefundResponse
     */
    public function updateRefund(UpdateRefund $parameters)
    {
        return $this->__soapCall('updateRefund', array($parameters));
    }

    /**
     * @param CancelSubscription $parameters
     * @return CancelSubscriptionResponse
     */
    public function cancelSubscription(CancelSubscription $parameters)
    {
        return $this->__soapCall('cancelSubscription', array($parameters));
    }

    /**
     * @param RefundPayment $parameters
     * @return RefundPaymentResponse
     */
    public function refundPayment(RefundPayment $parameters)
    {
        return $this->__soapCall('refundPayment', array($parameters));
    }

    /**
     * @param CreateTokenFromTransaction $parameters
     * @return CreateTokenFromTransactionResponse
     */
    public function createTokenFromTransaction(CreateTokenFromTransaction $parameters)
    {
        return $this->__soapCall('createTokenFromTransaction', array($parameters));
    }

    /**
     * @param VerifyThreeDSEnrollment $parameters
     * @return VerifyThreeDSEnrollmentResponse
     */
    public function verifyThreeDSEnrollment(VerifyThreeDSEnrollment $parameters)
    {
        return $this->__soapCall('verifyThreeDSEnrollment', array($parameters));
    }

    /**
     * @param ValidatePayment $parameters
     * @return ValidatePaymentResponse
     */
    public function validatePayment(ValidatePayment $parameters)
    {
        return $this->__soapCall('validatePayment', array($parameters));
    }

    /**
     * @param GetPaymentUuid $parameters
     * @return GetPaymentUuidResponse
     */
    public function getPaymentUuid(GetPaymentUuid $parameters)
    {
        return $this->__soapCall('getPaymentUuid', array($parameters));
    }

    /**
     * @param CreatePayment $parameters
     * @return CreatePaymentResponse
     */
    public function createPayment(CreatePayment $parameters)
    {
        return $this->__soapCall('createPayment', array($parameters));
    }

    /**
     * @param CreateSubscription $parameters
     * @return CreateSubscriptionResponse
     */
    public function createSubscription(CreateSubscription $parameters)
    {
        return $this->__soapCall('createSubscription', array($parameters));
    }

    /**
     * @param GetSubscriptionDetails $parameters
     * @return GetSubscriptionDetailsResponse
     */
    public function getSubscriptionDetails(GetSubscriptionDetails $parameters)
    {
        return $this->__soapCall('getSubscriptionDetails', array($parameters));
    }

    /**
     * @param UpdateSubscription $parameters
     * @return UpdateSubscriptionResponse
     */
    public function updateSubscription(UpdateSubscription $parameters)
    {
        return $this->__soapCall('updateSubscription', array($parameters));
    }

    /**
     * @param CancelToken $parameters
     * @return CancelTokenResponse
     */
    public function cancelToken(CancelToken $parameters)
    {
        return $this->__soapCall('cancelToken', array($parameters));
    }

    /**
     * @param CreateToken $parameters
     * @return CreateTokenResponse
     */
    public function createToken(CreateToken $parameters)
    {
        return $this->__soapCall('createToken', array($parameters));
    }

    /**
     * @param FindPayments $parameters
     * @return FindPaymentsResponse
     */
    public function findPayments(FindPayments $parameters)
    {
        return $this->__soapCall('findPayments', array($parameters));
    }

    /**
     * @param GetTokenDetails $parameters
     * @return GetTokenDetailsResponse
     */
    public function getTokenDetails(GetTokenDetails $parameters)
    {
        return $this->__soapCall('getTokenDetails', array($parameters));
    }
}
