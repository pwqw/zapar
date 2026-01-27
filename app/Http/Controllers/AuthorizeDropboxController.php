<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthorizeDropboxController extends Controller
{
    public function __invoke(Request $request)
    {
        $appKey = $request->route('key');

        return redirect()->away(
            "https://www.dropbox.com/oauth2/authorize?client_id=$appKey&response_type=code&token_access_type=offline",
        );
    }
}
