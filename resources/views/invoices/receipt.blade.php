<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->number }} · Recibo</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem; font-family: "Segoe UI", Arial, sans-serif; color: #111827; background: #f3f4f6; }
        .receipt { max-width: 820px; margin: 0 auto; background: #fff; border: 1px solid #d1d5db; border-radius: .75rem; padding: 2rem; }
        .toolbar { max-width: 820px; margin: 0 auto 1rem; display: flex; justify-content: flex-end; }
        .toolbar button { border: 0; border-radius: .5rem; padding: .6rem 1rem; background: #0f766e; color: #fff; font-size: .9rem; cursor: pointer; }
        header { display: flex; justify-content: space-between; gap: 1rem; border-bottom: 2px solid #0f766e; padding-bottom: 1rem; margin-bottom: 1.25rem; }
        h1 { margin: 0; font-size: 1.35rem; }
        h2 { margin: 1.5rem 0 .5rem; font-size: 1rem; color: #0f766e; }
        .muted { color: #6b7280; font-size: .8rem; }
        .clinic-name { font-size: 1.1rem; font-weight: 700; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .35rem 1.5rem; font-size: .9rem; }
        .grid strong { color: #374151; }
        table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        th { text-align: left; background: #f0fdfa; color: #134e4a; padding: .5rem; border-bottom: 1px solid #99f6e4; }
        td { padding: .45rem .5rem; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        .numeric { text-align: right; white-space: nowrap; }
        .totals { margin-left: auto; width: min(100%, 320px); margin-top: 1rem; font-size: .9rem; }
        .totals div { display: flex; justify-content: space-between; padding: .25rem 0; }
        .totals .grand { border-top: 2px solid #0f766e; margin-top: .35rem; padding-top: .5rem; font-weight: 700; font-size: 1rem; }
        .badge { display: inline-block; border-radius: 999px; padding: .15rem .6rem; font-size: .75rem; font-weight: 600; }
        .badge-paid { background: #dcfce7; color: #166534; }
        .badge-open { background: #dbeafe; color: #1e40af; }
        .badge-void { background: #fee2e2; color: #991b1b; }
        .void-banner { border: 1px solid #fca5a5; background: #fef2f2; color: #991b1b; border-radius: .5rem; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .9rem; }
        .empty { color: #6b7280; font-size: .85rem; padding: .5rem 0; }
        footer { margin-top: 1.75rem; display: flex; justify-content: space-between; gap: 1rem; font-size: .75rem; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: .75rem; }
        @media print {
            body { padding: 0; background: #fff; }
            .receipt { border: 0; border-radius: 0; padding: 1.5rem; max-width: none; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir recibo</button>
    </div>

    <main class="receipt">
        @if ($invoice->status === \App\Enums\InvoiceStatus::Void)
            <div class="void-banner">
                <strong>Recibo anulado.</strong>
                Motivo: {{ $invoice->void_reason }}
                @if ($invoice->voided_at)
                    · {{ $invoice->voided_at->format('d/m/Y H:i') }}
                @endif
            </div>
        @endif

        <header>
            <div>
                <div class="clinic-name">{{ \App\Models\Setting::get('clinic_name', config('clinic.name')) }}</div>
                <div class="muted">{{ \App\Models\Setting::get('clinic_address', config('clinic.address')) }}</div>
                <div class="muted">{{ \App\Models\Setting::get('clinic_phone', config('clinic.phone')) }}</div>
                @if (\App\Models\Setting::get('clinic_email', config('clinic.email')))
                    <div class="muted">{{ \App\Models\Setting::get('clinic_email', config('clinic.email')) }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <h1>{{ $invoice->document_type->getLabel() }} {{ $invoice->number }}</h1>
                <div class="muted">Emitido el {{ $invoice->issued_at->format('d/m/Y H:i') }}</div>
                <div style="margin-top: .35rem;">
                    <span class="badge {{ $invoice->statusBadgeClass() }}">{{ $invoice->status->getLabel() }}</span>
                </div>
            </div>
        </header>

        <section class="grid">
            <div><strong>Tutor:</strong> {{ $invoice->owner->full_name }}</div>
            <div><strong>Teléfono:</strong> {{ $invoice->owner->phone ?? '-' }}</div>
            <div><strong>Paciente:</strong> {{ $invoice->patient?->name ?? 'Sin paciente' }}</div>
            <div><strong>Especie:</strong> {{ $invoice->patient?->species?->name ?? '-' }}</div>
            <div><strong>Documento:</strong> {{ $invoice->owner->document_number ?? '-' }}</div>
            <div><strong>Emitido por:</strong> {{ $invoice->createdBy?->name ?? '-' }}</div>
        </section>

        <h2>Conceptos</h2>
        @if ($invoice->items->isEmpty())
            <div class="empty">Sin conceptos registrados.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="numeric">Cant.</th>
                        <th class="numeric">Precio</th>
                        <th class="numeric">Desc.</th>
                        <th class="numeric">IVA</th>
                        <th class="numeric">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->description }}</td>
                            <td class="numeric">{{ $item->formattedQuantity() }}</td>
                            <td class="numeric">{{ $item->formattedUnitPrice() }}</td>
                            <td class="numeric">{{ $item->formattedDiscount() }}</td>
                            <td class="numeric">{{ $item->formattedTax() }}</td>
                            <td class="numeric">{{ $item->formattedLineTotal() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="totals">
            <div><span>Subtotal</span><span>{{ $invoice->formattedSubtotal() }}</span></div>
            <div><span>Descuento</span><span>{{ $invoice->formattedDiscount() }}</span></div>
            <div><span>IVA</span><span>{{ $invoice->formattedTax() }}</span></div>
            <div class="grand"><span>Total</span><span>{{ $invoice->formattedTotal() }}</span></div>
            <div><span>Pagado</span><span>{{ $invoice->formattedPaid() }}</span></div>
            <div><span>Saldo</span><span>{{ $invoice->formattedBalance() }}</span></div>
        </div>

        <h2>Pagos</h2>
        @if ($invoice->payments->isEmpty())
            <div class="empty">Sin pagos registrados.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Método</th>
                        <th>Referencia</th>
                        <th>Recibido por</th>
                        <th class="numeric">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->paid_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $payment->method->getLabel() }}</td>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td>{{ $payment->receivedBy?->name ?? '-' }}</td>
                            <td class="numeric">{{ $payment->formattedAmount() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($invoice->notes)
            <h2>Observaciones</h2>
            <div style="font-size: .85rem;">{{ $invoice->notes }}</div>
        @endif

        <footer>
            <span>Recibo interno. No constituye factura fiscal.</span>
            <span>{{ $invoice->number }}</span>
        </footer>
    </main>
</body>
</html>
