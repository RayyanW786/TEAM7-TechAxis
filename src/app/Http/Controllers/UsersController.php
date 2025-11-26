<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'admin_code' => ['required_if:role,admin', 'string'],
        ]);

        if ($data['role'] === 'admin') {
            $expected = (string) config('app.admin_registration_code');

            if (!hash_equals($expected, (string) $data['admin_code'])) {
                return back()->withErrors(['admin_code' => 'Invalid admin registration code.'])->withInput();
            }
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => UserRole::from($data['role']),
        ]);

        $user->setPassword($request->password, false);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'customer.dashboard');



    }
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);


        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'The provided credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'customer.dashboard');
    
    }
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
