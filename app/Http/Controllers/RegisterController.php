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
        $request->merge([
            'student_id' => strtoupper(trim((string) $request->student_id)),
            'email'      => strtolower(trim((string) $request->email)),
        ]);

        $data = $request->validate([
            'student_id' => ['bail','required', 'regex:/^B[0-9]{7}$/', 'unique:users,student_id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['bail','required', 'email', 'max:255', 'unique:users,email'],
            'password'   => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'student_id.required' => 'Enter valid Student ID',
            'student_id.regex'    => 'Enter valid Student ID',
            'student_id.unique'   => 'This student ID is already registered.',
            'email.ends_with'     => 'Please use your @helplive.edu.my email address.',
            'email.unique'        => 'An account already exists for this email.',
        ]);

        User::create([
            'student_id' => $data['student_id'],
            'name'       => $data['name'],
            'email'      => strtolower($data['email']),
            'password'   => Hash::make($data['password']),
        ]);

        return redirect()->route('login')->with('success', 'Account created. You can now log in.');
    }
}