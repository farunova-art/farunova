<?php

/**
 * FARUNOVA M-Pesa Authentication Handler
 * Manages OAuth token generation and token caching
 * 
 * @version 1.0
 */

class MpesaAuth
{

    private $tokenCache = 'cache/mpesa_token.json';
    private $tokenExpiry = 3500; // 58 minutes (M-Pesa gives 1 hour)

    /**
     * Get or refresh access token
     */
    public function getAccessToken()
    {
        // Check if token is cached and still valid
        if ($this->isTokenValid()) {
            $cached = json_decode(file_get_contents($this->tokenCache), true);
            return $cached['access_token'];
        }

        // Request new token
        return $this->requestNewToken();
    }

    /**
     * Request new access token from M-Pesa
     */
    private function requestNewToken()
    {
        try {
            $key = MpesaConfig::getConsumerKey();
            $secret = MpesaConfig::getConsumerSecret();
            $authUrl = MpesaConfig::getAuthUrl();

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => $authUrl,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => "$key:$secret",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0'
            ]);

            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($httpCode !== 200) {
                throw new Exception('Failed to get M-Pesa token: ' . $response);
            }

            $result = json_decode($response, true);

            if (!isset($result['access_token'])) {
                throw new Exception('No access token in response');
            }

            // Cache the token
            $this->cacheToken($result['access_token']);

            return $result['access_token'];
        } catch (Exception $e) {
            throw new Exception('M-Pesa Auth Error: ' . $e->getMessage());
        }
    }

    /**
     * Cache access token to file
     */
    private function cacheToken($token)
    {
        $data = [
            'access_token' => $token,
            'cached_at' => time()
        ];

        // Ensure cache directory exists
        if (!is_dir('cache')) {
            mkdir('cache', 0755, true);
        }

        file_put_contents($this->tokenCache, json_encode($data));
    }

    /**
     * Check if cached token is still valid
     */
    private function isTokenValid()
    {
        if (!file_exists($this->tokenCache)) {
            return false;
        }

        try {
            $cached = json_decode(file_get_contents($this->tokenCache), true);
            $age = time() - $cached['cached_at'];

            return $age < $this->tokenExpiry;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clear cached token
     */
    public function clearCache()
    {
        if (file_exists($this->tokenCache)) {
            unlink($this->tokenCache);
        }
    }
}
