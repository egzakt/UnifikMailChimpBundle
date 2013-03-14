<?php

namespace Egzakt\MailChimpBundle\Lib;

require_once __DIR__ . 'MailChimp/MCAPI.class.php';

/**
 * Class MailChimpApi
 *
 * This class is a wrapper over MCAPI to be used as a Symfony Service
 *
 * @package Egzakt\MailChimpBundle\Lib
 */
class MailChimpApi
{

    /**
     * Construct
     *
     * @param string $apiKey The MailChimp API Key
     * @param bool $secure Whether to use HTTPS or not
     */
    public function __construct($apiKey, $secure)
    {
        return new \MCAPI($apiKey, $secure);
    }

}