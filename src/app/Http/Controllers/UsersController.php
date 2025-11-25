<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
   public function register(Request $request)
   {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'role' => 'required|in:admin,customer',
        'admin_code' => 'required_if:role,admin|string'
    ]);
    if ($request->role === 'admin' && $request->admin_code !== env('ADMIN_REGISTRATION_CODE')) {
       return back()->withErrors(['admin_code' => 'Invalid admin registration code.'])->withInput();
    }

    $user = users::create([
        'name' => $request->name,
        'email' => $request->email,
        'role' => $request->role === 'admin' ? UserRole::Admin : UserRole::Customer,
    ]);

    $user->setPassword($request->password, false);
      Auth::login($user);
      $request->session()->regenerate();

      if ($user->isAdmin()) {
          return redirect()->route(ADMIN_DASHBOARD_PLACEHOLDER);
      } else {
          return redirect()->route(CUSTOMER_DASHBOARD_PLACEHOLDER);
      }



   }
   public function login(Request $request)
   {
      $request->validate([
          'email' => 'required|string|email',
          'password' => 'required|string',
      ]);

      $user = users::where('email', $request->email)->first();

      if (!$user || !Hash::check($request->password, $user->password_hash)) {
          return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->withInput();
      }
      Auth::login($user);
      $request->session()->regenerate();

      if ($user->isAdmin()) {
          return redirect()->route(ADMIN_DASHBOARD_PLACEHOLDER);
      } else {
          return redirect()->route(CUSTOMER_DASHBOARD_PLACEHOLDER);
      }
   }
   public function logout(Request $request)
   {
      Auth::logout();
      $request->session()->invalidate();
      $request->session()->regenerateToken();
      return redirect()->route(HOME_PLACEHOLDER);
   }
}
