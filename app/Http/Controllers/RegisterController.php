<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('authorise.register');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'string', 'max:20', 'unique:users,student_id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique'      => 'An account already exists for this email.',
            'student_id.unique' => 'This student ID is already registered.',
        ]);

        User::create([
            'student_id' => $data['student_id'],
            'name'       => $data['name'],
            'email'      => strtolower($data['email']),
            'password'   => Hash::make($data['password']),
        ]);

        return redirect()->route('login')->with('status', 'Account created. You can now log in.');
    }
}