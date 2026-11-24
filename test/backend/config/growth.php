<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profit Growth rate
    |--------------------------------------------------------------------------
    | Weekly percentage applied to Profit Growth accounts. The daily rate used
    | by the scheduler is derived by dividing this by 7.
    */
    'weekly_percent' => (float) env('GROWTH_WEEKLY_PERCENT', 5.0),

    'frequency' => env('GROWTH_FREQUENCY', 'weekly'),
];
