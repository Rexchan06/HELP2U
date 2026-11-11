<?php

namespace App\Http\Controllers;

use App\Services\VerificationService;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function show()
    {
        return view('authorise.register');
    }

    public function store(Request $request, VerificationService $verification)
    {
        // Tidy the input first
        $request->merge([
            'student_id' => strtoupper(trim((string) $request->student_id)),
            'email'      => strtolower(trim((string) $request->email)),
        ]);

        $data = $request->validate([
            'student_id' => ['bail', 'required', 'regex:/^B[0-9]{7}$/', 'unique:users,student_id'],
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['bail', 'required', 'email', 'max:255', 'ends_with:@helplive.edu.my', 'unique:users,email'],
        ], [
            'student_id.required' => 'Enter valid Student ID',
            'student_id.regex'    => 'Enter valid Student ID',
            'student_id.unique'   => 'This student ID is already registered.',
            'email.ends_with'     => 'Please use your @helplive.edu.my email address.',
            'email.unique'        => 'An account already exists for this email.',
        ]);

        // Nothing is saved in the users table yet. The details wait in the cache until
        // the code is verified and the student has chosen a password.
        $token = $verification->startRegistration([
            'student_id' => $data['student_id'],
            'name'       => $data['name'],
            'email'      => $data['email'],
        ]);

        return redirect($verification->verifyUrl($token))
            ->with('status', 'We sent a 6-digit code to ' . $data['email'] . '. Enter it to continue.');
    }
}
