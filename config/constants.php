<?php

return [
    'upload_paths' => [
        'profile_images' => 'uploads/users', // Define the profile images folder
        'admin_upload' => 'uploads/admin', // Define the profile images folder
        'family_images' => 'uploads/family', // Define the profile images folder
        'event_images' => 'uploads/events', // Define the event images folder
        'post_images' => 'uploads/posts', 
        'service_images' => 'uploads/services',
        'business_images' => 'uploads/businesses',
        'nowis_point_images' => 'uploads/nowisPoints',
        'stamp_card_images' => 'uploads/stampCards',
        'home_banner_images' => 'uploads/homeBanner',
        'category_images' => 'uploads/businessCategory',
        'chat_images' => 'uploads/chat',
        'company_registration' => 'uploads/businesses/company_registration',
        "chat_upload" => 'uploads/chat',
        'help_images' => 'uploads/help_requests',
        'story_images' => 'uploads/stories',
        'report_type_images' => 'uploads/reportTypes',
    ],
    'default' => [
        'default/no_user_image.png',
        'default/no_image.png',
    ],
    'upload_type' => 's3',

    'firebase_credentials' => public_path('default/fcm_config.json'),
    'push_log' => true,
];
