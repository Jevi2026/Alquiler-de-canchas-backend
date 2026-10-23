<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reservations = Reservation::with('cancha')
            ->where('user_id', $request->user()->id)->latest('starts_at')->get();
        return response()->json(['reservations' => $reservations]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cancha_id' => ['required', 'integer', 'exists:canchas,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,card,transfer'],
        ]);

        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);
        if (! $start->isAfter(now())) {
            throw ValidationException::withMessages(['starts_at' => 'La hora de inicio debe ser futura.']);
        }

        if (! $end->isAfter($start)) {
            throw ValidationException::withMessages(['ends_at' => 'La hora final debe ser posterior a la inicial.']);
        }

        if ($start->toDateString() !== $end->toDateString() || $start->format('s') !== '00' || $end->format('s') !== '00') {
            throw ValidationException::withMessages(['starts_at' => 'La reserva debe iniciar y terminar el mismo día, en minutos exactos.']);
        }

        $reservation = DB::transaction(function () use ($request, $data, $start, $end) {
            $cancha = Cancha::whereKey($data['cancha_id'])->where('is_active', true)->lockForUpdate()->first();
            if (! $cancha) {
                throw ValidationException::withMessages(['cancha_id' => 'La cancha no está disponible.']);
            }

            if ($start->format('H:i:s') < $cancha->opens_at || $end->format('H:i:s') > $cancha->closes_at) {
                throw ValidationException::withMessages(['starts_at' => 'El horario solicitado está fuera del horario de atención de la cancha.']);
            }

            $overlap = Reservation::where('cancha_id', $cancha->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_at' => 'Ese horario ya está reservado.']);
            }

            $hours = $start->diffInMinutes($end) / 60;
            return Reservation::create([
                'user_id' => $request->user()->id,
                'cancha_id' => $cancha->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'total' => $cancha->price_per_hour * $hours,
                'status' => 'pending',
                'invoice_number' => 'FAC-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(5))),
                'payment_method' => $data['payment_method'],
                'payment_status' => 'pending',
            ])->load('cancha');
        });

        return response()->json(['message' => 'Reserva creada; la factura queda pendiente de pago.', 'reservation' => $reservation], 201);
    }

    public function destroy(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(404);
        }

        DB::transaction(function () use ($reservation) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === 'paid') {
                throw ValidationException::withMessages(['reservation' => 'Contacta al administrador para cancelar una reserva pagada.']);
            }
            if ($locked->status !== 'pending' || ! $locked->starts_at->isAfter(now())) {
                throw ValidationException::withMessages(['reservation' => 'Solo se pueden cancelar reservas futuras pendientes.']);
            }
            $locked->update(['status' => 'cancelled']);
        });

        return response()->json(['message' => 'Reserva cancelada.']);
    }

    public function reschedule(Request $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->user_id !== $request->user()->id) {
            abort(404);
        }

        $data = $request->validate([
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
        ]);
        $start = Carbon::parse($data['starts_at']);
        $end = Carbon::parse($data['ends_at']);

        if (! $start->isAfter(now())) {
            throw ValidationException::withMessages(['starts_at' => 'La hora de inicio debe ser futura.']);
        }
        if (! $end->isAfter($start)) {
            throw ValidationException::withMessages(['ends_at' => 'La hora final debe ser posterior a la inicial.']);
        }
        if ($start->toDateString() !== $end->toDateString() || $start->format('s') !== '00' || $end->format('s') !== '00') {
            throw ValidationException::withMessages(['starts_at' => 'El horario debe ser el mismo día y en minutos exactos.']);
        }

        $updated = DB::transaction(function () use ($reservation, $start, $end) {
            $locked = Reservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || $locked->payment_status !== 'pending') {
                throw ValidationException::withMessages(['reservation' => 'Solo se pueden mover reservas pendientes de pago.']);
            }

            $cancha = Cancha::whereKey($locked->cancha_id)->where('is_active', true)->lockForUpdate()->first();
            if (! $cancha) {
                throw ValidationException::withMessages(['cancha_id' => 'La cancha no esta disponible.']);
            }
            if ($start->format('H:i:s') < $cancha->opens_at || $end->format('H:i:s') > $cancha->closes_at) {
                throw ValidationException::withMessages(['starts_at' => 'El horario solicitado esta fuera del horario de la cancha.']);
            }

            $overlap = Reservation::where('cancha_id', $cancha->id)
                ->where('id', '<>', $locked->id)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('starts_at', '<', $end)
                ->where('ends_at', '>', $start)
                ->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['starts_at' => 'Ese horario ya esta ocupado. Elige otro.']);
            }

            $locked->update([
                'starts_at' => $start,
                'ends_at' => $end,
                'total' => $cancha->price_per_hour * ($start->diffInMinutes($end) / 60),
            ]);

            return $locked->fresh()->load('cancha');
        });

        return response()->json(['message' => 'Horario de reserva actualizado.', 'reservation' => $updated]);
    }
}
