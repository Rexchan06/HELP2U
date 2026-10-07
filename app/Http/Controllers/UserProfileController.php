<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('student_profile.student_edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        // Only name and bio are accepted. Email and student ID cannot be changed here.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'bio'  => ['nullable', 'string', 'max:500'],
        ]);

        $request->user()->update($data);

        return redirect()->route('dashboard')->with('status', 'Your profile has been updated.');
    }
}