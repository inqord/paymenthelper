<?php

namespace Inqord\PaymentHelper;

use Illuminate\Support\Manager;
use Inqord\PaymentHelper\Gateways\SslCommerzGateway;
use Inqord\PaymentHelper\Gateways\EpsGateway;
use Inqord\PaymentHelper\Gateways\BkashGateway;
use Illuminate\Support\Facades\Log;

class PaymentManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('paymenthelper.default') ?? 'eps';
    }

    /**
     * Create an instance of the EPS gateway driver.
     *
     * @return EpsGateway
     */
    protected function createEpsDriver()
    {
        return new EpsGateway(
            $this->config->get('paymenthelper.gateways.eps')
        );
    }

    /**
     * Create an instance of the SSLCommerz gateway driver.
     *
     * @return SslCommerzGateway
     */
    protected function createSslcommerzDriver()
    {
        return new SslCommerzGateway(
            $this->config->get('paymenthelper.gateways.sslcommerz')
        );
    }

    /**
     * Create an instance of the bKash gateway driver.
     *
     * @return BkashGateway
     */
    protected function createBkashDriver()
    {
        return new BkashGateway(
            $this->config->get('paymenthelper.gateways.bkash')
        );
    }
    
    // Note: The Manager dynamically proxies method calls like ->initiate() and ->verify() 
    // down to the default selected driver.
}
