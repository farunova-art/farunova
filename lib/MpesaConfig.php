<?php

/**
 * FARUNOVA M-Pesa Configuration
 * Centralized M-Pesa API credentials and environment settings
 * 
 * @version 1.0
 */

class MpesaConfig
{

    // M-Pesa Sandbox/Production URLs
    const SANDBOX_AUTH_URL = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    const PRODUCTION_AUTH_URL = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    const SANDBOX_STK_URL = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    const PRODUCTION_STK_URL = 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

    const SANDBOX_QUERY_URL = 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query';
    const PRODUCTION_QUERY_URL = 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';

    const SANDBOX_QR_URL = 'https://sandbox.safaricom.co.ke/mpesa/qrcode/v1/generate';
    const PRODUCTION_QR_URL = 'https://api.safaricom.co.ke/mpesa/qrcode/v1/generate';

    // Environment mode
    private static $environment = 'sandbox'; // 'sandbox' or 'production'

    // API Credentials
    private static $consumerKey;
    private static $consumerSecret;
    private static $passKey;
    private static $shortCode;
    private static $callbackUrl;
    private static $accountNo;

    /**
     * Initialize M-Pesa configuration from environment variables
     */
    public static function init()
    {
        self::$consumerKey = getenv('MPESA_CONSUMER_KEY') ?: 'yqG6GRkrpP8tjtBDHWGzr3IB20t5vQXQvqE6jnstrwvxZSYr';
        self::$consumerSecret = getenv('MPESA_CONSUMER_SECRET') ?: 'yqG6GRkrpP8tjtBDHWGzr3IB20t5vQXQvqE6jnstrwvxZSYr';
        self::$passKey = getenv('MPESA_PASSKEY') ?: 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
        self::$shortCode = getenv('MPESA_SHORTCODE') ?: '174379';
        self::$callbackUrl = getenv('MPESA_CALLBACK_URL') ?: 'https://farunova.com/api/payments.php?action=callback';
        self::$accountNo = getenv('MPESA_ACCOUNT_NO') ?: 'FARUNOVA';
        self::$environment = getenv('MPESA_ENV') ?: 'sandbox';
    }

    /**
     * Get API authentication URL
     */
    public static function getAuthUrl()
    {
        return self::$environment === 'production' ? self::PRODUCTION_AUTH_URL : self::SANDBOX_AUTH_URL;
    }

    /**
     * Get STK Push URL
     */
    public static function getStkUrl()
    {
        return self::$environment === 'production' ? self::PRODUCTION_STK_URL : self::SANDBOX_STK_URL;
    }

    /**
     * Get STK Query URL
     */
    public static function getQueryUrl()
    {
        return self::$environment === 'production' ? self::PRODUCTION_QUERY_URL : self::SANDBOX_QUERY_URL;
    }

    /**
     * Get QR Code URL
     */
    public static function getQrUrl()
    {
        return self::$environment === 'production' ? self::PRODUCTION_QR_URL : self::SANDBOX_QR_URL;
    }

    /**
     * Get consumer key
     */
    public static function getConsumerKey()
    {
        return self::$consumerKey;
    }

    /**
     * Get consumer secret
     */
    public static function getConsumerSecret()
    {
        return self::$consumerSecret;
    }

    /**
     * Get pass key
     */
    public static function getPassKey()
    {
        return self::$passKey;
    }

    /**
     * Get short code
     */
    public static function getShortCode()
    {
        return self::$shortCode;
    }

    /**
     * Get callback URL
     */
    public static function getCallbackUrl()
    {
        return self::$callbackUrl;
    }

    /**
     * Get account number
     */
    public static function getAccountNo()
    {
        return self::$accountNo;
    }

    /**
     * Get environment mode
     */
    public static function getEnvironment()
    {
        return self::$environment;
    }

    /**
     * Set environment mode
     */
    public static function setEnvironment($env)
    {
        self::$environment = $env;
    }

    /**
     * Check if in production
     */
    public static function isProduction()
    {
        return self::$environment === 'production';
    }

    /**
     * Get timestamp in correct format
     */
    public static function getTimestamp()
    {
        return date('YmdHis');
    }

    /**
     * Generate password for STK push
     */
    public static function generatePassword($timestamp)
    {
        $password = base64_encode(self::$shortCode . self::$passKey . $timestamp);
        return $password;
    }
}

// Initialize on load
MpesaConfig::init();
