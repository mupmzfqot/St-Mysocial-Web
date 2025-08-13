<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpConfig extends Model
{
    protected $table = 'smtp_configs';
    protected $fillable = [
        'host',
        'port',
        'username',
        'password',
        'encryption',
        'email',
        'sender',
    ];
}
