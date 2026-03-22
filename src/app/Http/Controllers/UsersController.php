<?php


namespace App\Http\Controllers;
ini_set('display_errors', 1);
error_reporting(E_ALL);


use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;



class UsersController extends Controller
{
    public function register(Request $request)
    {

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role' => ['required', Rule::in(['admin', 'customer'])],
            'admin_code' => ['nullable', 'required_if:role,admin', 'string'],
        ]);


        if ($data['role'] === 'admin') {
            $expected = (string) config('app.admin_registration_code');

            if (!hash_equals($expected, (string) $data['admin_code'])) {
                return back()->withErrors(['admin_code' => 'Invalid admin registration code.'])->withInput();
            }
        }

        $user = DB::transaction(function () use ($data) {
            $user = new User();
            $user->name = $data['name'];
            $user->email = $data['email'];
            $user->role = UserRole::from($data['role']);
            $user->password_hash = Hash::make($data['password']);
            $user->password_must_change = false;
            $user->save();
            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'home');


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
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ]);

        if (!Hash::check($data['current_password'], $user->password_hash)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect'])
                ->withInput();
        }

        $user->password_hash = Hash::make($data['password']);
        $user->save();

        return back()->with('success', 'Password updated successfully');
    }
}
