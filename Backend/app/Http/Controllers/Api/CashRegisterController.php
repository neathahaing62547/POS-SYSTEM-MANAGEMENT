<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Models\UserShift;


class CashRegisterController extends Controller
{
    public function index()
    {
        $registers = CashRegister::with('store')
            ->latest()
            ->get();
        return response()->json($registers);
    }
    public function show($id)
    {
        $register = CashRegister::with([
            'store',
            'userShifts.user',
        ])->findOrFail($id);
        return response()->json($register);
    }
    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $register = CashRegister::create([
            'store_id' => $request->store_id,
            'name' => $request->name,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Cash register created successfully.',
            'cash_register' => $register,
        ], 201);
    }

    public function openShift(Request $request)
    {
        $validated = $request->validate([
            'store_id' => ['required', 'integer', Rule::exists('stores', 'id')->where('status', 'active')],
            'opening_cash' => 'required|numeric|min:0',
        ]);

        $shift = DB::transaction(function () use ($validated) {
            $user = User::query()
                ->whereKey(Auth::id())
                ->lockForUpdate()
                ->firstOrFail();

            $existingShift = UserShift::where('user_id', $user->id)
                ->where('status', 'open')
                ->first();

            if ($existingShift) {
                return null;
            }

            $store = Store::query()
                ->whereKey($validated['store_id'])
                ->where('status', 'active')
                ->lockForUpdate()
                ->firstOrFail();

            $register = $store->cashRegisters()
                ->where('status', 'active')
                ->orderBy('id')
                ->first();

            if (!$register) {
                $register = $store->cashRegisters()->create([
                    'name' => $store->name . ' Register',
                    'status' => 'active',
                ]);
            }

            return UserShift::create([
                'cash_register_id' => $register->id,
                'user_id' => $user->id,
                'opening_cash' => $validated['opening_cash'],
                'opened_at' => now(),
                'status' => 'open',
            ]);
        });

        if (!$shift) {
            return response()->json([
                'message' => 'You already have an open shift.'
            ], 422);
        }

        return response()->json([
            'message' => 'Shift opened successfully.',
            'shift' => $shift->load([
                'user',
                'cashRegister.store',
            ]),
        ], 201);
    }

    public function currentShift()
    {
        $shift = UserShift::with([
            'user',
            'cashRegister.store',
        ])
            ->where('user_id', Auth::id())
            ->where('status', 'open')
            ->first();
        return response()->json($shift);
    }

    public function closeShift(Request $request)
    {
        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
        ]);

        $shift = UserShift::where('user_id', Auth::id())
            ->where('status', 'open')
            ->firstOrFail();

        $shift->update([
            'closing_cash' => $request->closing_cash,
            'closed_at' => now(),
            'status' => 'closed',
        ]);

        return response()->json([
            'message' => 'Shift closed successfully.',
            'shift' => $shift->load([
                'user',
                'cashRegister.store',
            ]),
        ]);
    }
}
