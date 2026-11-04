<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tarjeta de vacunas · {{ $patient->name }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 2rem; font-family: "Segoe UI", Arial, sans-serif; color: #111827; background: #f3f4f6; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; border: 1px solid #d1d5db; border-radius: .75rem; padding: 2rem; }
        .toolbar { max-width: 900px; margin: 0 auto 1rem; display: flex; justify-content: flex-end; }
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
        .overdue { color: #b91c1c; font-weight: 600; }
        .empty { color: #6b7280; font-size: .85rem; padding: .5rem 0; }
        footer { margin-top: 1.75rem; display: flex; justify-content: space-between; gap: 1rem; font-size: .75rem; color: #6b7280; }
        @media print {
            body { padding: 0; background: #fff; }
            .card { border: 0; border-radius: 0; padding: 1.5rem; max-width: none; }
            .toolbar { display: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir tarjeta</button>
    </div>

    <main class="card">
        <header>
            <div>
                <div class="clinic-name">{{ \App\Models\Setting::get('clinic_name', config('clinic.name')) }}</div>
                <div class="muted">{{ \App\Models\Setting::get('clinic_address', config('clinic.address')) }}</div>
                <div class="muted">{{ \App\Models\Setting::get('clinic_phone', config('clinic.phone')) }}</div>
            </div>
            <div style="text-align: right;">
                <h1>Tarjeta de vacunas</h1>
                <div class="muted">Emitida el {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </header>

        <section class="grid">
            <div><strong>Paciente:</strong> {{ $patient->name }}</div>
            <div><strong>Tutor:</strong> {{ $patient->owner->full_name }}</div>
            <div><strong>Especie:</strong> {{ $patient->species?->name ?? '-' }}</div>
            <div><strong>Raza:</strong> {{ $patient->breed?->name ?? '-' }}</div>
            <div><strong>Sexo:</strong> {{ $patient->sex->getLabel() }}</div>
            <div><strong>Nacimiento:</strong> {{ $patient->birth_date?->format('d/m/Y') ?? '-' }}</div>
            <div><strong>Microchip:</strong> {{ $patient->microchip ?? '-' }}</div>
            <div><strong>Teléfono:</strong> {{ $patient->owner->phone ?? '-' }}</div>
        </section>

        <h2>Vacunas aplicadas</h2>
        @if ($vaccinations->isEmpty())
            <div class="empty">Sin vacunas registradas.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Vacuna</th>
                        <th>Lote</th>
                        <th>Próxima dosis</th>
                        <th>Veterinario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vaccinations as $vaccination)
                        <tr>
                            <td>{{ $vaccination->administered_at->format('d/m/Y') }}</td>
                            <td>{{ $vaccination->vaccine->name }}</td>
                            <td>{{ $vaccination->batch_number ?? '-' }}</td>
                            <td class="{{ $vaccination->isOverdue() ? 'overdue' : '' }}">
                                {{ $vaccination->next_due_at?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td>{{ $vaccination->vet?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <h2>Desparasitaciones</h2>
        @if ($dewormings->isEmpty())
            <div class="empty">Sin desparasitaciones registradas.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Producto</th>
                        <th>Dosis</th>
                        <th>Próxima dosis</th>
                        <th>Veterinario</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dewormings as $deworming)
                        <tr>
                            <td>{{ $deworming->administered_at->format('d/m/Y') }}</td>
                            <td>{{ $deworming->product_name }}</td>
                            <td>{{ $deworming->dose ?? '-' }}</td>
                            <td class="{{ $deworming->isOverdue() ? 'overdue' : '' }}">
                                {{ $deworming->next_due_at?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td>{{ $deworming->vet?->name ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <footer>
            <span>Este documento es un resumen interno del historial preventivo.</span>
            <span>{{ $patient->name }} · {{ $patient->owner->full_name }}</span>
        </footer>
    </main>
</body>
</html>
