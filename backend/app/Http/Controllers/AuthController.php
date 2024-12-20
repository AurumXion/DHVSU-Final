<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\ValidIDs;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Check if the 'user_type' is not set or is invalid
        if (!$request->has('user_type') || !in_array($request->user_type, ['S', 'T'])) {
            return response()->json(['errors' => [
                'user_type' => ['The user_type field is required and must be either "S" or "T".']
            ]], 400);
        }

        // Check if the user exists in Valid_IDs
        $isValidId = ValidIDs::where('id', $request->id)
            ->where('user_type', $request->user_type)
            ->exists();

        if (!$isValidId) {
            return response()->json([
                'errors' => [
                    'id_or_type' => ['Invalid ID or user type.']
                ]
            ], 400);
        }

        $field = null;
        $user_creds = null;

        if ($request->user_type === 'S') {
            $field = $request->validate([
                'id' => 'required|integer|unique:users',
                'email' => 'required|string|unique:users|ends_with:@dhvsu.edu.ph',
                'birthday' => 'nullable|string',
                'gender' => 'sometimes|nullable|string|in:M,F,Others',
                'user_type' => 'required|string|in:S,T',
                'password' => 'required|string|min:8|confirmed',
                'fn' => 'required|string|max:100',
                'ln' => 'required|string|max:100',
                'section_id' => 'nullable|integer',
            ], [
                'email.ends_with' => 'Use your dhvsu email', // Custom error message for email domain validation
            ]);

            $user_creds = Student::create([
                'id' => $request->id,
                'gender' => $request->gender,
                'fn' => $request->fn,
                'ln' => $request->ln,
                'section_id' => 2,
                // 'section_id' => $request->section_id,
                'grades' => [],
                'activities' => []
            ]);
        } else if ($request->user_type === 'T') {
            $field = $request->validate([
                'id' => 'required|integer|unique:users',
                'email' => 'required|string|unique:users|ends_with:@dhvsu.edu.ph',
                'birthday' => 'nullable|string',
                'gender' => 'sometimes|nullable|string|in:M,F,Others',
                'user_type' => 'required|string|in:S,T',
                'password' => 'required|string|min:8|confirmed',
                'fn' => 'required|string|max:100',
                'ln' => 'required|string|max:100',
            ], [
                'email.ends_with' => 'Use your dhvsu email', // Custom error message for email domain validation
            ]);

            $user_creds = Teacher::create([
                'id' => $request->id,
                'gender' => $request->gender,
                'fn' => $request->fn,
                'ln' => $request->ln,
                'isAdmin' => false,
                'subjects' => []
            ]);
        }

        if ($field === null || $user_creds === null) {
            return response()->json([
                'errors' => [
                    'input_validation' => ['Error with validating the inputs']
                ]
            ], 400);
        }

        // Create the User record
        $user = User::create([
            'id' => $request->id,
            'email' => $request->email,
            'user_type' => $request->user_type,
            'password' => Hash::make($request->password)
        ]);

        // Create the API token
        $token = $user->createToken("{$user_creds->fn} {$user_creds->ln}");

        return [
            'user' => $user,
            'user_creds' => $user_creds,
            'token' => $token
        ];
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required'
        ], [
            'email.exists' => 'The provided credentials are incorrect', // Custom message for invalid email
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['errors' => [
                'password' => ['The provided credentials are incorrect']
            ]], 400);
        }

        $UserClass = $user->user_type === 'S'
            ? Student::class
            : ($user->user_type === 'T'
                ? Teacher::class
                : null);

        if ($UserClass) {
            $user_creds = $UserClass::find($user->id);

            if (!$user_creds) {
                return response()->json(['errors' => [
                    'email_or_password' => ['The provided credentials are incorrect']
                ]], 404);
            }

            $token = $user->createToken("{$user_creds->fn} {$user_creds->ln}");

            return [
                'user' => $user,
                'user_creds' => $user_creds,
                'token' => $token,
            ];
        }

        return response()->json(['errors' => [
            'email_or_password' => ['The provided credentials are incorrect']
        ]], 400);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return ['message' => 'You are logged out'];
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users',
            'password' => 'required|string',
            'password_confirmation' => 'required|string'
        ], [
            'email.exists' => 'The provided credentials are incorrect', // Custom message for invalid email
        ]);

        $user = User::where('email', $request->email)->first();

        $user->password = Hash::make($request->password);
        $user->save();

        return ['message' => 'password reset successful'];
    }

    public function getUser(User $user)
    {
        return ['user' => $user];
    }
}
