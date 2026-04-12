<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthPageController extends Controller
{
    public function login(): View
    {
        return view('pages.auth.login');
    }

    public function forgotPassword(): View
    {
        return view('pages.auth.forgot-password');
    }

    public function resetPassword(Request $request, string $token): View
    {
        return view('pages.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }
}
