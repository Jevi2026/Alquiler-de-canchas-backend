<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashierController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => ['sometimes', 'date_format:Y-m-d']])['date'] ?? now()->toDateString();
        $transactions = CashTransaction::with(['user:id,name', 'reservation:id,invoice_number'])
            ->whereDate('occurred_at', $date)
            ->orderByDesc('occurred_at')
            ->get();

        $income = (float) $transactions->where('type', 'income')->sum('amount');
        $expenses = (float) $transactions->where('type', 'expense')->sum('amount');

        return response()->json([
            'date' => $date,
            'income' => number_format($income, 2, '.', ''),
            'expenses' => number_format($expenses, 2, '.', ''),
            'net' => number_format($income - $expenses, 2, '.', ''),
            // Keep this as a JSON object even when there are no income rows;
            // Flutter reads by_method as a map, while an empty PHP array
            // would be serialized as a JSON list.
            'by_method' => (object) $transactions->where('type', 'income')->groupBy('payment_method')
                ->map(fn ($rows) => number_format((float) $rows->sum('amount'), 2, '.', ''))
                ->all(),
            'transactions' => $transactions,
        ]);
    }

    public function pendingReservations(): JsonResponse
    {
        return response()->json([
            'reservations' => Reservation::with(['user:id,name,email', 'cancha:id,name'])
                ->where('status', 'pending')
                ->where('payment_status', 'pending')
                ->orderBy('starts_at')
                ->get(),
        ]);
    }

    public function pay(Request $request, Reservation $reservation): JsonResponse
    {
        $data = $request->validate([
            'payment_method' => ['sometimes', 'in:cash,card,transfer'],
        ]);

        $transaction = DB::transaction(function () use ($request, $reservation, $data) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || $locked->payment_status !== 'pending') {
                throw ValidationException::withMessages(['reservation' => 'La reserva ya fue cobrada o cancelada.']);
            }

            $method = $data['payment_method'] ?? $locked->payment_method;
            $locked->update(['payment_method' => $method, 'payment_status' => 'paid', 'status' => 'confirmed']);

            return CashTransaction::create([
                'user_id' => $request->user()->id,
                'reservation_id' => $locked->id,
                'type' => 'income',
                'payment_method' => $method,
                'amount' => $locked->total,
                'description' => 'Cobro de factura '.$locked->invoice_number,
                'occurred_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Pago registrado en caja.', 'transaction' => $transaction], 201);
    }

    public function expense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'payment_method' => ['required', 'in:cash,card,transfer'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $transaction = CashTransaction::create([
            'user_id' => $request->user()->id,
            'type' => 'expense',
            'payment_method' => $data['payment_method'],
            'amount' => $data['amount'],
            'description' => $data['description'],
            'occurred_at' => now(),
        ]);

        return response()->json(['message' => 'Gasto registrado en caja.', 'transaction' => $transaction], 201);
    }
}
