<?php

namespace src\ZipkinOpenTracing\Propagation;

use OpenTracing\Propagators\Reader;
use OpenTracing\Propagators\Writer;
use Zipkin\Propagation\Getter;
use Zipkin\Propagation\Setter;

class CarrierPropagator implements Getter, Setter
{
    /**
     * @param Writer $carrier
     * @param string $key
     * @param string $value
     * @return void
     */
    public function put($carrier, $key, $value)
    {
        $carrier->set($key, $value);
    }

    /**
     * @param Reader $carrier
     * @param string $key
     * @return string
     */
    public function get($carrier, $key)
    {
        foreach ($carrier as $carrierKey => $carrierValue)
        {
            if ($carrierKey === $key) {
                return $carrierValue;
            }
        }

        return null;
    }
}