<?php
/**
 * Cliente HTTP base. Ejecuta GET / POST / PUT / DELETE contra la API Node.js.
 * Adjunta automáticamente el JWT Bearer si existe en sesión.
 */
class HttpClient {

    private $baseUrl;
    private $timeout;
    private $lastError;
    private $lastResponse;

    public function __construct() {
        $config        = ApiConfig::getInstance();
        $this->baseUrl = rtrim($config->getApiUrl(), '/');
        $this->timeout = $config->getTimeout();
    }

    public function get($endpoint, $params = []) {
        return $this->request('GET', $this->buildUrl($endpoint, $params));
    }

    public function post($endpoint, $data = []) {
        return $this->request('POST', $this->buildUrl($endpoint), $data);
    }

    public function put($endpoint, $data = []) {
        return $this->request('PUT', $this->buildUrl($endpoint), $data);
    }

    public function delete($endpoint, $data = []) {
        return $this->request('DELETE', $this->buildUrl($endpoint), $data);
    }

    private function buildUrl($endpoint, $params = []) {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        return $url;
    }

    private function request($method, $url, $data = null) {
        $ch = curl_init($url);

        $headers = ['Content-Type: application/json', 'Accept: application/json'];
        $token   = TokenManager::getToken();
        if ($token) $headers[] = 'Authorization: Bearer ' . $token;

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($data !== null && in_array($method, ['POST', 'PUT', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        $this->lastResponse = $response;

        if ($error) {
            $this->lastError = $error;
            return ['success' => false, 'error' => 'Error de conexión: ' . $error, 'http_code' => 0];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData, 'http_code' => $httpCode];
        }

        $errorMsg = $responseData['error'] ?? $responseData['message'] ?? 'Error HTTP ' . $httpCode;
        return ['success' => false, 'error' => $errorMsg, 'data' => $responseData, 'http_code' => $httpCode];
    }

    public function getLastError()    { return $this->lastError; }
    public function getLastResponse() { return $this->lastResponse; }
}
