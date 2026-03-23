<?php
/**
 * Configuración de entorno y URL base de la API.
 * Detecta automáticamente: local | desarrollo | produccion
 */
class ApiConfig {

    private static $instance = null;
    private $entorno;
    private $config;

    private function __construct() {
        $this->entorno = $this->detectarEntorno();
        $this->config  = $this->getConfigPorEntorno();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function detectarEntorno() {
        $host     = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $enDocker = file_exists('/.dockerenv');

        if ($enDocker && $this->esHostLocal($host)) return 'produccion';
        if ($this->esHostLocal($host))               return 'local';
        if (strpos($host, 'desarollo') !== false
         || strpos($host, 'dev')       !== false)    return 'desarrollo';

        return 'produccion';
    }

    private function esHostLocal($host) {
        return strpos($host, 'localhost') !== false
            || strpos($host, '127.0.0.1') !== false
            || strpos($host, '192.168.')  !== false
            || strpos($host, '10.')       !== false;
    }

    private function getConfigPorEntorno() {
        return [
            'local'      => ['api_url' => 'http://127.0.0.1:3000',            'timeout' => 10,  'debug' => true],
            'desarrollo'  => ['api_url' => 'http://desarollo-bacros:3000',     'timeout' => 15,  'debug' => true],
            'produccion'  => ['api_url' => 'http://host.docker.internal:3000', 'timeout' => 20,  'debug' => true],
        ][$this->entorno];
    }

    public function getApiUrl()  { return $this->config['api_url']; }
    public function getTimeout() { return $this->config['timeout']; }
    public function isDebug()    { return $this->config['debug']; }
    public function getEntorno() { return $this->entorno; }

    public function getAppUrl($path = '') {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = ltrim($path, '/');
        return $protocol . '://' . $host . ($path ? '/' . $path : '');
    }
}
