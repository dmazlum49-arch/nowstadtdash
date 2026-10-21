<?php

return [

    'Free' => [
        'event_limit'       => 0,
        'feed_post_limit'   => 0,
        'push_notification_limit' => 0,
        'stamp_card'        => false,
        'visibility'        => 'normal',
    ],

    'Basic' => [
        'event_limit' => 1,
        'feed_post_limit' => 0,
        'push_notification_limit' => 0,
        'stamp_card' => false,
        'visibility' => 'normal',
    ],

    'Plus' => [
        'event_limit' => 4,
        'feed_post_limit' => 1,
        'push_notification_limit' => 0,
        'stamp_card' => true,
        'visibility' => 'priority',
    ],

    'Premium' => [
        'event_limit' => 6,
        'feed_post_limit' => 2,
        'push_notification_limit' => 2,
        'stamp_card' => true,
        'visibility' => 'top',
    ],

];
