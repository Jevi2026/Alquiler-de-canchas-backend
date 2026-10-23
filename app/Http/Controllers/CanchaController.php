<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CanchaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->validate(['date' => ['sometimes', 'date_format:Y-m-d']])['date'] ?? null;
        $query = Cancha::where('is_active', true)->orderBy('name');
        if ($date) {
            $query->with(['reservations' => fn ($reservations) => $reservations
                ->whereDate('starts_at', $date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('starts_at')
                ->select(['id', 'cancha_id', 'starts_at', 'ends_at'])]);
        }
        return response()->json(['canchas' => $query->get()]);
    }

    public function photo(Cancha $cancha): StreamedResponse
    {
        abort_unless($cancha->photo_path && Storage::disk('public')->exists($cancha->photo_path), 404);

        return Storage::disk('public')->response($cancha->photo_path, basename($cancha->photo_path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('canchas', 'public');
        }
        $cancha = Cancha::create($data);
        return response()->json(['message' => 'Cancha creada.', 'cancha' => $cancha], 201);
    }

    public function update(Request $request, Cancha $cancha): JsonResponse
    {
        $data = $this->validated($request, true);
        if ($request->hasFile('photo')) {
            if ($cancha->photo_path) {
                Storage::disk('public')->delete($cancha->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('canchas', 'public');
        }
        $cancha->update($data);
        return response()->json(['message' => 'Cancha actualizada.', 'cancha' => $cancha->fresh()]);
    }

    public function destroy(Cancha $cancha): JsonResponse
    {
        if ($cancha->reservations()->whereIn('status', ['pending', 'confirmed'])->exists()) {
            return response()->json(['message' => 'No se puede eliminar una cancha con reservas vigentes.'], 409);
        }
        if ($cancha->photo_path) {
            Storage::disk('public')->delete($cancha->photo_path);
        }
        $cancha->delete();
        return response()->json(['message' => 'Cancha eliminada.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';
        $opensAt = $request->input('opens_at', $request->route('cancha')?->opens_at);
        $closesAt = $request->input('closes_at', $request->route('cancha')?->closes_at);
        $data = $request->validate([
            'name' => [$required, 'string', 'max:120', 'unique:canchas,name,'.($request->route('cancha')?->id ?? 'NULL')],
            'surface' => ['sometimes', 'nullable', 'string', 'max:60'],
            'price_per_hour' => [$required, 'numeric', 'gt:0', 'max:99999999.99'],
            'opens_at' => [$required, 'date_format:H:i'],
            'closes_at' => [$required, 'date_format:H:i'],
            'is_active' => ['sometimes', 'boolean'],
            'photo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($opensAt && $closesAt && $closesAt <= $opensAt) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'closes_at' => 'La hora de cierre debe ser posterior a la hora de apertura.',
            ]);
        }
        return $data;
    }
}
