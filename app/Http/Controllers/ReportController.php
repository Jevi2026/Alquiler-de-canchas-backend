<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Cancha;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const REPORTS = [
        'sales' => 'Ventas por fecha',
        'reservations' => 'Reservas por fecha',
        'courts' => 'Uso de canchas',
        'payments' => 'Metodos de pago',
        'cash' => 'Movimientos de caja',
    ];

    public function index()
    {
        return response()->json(['reports' => collect(self::REPORTS)->map(
            fn ($name, $type) => ['type' => $type, 'name' => $name]
        )->values()]);
    }

    public function download(Request $request, string $type)
    {
        abort_unless(array_key_exists($type, self::REPORTS), 404, 'Reporte no encontrado.');

        $filters = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($filters['from'])->startOfDay();
        $to = Carbon::parse($filters['to'])->endOfDay();

        $lines = match ($type) {
            'sales' => $this->sales($from, $to),
            'reservations' => $this->reservations($from, $to),
            'courts' => $this->courts($from, $to),
            'payments' => $this->payments($from, $to),
            'cash' => $this->cash($from, $to),
        };
        $pdf = $this->makePdf([
            'CANCHAS JLINARES',
            self::REPORTS[$type],
            'Periodo: '.$from->toDateString().' a '.$to->toDateString(),
            'Generado: '.now()->format('Y-m-d H:i'),
            str_repeat('-', 78),
            ...$lines,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$type.'-'.$from->toDateString().'-'.$to->toDateString().'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function sales(Carbon $from, Carbon $to): array
    {
        $rows = Reservation::whereBetween('starts_at', [$from, $to])->orderBy('starts_at')->get();
        $lines = $rows->map(fn ($r) => sprintf('%s  %s  %s  L %s  %s', $r->starts_at->format('Y-m-d'), $r->invoice_number, $r->status, $r->total, $r->payment_status))->all();
        $lines[] = 'Reservas: '.$rows->count().'    Total facturado: L '.number_format((float) $rows->sum('total'), 2);
        return $lines ?: ['No hay ventas en el periodo seleccionado.'];
    }

    private function reservations(Carbon $from, Carbon $to): array
    {
        $rows = Reservation::with(['cancha', 'user'])->whereBetween('starts_at', [$from, $to])->orderBy('starts_at')->get();
        $lines = $rows->map(fn ($r) => sprintf('%s  %-18s  %-18s  %-12s  %s', $r->starts_at->format('Y-m-d H:i'), $r->cancha?->name ?? 'Cancha', $r->user?->name ?? 'Cliente', $r->status, $r->invoice_number))->all();
        return $lines ?: ['No hay reservas en el periodo seleccionado.'];
    }

    private function courts(Carbon $from, Carbon $to): array
    {
        return Cancha::withCount(['reservations' => fn ($q) => $q->whereBetween('starts_at', [$from, $to])])
            ->orderBy('name')->get()->map(fn ($c) => sprintf('%-30s  Reservas: %d', $c->name, $c->reservations_count))->all() ?: ['No hay canchas registradas.'];
    }

    private function payments(Carbon $from, Carbon $to): array
    {
        $rows = Reservation::whereBetween('starts_at', [$from, $to])->selectRaw('payment_method, payment_status, COUNT(*) as count, SUM(total) as amount')->groupBy('payment_method', 'payment_status')->get();
        return $rows->map(fn ($r) => sprintf('%-14s  %-12s  Facturas: %d  Total: L %s', $r->payment_method, $r->payment_status, $r->count, number_format((float) $r->amount, 2)))->all() ?: ['No hay pagos en el periodo seleccionado.'];
    }

    private function cash(Carbon $from, Carbon $to): array
    {
        $rows = CashTransaction::whereBetween('occurred_at', [$from, $to])->orderBy('occurred_at')->get();
        $lines = $rows->map(fn ($r) => sprintf('%s  %-8s  %-14s  L %s  %s', $r->occurred_at->format('Y-m-d H:i'), $r->type, $r->payment_method, number_format((float) $r->amount, 2), $r->description))->all();
        $lines[] = 'Movimientos: '.$rows->count().'    Neto: L '.number_format((float) $rows->where('type', 'income')->sum('amount') - (float) $rows->where('type', 'expense')->sum('amount'), 2);
        return $lines ?: ['No hay movimientos de caja en el periodo seleccionado.'];
    }

    private function makePdf(array $lines): string
    {
        $pages = array_chunk($lines, 48);
        $pageIds = [];
        foreach (array_keys($pages) as $index) $pageIds[] = 4 + ($index * 2);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids ['.implode(' ', array_map(fn ($id) => $id.' 0 R', $pageIds)).'] /Count '.count($pages).' >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        foreach ($pages as $index => $pageLines) {
            $stream = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($pageLines as $line) {
                $safe = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line) ?: '';
                $safe = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $safe) ?? '';
                $safe = substr(str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $safe), 0, 110);
                $stream .= '('.$safe.") Tj\nT*\n";
            }
            $stream .= "ET\n";
            $pageId = $pageIds[$index];
            $contentId = $pageId + 1;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 842] /Resources << /Font << /F1 3 0 R >> >> /Contents '.$contentId.' 0 R >>';
            $objects[] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        }
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) $pdf .= sprintf("%010d 00000 n \n", $offset);
        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";
    }
}
