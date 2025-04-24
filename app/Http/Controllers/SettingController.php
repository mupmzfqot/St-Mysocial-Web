<?php

namespace App\Http\Controllers;

use App\Models\SmtpConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    public function index()
    {
        $config = SmtpConfig::first();
        return Inertia::render('Settings/AppSetting', compact('config'));
    }

    public function save_smtp(Request $request, $id = null)
    {
        $request->validate([
            'host' => 'required',
            'port' => 'required|integer',
            'username' => 'required',
            'password' => 'required',
            'encryption' => 'required',
            'sender' => 'required',
            'email' => 'required|email',
        ]);

        if ($id) {
            $smtp_config = SmtpConfig::find($id);
            
        } else {
            $smtp_config = new SmtpConfig();
        }
        
        $result = $this->smtp_payload($smtp_config, $request);

        return back()->withSuccess('Successfully saved!');
    }

    private function smtp_payload($smtp_config, $request)
    {
        $smtp_config->host = $request->host;
        $smtp_config->port = $request->port;
        $smtp_config->username = $request->username;
        $smtp_config->password = $request->password;
        $smtp_config->encryption = $request->encryption;
        $smtp_config->sender = $request->sender;
        $smtp_config->email = $request->email;
        $smtp_config->save();
        return $smtp_config;
    }
}
