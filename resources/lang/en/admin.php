<?php

return [
    //Title
    'login' => 'Login',
    'forgot_password' => 'Forgot password',
    'reset_password' => 'Reset password',
    'change_password' => 'Change password',
    'dashboard' => 'Dashboard',
    'profile' => 'Profile',
    'site_settings' => 'Site Settings',
    'version_settings' => 'Version Settings',
    'credentials' => 'Credentials',

    'change_password_updated' => 'Your password has been updated',
    'change_fail_to_update' => 'Fail to update Password',
    'change_password_not_match' => 'old password does not match',
    'change_password_not_same' => 'new password and confirm password not match',
    'site_setting_updated' => 'Site settings have been update successfully',
    'credentials_updated' => 'Credentials have been update successfully',

    // Event published
    'event_published_title' => 'New Event Published',
    'event_published_message' => 'A new event has been published!',

    // Business created
    'business_created_title' => 'New Business Created',
    'business_created_message' => 'A new business has been created!',

    // Event reminder
    'event_reminder_title' => 'Event Reminder',
    'event_reminder_message' => 'Don\'t forget about the upcoming event!',

    'errors' => [
        'username_or_email' => [
            'required' => 'Please enter your username or email',
            'remote' => 'This username or email is already taken',
            'format' => 'Please enter a valid email address',
        ],
        'password' => [
            'required' => 'Please enter your password',
            'minlength' => 'Password must be at least 6 characters',
        ],
        'confirm_password' => [
            'required' => 'Please enter your confirm password',
            'equal_to' => 'Confirm password does not match',
        ],
        'email' => [
            'required' => 'Please enter your email',
            'remote' => 'This email is already taken',
            'format' => 'Please enter a valid email address',
        ],
        'username' => [
            'required' => 'Please enter your username',
            'remote' => 'This username is already taken',
        ],
        'name' => [
            'required' => 'Please enter your name',
        ],
        'opassword' => [
            'required' => 'Please enter your current password',
        ],
        'npassword' => [
            'required' => 'Please enter a new password',
            'minlength' => 'The new password must be at least 6 characters long',
            'notEqual' => 'Old Password and New Password can not be Same',
        ],
        'cpassword' => [
            'required' => 'Please confirm your new password',
            'equal_to' => 'The confirmation password does not match the new password',
        ],
        'title' => [
            'required' => 'Please enter your title',
        ],
        'details' => [
            'required' => 'Please enter your details',
        ],
        'image' => [
            'required' => 'Please upload an image',
        ],
        'business_category' => [
            'required' => 'Please enter your business category',
            'remote' => 'This business category is already taken',
        ],
        'event_category' => [
            'required' => 'Please enter your event category',
            'remote' => 'This event category is already taken',
        ],
    ]
];
