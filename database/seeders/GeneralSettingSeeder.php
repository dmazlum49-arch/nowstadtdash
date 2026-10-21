<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GeneralSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the settings data
        $settings = [
            [
                'label' => 'Site Name',
                'unique_name' => 'SITE_NAME',
                'input_type' => 'text',
                'value' => 'name',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['required' => 'required']),
                'hint' => 'Please enter site name',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Site Logo',
                'unique_name' => 'SITE_LOGO',
                'input_type' => 'file',
                'value' => '',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['accept' => "image/*"]),
                'hint' => 'Site logo main',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Small Site Logo',
                'unique_name' => 'SMALL_SITE_LOGO',
                'input_type' => 'file',
                'value' => '',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['accept' => "image/*"]),
                'hint' => 'Site small logo main',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Fav Icon',
                'unique_name' => 'FAVICON',
                'input_type' => 'file',
                'value' => '',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['accept' => "image/*"]),
                'hint' => 'Fav icon for site',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Support Email',
                'unique_name' => 'SUPPORT_EMAIL',
                'input_type' => 'email',
                'value' => 'admin@gmail.com',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['maxlength' => "255", 'required' => 'required']),
                'hint' => 'Please enter email address for support',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Support Mobile',
                'unique_name' => 'SUPPORT_MOBILE',
                'input_type' => 'text',
                'value' => '+1 1000998877',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['maxlength' => "20", 'required' => 'required']),
                'hint' => 'Please enter mobile for support',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Android Link',
                'unique_name' => 'ANDROID_LINK',
                'input_type' => 'text',
                'value' => '',
                'options' => null,
                'class' => 'form-control',
                'extra' => null,
                'hint' => 'Please enter android link',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Ios Link',
                'unique_name' => 'IOS_LINK',
                'input_type' => 'text',
                'value' => '',
                'options' => null,
                'class' => 'form-control',
                'extra' => null,
                'hint' => 'Please enter ios link',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Android Version',
                'unique_name' => 'ANDROID_VERSION',
                'input_type' => 'text',
                'value' => '1',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['required' => 'required']),
                'hint' => 'Please enter android current version',
                'type' => 'version',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Android Force Update',
                'unique_name' => 'ANDROID_FORCE_UPDATE',
                'input_type' => 'select',
                'value' => '0',
                'options' => json_encode([
                    ['name' => 'Yes', 'value' => 'Yes'],
                    ['name' => 'No', 'value' => 'No'],
                ]),
                'class' => 'form-select',
                'extra' => null,
                'hint' => 'Is android update forced?',
                'type' => 'version',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'iOS Version',
                'unique_name' => 'IOS_VERSION',
                'input_type' => 'text',
                'value' => '1',
                'options' => null,
                'class' => 'form-control',
                'extra' => json_encode(['required' => 'required']),
                'hint' => 'Please enter iOS current version',
                'type' => 'version',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'iOS Force Update',
                'unique_name' => 'IOS_FORCE_UPDATE',
                'input_type' => 'select',
                'value' => '0',
                'options' => json_encode([
                    ['name' => 'Yes', 'value' => 'Yes'],
                    ['name' => 'No', 'value' => 'No'],
                ]),
                'class' => 'form-select',
                'extra' => null,
                'hint' => 'Is iOS update forced?',
                'type' => 'version',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'label' => 'Site Radius(km)',
                'unique_name' => 'SITE_RADIUS',
                'input_type' => 'number',
                'value' => 10,
                'options' => null,
                'class' => 'form-control',
                'extra' => null,
                'hint' => 'Please enter readius for site',
                'type' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Clear the table before seeding
        DB::table('general_settings')->truncate();

        // Insert settings into the database
        foreach ($settings as $setting) {
            DB::table('general_settings')->insert($setting);
        }
    }
}
