<?php

namespace Wncms\Database\Seeders;

use Wncms\Models\ContactFormOption;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContactFormSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $contact_form_options = [
            [
                "name" => "name",
                "type" => "text",
                "display_name" => "姓名",
                "placeholder" => "輸入你的名字",
                "default_value" => null,
                "options" => null,
            ],
            [
                "name" => "email",
                "type" => "text",
                "display_name" => "Email",
                "placeholder" => "輸入你的Email",
                "default_value" => null,
                "options" => null,
            ],
            [
                "name" => "message",
                "type" => "textarea",
                "display_name" => "訊息",
                "placeholder" => "輸入你要查詢的內容",
                "default_value" => null,
                "options" => null,
            ],
            [
                "name" => "type",
                "type" => "select",
                "display_name" => "是次查詢的目的",
                "placeholder" => null,
                "default_value" => null,
                "options" => "售前查詢\r\n商務合作\r\n技術支援\r\n帳務查詢",
            ]
        ];

        foreach($contact_form_options as $contact_form_option){
            ContactFormOption::create([
                'name' => $contact_form_option['name'],
                'type' => $contact_form_option['type'],
                'display_name' => $contact_form_option['display_name'],
                'placeholder' => $contact_form_option['placeholder'],
                'default_value' => $contact_form_option['default_value'],
                'options' => $contact_form_option['options'],
            ]);
        }
    }
}
