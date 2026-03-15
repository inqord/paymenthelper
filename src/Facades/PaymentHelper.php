<?php

namespace Inqord\PaymentHelper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Inqord\PaymentHelper\Contracts\GatewayInterface driver(string $driver = null)
 * @method static string initiate(\Inqord\PaymentHelper\DataTransferObjects\PaymentRequest $request)
 * @method static \Inqord\PaymentHelper\DataTransferObjects\VerificationResponse verify(\Illuminate\Http\Request $request)
 */
class PaymentHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'paymenthelper';
    }
}
