<?php

namespace App\Http\Controllers\Demo;

use App\Attributes\RequiresDemo;
use App\Http\Controllers\Controller;
use App\Services\SettingService;

#[RequiresDemo]
class IndexController extends Controller
{
    public function __invoke(SettingService $settingService)
    {
        if (!request()->session()->has('demo_account')) {
            // redirect to the new session controller to create or get a demo account
            return redirect()->route('demo.new-session');
        }

        $data = [
            'demo_account' => request()->session()->get('demo_account'),
        ];

        if (config('koel.misc.allow_anonymous')) {
            $data['consent_legal_urls'] = $settingService->getConsentLegalUrls();
        }

        return view('index', $data);
    }
}
