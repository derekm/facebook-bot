<?php

/**
 * This file is part of the FacebookBot package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author  Peter Kokot
 * @author  Dennis Degryse
 * @since   0.0.4
 * @version 0.0.4
 */

namespace PHPWorldWide\FacebookBot\Connection\Request;

/**
 * A request adapter for cURL HTTP requests.
 */
class CURLRequest extends RequestAbstract
{
    const USERAGENT = 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:42.0) Gecko/20100101 Firefox/42.0';

    /**
     * The base url for the request.
     */
    private $baseUrl;

    /**
     * The session cookies.
     */
    private $cookies;

    /**
     * Whether or not to retrieve only headers (exclusive).
     */
    private $headersOnly;

    /**
     * The request headers to set.
     */
    private $headers;

    /**
     * Creates a new instance.
     *
     * @param string $baseUrl The base url for the request
     * @param string $path The request path
     * @param string $method The request method
     * @param string $cookies The session cookies
     * @param string $data The data to send with the request
     * @param string $headersOnly Whether or not to retrieve only headers (exclusive)
     * @param string $headers The request headers to set;
     */
    public function __construct($baseUrl, $path, $method, $cookies = null, $data = [], $headersOnly = false, $headers = []) 
    {
        parent::__construct($path, $method, $data);

        $this->baseUrl = $baseUrl;
        $this->cookies = $cookies;
        $this->headersOnly = $headersOnly;
        $this->headers = $headers;
    }

    /**
     * Performs the HTTP request with cURL using the provided cookie and returns the result.
     *
     * @return string Result of cURL session.
     *
     * @throws Exception in case the cURL request has failed.
     */
    public function execute()
    {
        $curl = curl_init();

        $url = $this->baseUrl . $this->getPath();

        curl_setopt($curl, CURLOPT_HEADER, $this->headersOnly);
        curl_setopt($curl, CURLOPT_NOBODY, $this->headersOnly);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_COOKIEFILE, "cookie");
        curl_setopt($curl, CURLOPT_COOKIEJAR, "cookie");
        curl_setopt($curl, CURLOPT_USERAGENT, self::USERAGENT);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

        if (count($this->headers) > 0) {
            $headers = array_map(
                function($name, $value) { return "$name: $value"; }, 
                array_keys($this->headers), 
                array_values($this->headers)
            );

            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }

        if (is_array($this->getParameters())) {
            if (count($this->getParameters()) > 0) {
                $dataString = http_build_query($this->getParameters());
            } else {
                $dataString = "";
            }
        } else {
            $dataString = $this->getParameters();
        }

        switch ($this->getMethod()) {
            case 'POST':
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
                break;

            case 'GET': 
                curl_setopt($curl, CURLOPT_URL, "$url?$dataString");
                break;

            default:
                throw new \Exception("An error has occured during the cURL request: Method $method is currently not supported");
                break;
        }

        $result = curl_exec($curl);

        if (!$result) 
        {
            $errorDetails = curl_error($curl);

            throw new \Exception("An error has occured during the cURL request: " . $errorDetails);
        }

        curl_close($curl);

        return $result;
    }
}