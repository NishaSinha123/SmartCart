<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Cart;
use App\Models\Budget;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'role' => ['required', 'in:user,seller,admin'],
            'password' => ['required', 'confirmed', Rules\Password::min(8)->mixedCase()->numbers()->symbols()],
            'terms' => ['required', 'accepted'],
        ], [
            'name.regex' => 'Name should only contain letters and spaces.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'terms.required' => 'You must agree to the terms and conditions.',
        ]);

        // ADMIN automatically verified, others need verification
        $isAdmin = $request->role === 'admin';
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'email_verified_at' => $isAdmin ? now() : null,  // Admin automatically verified
        ]);

        Cart::create(['user_id' => $user->id]);
        Budget::create(['user_id' => $user->id, 'amount' => 0]);

        // Send verification email ONLY for non-admin users 
        if (!$isAdmin) {
            event(new Registered($user));
        }

        Auth::login($user);

        // Redirect appropriately
        if ($isAdmin) {
            return redirect()->route('admin.dashboard');
        }
        
        // For non-admin users, go to verification page
        return redirect()->route('verification.notice');
    }
}