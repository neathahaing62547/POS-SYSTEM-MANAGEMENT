<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Nette\Schema\Message;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::with('roles')->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'status' => ['nullable', 'string', 'max:255'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password_hash' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? 'active',
        ]);
        if (array_key_exists('role_ids', $validated)) {
            $user->roles()->sync($validated['role_ids'] ?? []);
        }
        return response()->json([
            'Message' => 'User Crate Successfully',
            $user->load('roles')
        ],201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('roles'));
    }
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => ['nullable', 'in:active,inactive'],
            'role_ids' => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $user->fill([
            'username' => $validated['username'] ?? $user->username,
            'full_name' => array_key_exists('full_name', $validated) ? $validated['full_name'] : $user->full_name,
            'email' => $validated['email'] ?? $user->email,
            'phone' => array_key_exists('phone', $validated) ? $validated['phone'] : $user->phone,
            'status' => array_key_exists('status', $validated) ? $validated['status'] : $user->status,
        ]);

        if (!empty($validated['password'])) {
            $user->password_hash = Hash::make($validated['password']);
        }
        $user->save();

        if (array_key_exists('role_ids', $validated)) {
            $user->roles()->sync($validated['role_ids'] ?? []);
        }
        return response()->json($user->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'You cannot delete yourself.'
            ], 403);
        }
        $user->delete();
        
        return response()->json([
            'message' => 'User deleted successfully.'
        ], 200);
    }
}
