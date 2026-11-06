<?php

namespace App\Http\Controllers;

use App\Services\Format;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * Export daily sales to CSV.
     */
    public function sales(Request $request): StreamedResponse
    {
        Gate::authorize('reports.view');

        [$from, $until] = $this->range($request);
        $service = app(ReportService::class);
        $rows = $service->salesByDay($from, $until);
        $totals = $service->salesTotals($rows);

        $data = $rows->map(fn (array $row): array => [
            $row['date'],
            (string) $row['receipts'],
            $this->decimal($row['subtotal_cents']),
            $this->decimal($row['discount_cents']),
            $this->decimal($row['tax_cents']),
            $this->decimal($row['total_cents']),
            $this->decimal($row['paid_cents']),
            $this->decimal($row['balance_cents']),
        ])->all();

        $data[] = [
            'Total',
            (string) $totals['receipts'],
            $this->decimal($totals['subtotal_cents']),
            $this->decimal($totals['discount_cents']),
            $this->decimal($totals['tax_cents']),
            $this->decimal($totals['total_cents']),
            $this->decimal($totals['paid_cents']),
            $this->decimal($totals['balance_cents']),
        ];

        return $this->download(
            filename: "ventas-{$from->toDateString()}-{$until->toDateString()}.csv",
            headers: ['Fecha', 'Recibos', 'Subtotal', 'Descuento', 'IVA', 'Total', 'Cobrado', 'Saldo'],
            rows: $data,
        );
    }

    /**
     * Export the appointment summary to CSV.
     */
    public function appointments(Request $request): StreamedResponse
    {
        Gate::authorize('reports.view');

        [$from, $until] = $this->range($request);
        $vetId = $request->filled('vet') ? (int) $request->input('vet') : null;
        $service = app(ReportService::class);

        $data = [];

        foreach ($service->appointmentsByStatus($from, $until, $vetId) as $row) {
            $data[] = ['Estado', $row['label'], (string) $row['appointments']];
        }

        foreach ($service->appointmentsByVet($from, $until, $vetId) as $row) {
            $data[] = ['Veterinario', $row['vet'], (string) $row['appointments']];
        }

        return $this->download(
            filename: "citas-{$from->toDateString()}-{$until->toDateString()}.csv",
            headers: ['Sección', 'Clave', 'Citas'],
            rows: $data,
        );
    }

    /**
     * Export low stock and expiring batches to CSV.
     */
    public function inventory(): StreamedResponse
    {
        Gate::authorize('reports.view');

        $service = app(ReportService::class);
        $data = [];

        foreach ($service->lowStockProducts() as $product) {
            $data[] = [
                'Stock bajo',
                $product->name,
                (string) (data_get($product, 'category.name') ?? 'Sin categoría'),
                '-',
                Format::quantity((float) $product->stock_quantity).' '.$product->unit->abbreviation(),
                Format::quantity((float) $product->min_stock).' '.$product->unit->abbreviation(),
            ];
        }

        foreach ($service->expiringBatches() as $batch) {
            $data[] = [
                'Lote por vencer',
                (string) (data_get($batch, 'product.name') ?? 'Producto eliminado'),
                $batch->batch_number,
                $batch->expires_at?->format('d/m/Y') ?? '-',
                $batch->formattedRemaining(),
                $batch->expiryStatus(),
            ];
        }

        return $this->download(
            filename: 'inventario-'.today()->toDateString().'.csv',
            headers: ['Reporte', 'Producto', 'Categoría o lote', 'Caducidad', 'Cantidad', 'Detalle'],
            rows: $data,
        );
    }

    /**
     * Resolve the requested date range, defaulting to the current month.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $from = $request->date('from') ?? now()->startOfMonth();
        $until = $request->date('until') ?? today();

        $from = $from->copy()->startOfDay();
        $until = $until->copy()->endOfDay();

        if ($from->greaterThan($until)) {
            [$from, $until] = [$until->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $until];
    }

    /**
     * Convert cents to a CSV-friendly decimal amount.
     */
    private function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Stream a UTF-8 CSV file with an Excel-friendly BOM.
     *
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function download(string $filename, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
