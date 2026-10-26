<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        
        // បន្ថែម URL ពេញលេញសម្រាប់រូបភាព
        $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

        return response()->json($user);
    }

   public function update(Request $request)
{
    $user = $request->user();

    $request->validate([
        'name' => 'required|string|max:255',
        // កំណត់ unique តែពេលអ៊ីមែលផ្លាស់ប្តូរ
        'email' => 'required|email|max:255|unique:users,email,' . $user->id,
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
    ]);

    $user->name = $request->name;
    $user->email = $request->email;

    if ($request->hasFile('avatar')) {
        if ($user->avatar && \Storage::disk('public')->exists($user->avatar)) {
            \Storage::disk('public')->delete($user->avatar);
        }
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
    }

    $user->save();

    $user->avatar_url = $user->avatar ? asset('storage/' . $user->avatar) : null;

    return response()->json([
        'message' => 'Profile updated successfully',
        'user' => $user,
    ]);
}

public function updatePassword(Request $request)
{
    $request->validate([
        'current_password' => 'required',
        'new_password' => 'required|min:6|confirmed', 
    ]);

    $user = $request->user();

    if (!\Hash::check($request->current_password, $user->password)) {
        return response()->json(['message' => 'Password ចាស់មិនត្រឹមត្រូវទេ។'], 400);
    }

    $user->password = \Hash::make($request->new_password);
    $user->save();

    return response()->json(['message' => 'Password ត្រូវបានប្តូរដោយជោគជ័យ!']);
}
}