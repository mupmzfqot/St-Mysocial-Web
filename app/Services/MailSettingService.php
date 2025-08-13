<?php

namespace App\Services;

use App\Models\SmtpConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class MailSettingService
{
    public static function getMailSettings()
    {
        if(Schema::hasTable('smtp_configs')) {
            $settings = SmtpConfig::first();

            if ($settings) {
                Config::set('mail.mailers.smtp.host', $settings->host ?? config('mail.mailers.smtp.host'));
                Config::set('mail.mailers.smtp.port', $settings->port ?? config('mail.mailers.smtp.port'));
                Config::set('mail.mailers.smtp.username', $settings->username ?? config('mail.mailers.smtp.username'));
                Config::set('mail.mailers.smtp.password', $settings->password ?? config('mail.mailers.smtp.password'));
                Config::set('mail.mailers.smtp.encryption', $settings->encryption ?? config('mail.mailers.smtp.encryption'));
                Config::set('mail.from.address', $settings->email ?? config('mail.from.address'));
            }
        }
    }
}