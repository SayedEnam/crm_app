<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)], 'phone' => ['nullable', 'string', 'max:30']]);
        $request->user()->fill($data)->save();

        return back()->with('status', 'Profile updated.');
    }
}
