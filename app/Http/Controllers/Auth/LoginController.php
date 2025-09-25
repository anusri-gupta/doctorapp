<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Doctor;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{
    // Show login form
    public function showLoginForm()
    {
        if (auth('doctor')->check()) {
            return redirect()->route('availability.create');
        }

        return view('frontend.index'); // Your login Blade view
    }


    public function register(Request $request)
    {
        // Validate input
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:doctors,email'],
            'password' => ['required', 'min:6'],
        ]);

        // Create doctor
        $doctor = Doctor::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Log in the doctor
        Auth::guard('doctor')->login($doctor);

        // Redirect to availability page
        return redirect()->route('availability.create');
    }

    // Handle login
    public function login(Request $request)
    {
        // Validate incoming form fields
        $credentials = $request->validate([
            'email' => ['required', 'email'], // You can change this to 'username'
            'password' => ['required'],       // You can change this to 'pass'
        ]);

        // Attempt login using 'doctor' guard
        if (Auth::guard('doctor')->attempt($credentials)) {
            $request->session()->regenerate(); // Prevent session fixation
            return redirect()->intended('/availability'); // Redirect to intended page or fallback
        }

        // If login fails
        return back()->with('error', 'Invalid credentials');
    }

    // Handle logout
    public function logout(Request $request)
    {
        Auth::guard('doctor')->logout(); // Logout from doctor guard
        $request->session()->invalidate(); // Clear session
        $request->session()->regenerateToken(); // Regenerate CSRF token

        return redirect('/'); // Redirect to login page
    }
}

