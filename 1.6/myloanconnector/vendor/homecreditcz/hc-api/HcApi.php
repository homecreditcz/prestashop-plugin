<?php
/**
 * Created by PhpStorm.
 * User: xtruhl05
 * Date: 21.11.19
 * Time: 18:57
 */

namespace HcApi;

use Exception;
use RuntimeException;

/**
 * Class HcApi
 * @package HcApi
 */
class HcApi
{

    /**
     * Class variables
     */
    private $apiLogin;
    private $apiPassword;
    private $baseURL;
    private $token = null;

    /**
     * HcApi constructor.
     * @param array $credentials array(username, password)
     * @param string $URL API url endpoint.
     * @param string $token
     */
    public function __construct($credentials, $URL, $token = null)
    {
        $this->apiLogin = $credentials[0];
        $this->apiPassword = $credentials[1];
        $this->baseURL = $URL;

        $last = strlen($URL) - 1;
        if($URL[$last] == "/"){
            $this->baseURL = substr($this->baseURL, 0, -1);
        }

        if($token === null) {
            $this->token = $this->getToken();
        } else {
            $this->token = $token; // TODO: Kontrola tokenu?
        }
        //var_dump($this->token);die();

    }

    /**
     * @param string $type defines request type GET / POST / PUT...
     * @param bool $useToken If true, token will be used.
     * @return array cUrl opt array.
     * @throws Exception Throw exception, if passed data is not used.
     */
    private function setCurlOptions($type, $useToken = true){

        // Create options array.
        $options[CURLOPT_RETURNTRANSFER] = true;
        $options[CURLINFO_HEADER_OUT] = true;
        $options[CURLOPT_HTTPHEADER] = array(

            'Content-Type: application/json',
            'Charset: utf-8',

        );

        // If token exists, lets use it.
        if($useToken){
            $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $this->token;
        }

        switch($type){

            case 'POST':
                // enable post
                $options[CURLOPT_POST] =  true;
                break;

            case 'GET':
                $options[CURLOPT_HTTPGET] = true;
                break;

            case 'PUT':
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
                break;

            default:
                throw new Exception("Unsupported request type.", 0);

        }

        return $options;
    }

    /**
     * @param string $url Target url.
     * @param string $data Data which should be sent.
     * @param string $type Request type (GET, POST, PUT,...)
     * @param bool $useToken If true, use token.
     * @return string JSON string with response
     * @throws Exception If request fails.
     */
    private function request($url, $type, $data = null, $useToken = true)
    {

        $options = $this->setCurlOptions($type, $useToken);

        if($data !== null){

            switch ($type){

                case "POST":
                case "PUT":
                    // Set request data
                    $options[CURLOPT_POSTFIELDS] =  $data;
                    break;

                default:
                    throw new RuntimeException("Request data was not used.", 0);
            }

        }

        // Prepare new cURL resource
        $ch = curl_init($this->baseURL .$url);
        // var_dump(curl_getinfo($ch));

        curl_setopt_array($ch, $options);
        // Submit the POST request
        $result = curl_exec($ch);

        /*var_dump(curl_getinfo($ch));
        var_dump($data);*/
        // In case of error
        if(! $result){

            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);

            // Close cURL session handle
            curl_close($ch);

            throw new RuntimeException(
                $type." request was not successful. Curl error code: ".$errno.
                ", HTTP error code: ".$code
            , $code);
        }

        // Close cURL session handle
        curl_close($ch);

        return $result;
    }

    /**
     * @param $json string Input data
     * @return array Output data
     * @throws Exception If decoding fails.
     */
    private function jsonDecode($json){

        $data = json_decode($json, true);

        // JSON couldn't be decoded
        if($data === null){
            throw new RuntimeException("JSON could not be decoded.", 0);
        }

        return $data;

    }

    /**
     * @param string $url Target url.
     * @param string $data Data which should be sent.
     * @param bool $useToken If true, use token.
     * @return string JSON string with response
     * @throws Exception If request fails.
     */
    private function post($url, $data, $useToken = true){
        return $this->request($url, "POST", $data, $useToken);
    }

    /**
     * @param string $url Target url.
     * @param string $data Data which should be sent.
     * @param bool $useToken If true, use token.
     * @return string JSON string with response
     * @throws Exception If request fails.
     */
    private function get($url, $data, $useToken = true){
        return $this->request($url, "GET", $data, $useToken);
    }

    /**
     * @param string $url Target url.
     * @param string$data Data which should be sent.
     * @param bool $useToken If true, use token.
     * @return string|null token
     * @throws Exception If request fails.
     */
    private function put($url, $data, $useToken = true){
        return $this->request($url, "PUT", $data, $useToken);
    }

    private function getToken(){

        // We'r obtaining token, can't use it.
        $useToken = false;

        $data = '{
                  "username" : "'.$this->apiLogin.'",
                  "password" : "'.$this->apiPassword.'"
                 }';


        $result = $this->post('/authentication/v1/partner', $data, $useToken);
        $response = $this->jsonDecode($result);


        if(array_key_exists("accessToken", $response))
            return $response["accessToken"];
        else
            return null;

    }

    // Public methods

    /**
     * @param string $json Application data
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function createApplication($json){

        $json = $this->post('/financing/v1/applications', $json);

        return $this->jsonDecode($json);

    }

    /**
     * @param string $applicationId
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function markOrderAsSent($applicationId){

        // Request URL without sending data.
        $json = $this->put('/financing/v1/applications/' . $applicationId . '/order/send', null);

        return $this->jsonDecode($json);

    }

    /**
     * @param string $applicationId
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function markOrderAsDelivered($applicationId){

        // Request URL without sending data.
        $json = $this->put('/financing/v1/applications/' . $applicationId . '/order/deliver', null);

        return $this->jsonDecode($json);

    }

    /** This function is only for testing purposes.
     * @param string $applicationId
     * @param string $applicationState State in which should be application transited.
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function changeApplicationState($applicationId, $applicationState){

        $data = '{ "stateReason": "'.$applicationState.'"}';

        // Request URL sending data.
        $json = $this->post('/fakeshop/rest/applications/' . $applicationId . '/changeState', $data);

        return $this->jsonDecode($json);

    }

    /**
     * @param string $applicationId
     * @param string $reason
     * @param string $customReason
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function cancelApplication($applicationId, $reason, $customReason = ""){

        $data = '{
                  "stateReason": "'.$reason.'",
                  "customReason": "'.$customReason.'"
                 }';

        // Request URL without sending data.
        $json = $this->put('/financing/v1/applications/' . $applicationId . '/cancel', $data);

        return $this->jsonDecode($json);

    }

    /**
     * @param string $applicationId
     * @return array Api call result array.
     * @throws Exception If request fails.
     */
    public function getApplicationDetail($applicationId){

        // Request URL without sending data.
        $json = $this->get('/financing/v1/applications/' . $applicationId, null);

        return $this->jsonDecode($json);
    }

    /**
     * @return bool true if token exists.
     */
    public function isLoggedIn(){

        try {
            $this->token = $this->getToken();
        } catch(Exception $e){
            return false;
        }

        if($this->token != null)
            return true;

        return false;

    }


}