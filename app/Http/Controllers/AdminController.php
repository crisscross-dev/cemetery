<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\Admin;

class AdminController extends Controller
{
    /**
     * Show the admin login form
     */
    public function showLoginForm()
    {
        // If already logged in, redirect to map
        if (Session::get('admin_logged_in')) {
            return redirect()->route('admin.map');
        }

        return view('admin.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Authenticate against database
        $admin = Admin::where('username', $request->username)->first();

        if ($admin && $admin->verifyPassword($request->password)) {
            Session::put('admin_logged_in', true);
            Session::put('admin_username', $admin->username);
            Session::put('admin_name', $admin->name);

            return redirect()->route('admin.map');
        }

        return back()->withErrors(['error' => 'Invalid username or password']);
    }

    /**
     * Handle admin logout
     */
    public function logout()
    {
        Session::forget('admin_logged_in');
        Session::forget('admin_username');
        Session::forget('admin_name');
        Session::flush();

        return redirect()->route('homepage');
    }
}
