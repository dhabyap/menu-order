<?php

return [
    'whatsapp' => env('STORE_WHATSAPP', ''),
    'delivery_fee' => (int) env('STORE_DELIVERY_FEE', 0),
    'open_time' => env('STORE_OPEN_TIME', '08:00'),
    'close_time' => env('STORE_CLOSE_TIME', '21:00'),
    'timezone' => env('STORE_TIMEZONE', 'Asia/Jakarta'),
];
