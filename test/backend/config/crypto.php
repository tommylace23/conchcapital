<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Deposit addresses
    |--------------------------------------------------------------------------
    | Shown to clients on the deposit screen and returned by the API. Values
    | stored in the admin settings take priority over these defaults.
    */

    'btc_address'  => env('CRYPTO_BTC_ADDRESS', 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh'),
    'eth_address'  => env('CRYPTO_ETH_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'),
    'usdt_address' => env('CRYPTO_USDT_ADDRESS', '0x742d35Cc6634C0532925a3b844Bc9e7595f0bEb'),
];
