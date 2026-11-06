<x-filament-panels::page>
    <style>
        .report-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
        .report-field { display: flex; flex-direction: column; gap: .25rem; font-size: .8125rem; }
        .report-input { border: 1px solid rgba(107, 114, 128, .35); border-radius: .5rem; padding: .45rem .6rem; background: transparent; color: inherit; }
        .report-actions { display: flex; flex-wrap: wrap; gap: .5rem; margin-left: auto; }
        .report-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: .75rem; margin-top: 1.25rem; }
        .report-card { border: 1px solid rgba(107, 114, 128, .2); border-radius: .75rem; padding: .85rem 1rem; }
        .report-card-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; color: rgba(107, 114, 128, 1); }
        .report-card-value { font-size: 1.25rem; font-weight: 700; margin-top: .2rem; }
        .report-heading { margin-top: 1.75rem; font-size: 1.05rem; font-weight: 600; }
        .report-subheading { margin-top: .25rem; font-size: .8125rem; color: rgba(107, 114, 128, 1); }
        .report-scroll { overflow-x: auto; margin-top: .6rem; }
        .report-table { width: 100%; border-collapse: collapse; font-size: .85rem; }
        .report-table th { text-align: left; padding: .5rem; border-bottom: 2px solid rgba(107, 114, 128, .3); font-size: .75rem; text-transform: uppercase; letter-spacing: .03em; white-space: nowrap; }
        .report-table td { padding: .45rem .5rem; border-bottom: 1px solid rgba(107, 114, 128, .16); vertical-align: top; }
        .report-table tfoot td { font-weight: 700; border-top: 2px solid rgba(107, 114, 128, .3); border-bottom: 0; }
        .report-numeric { text-align: right; white-space: nowrap; }
        .report-empty { margin-top: .6rem; font-size: .85rem; color: rgba(107, 114, 128, 1); }
        .report-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        @media print {
            .report-toolbar, .report-actions { display: none !important; }
            .report-card { break-inside: avoid; }
            .report-heading { break-after: avoid; }
        }
    </style>

    <div class="report-toolbar">
        <label class="report-field">
            <span>Desde</span>
            <input type="date" wire:model.live="from" class="report-input">
        </label>

        <label class="report-field">
            <span>Hasta</span>
            <input type="date" wire:model.live="until" class="report-input">
        </label>

        <label class="report-field">
            <span>Veterinario</span>
            <select wire:model.live="vetId" class="report-input">
                <option value="">Todos</option>
                @foreach ($vets as $vet)
                    <option value="{{ $vet->getKey() }}">{{ $vet->name }}</option>
                @endforeach
            </select>
        </label>

        <div class="report-actions">
            <x-filament::button
                tag="a"
                :href="route('reports.sales.export', ['from' => $rangeStart->toDateString(), 'until' => $rangeEnd->toDateString()])"
                color="gray"
                size="sm"
            >
                Exportar ventas
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="route('reports.appointments.export', ['from' => $rangeStart->toDateString(), 'until' => $rangeEnd->toDateString(), 'vet' => $vetId])"
                color="gray"
                size="sm"
            >
                Exportar citas
            </x-filament::button>

            <x-filament::button
                tag="a"
                :href="route('reports.inventory.export')"
                color="gray"
                size="sm"
            >
                Exportar inventario
            </x-filament::button>

            <x-filament::button type="button" onclick="window.print()" color="primary" size="sm">
                Imprimir
            </x-filament::button>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card">
            <div class="report-card-label">Recibos emitidos</div>
            <div class="report-card-value">{{ $salesTotals['receipts'] }}</div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Total facturado</div>
            <div class="report-card-value">{{ \App\Services\Format::money($salesTotals['total_cents']) }}</div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Cobrado</div>
            <div class="report-card-value">{{ \App\Services\Format::money($salesTotals['paid_cents']) }}</div>
        </div>
        <div class="report-card">
            <div class="report-card-label">Saldo por cobrar</div>
            <div class="report-card-value">{{ \App\Services\Format::money($salesTotals['balance_cents']) }}</div>
        </div>
    </div>

    <h2 class="report-heading">Ventas por día</h2>
    <p class="report-subheading">
        Del {{ $rangeStart->format('d/m/Y') }} al {{ $rangeEnd->format('d/m/Y') }}. No incluye recibos anulados.
    </p>

    @if ($salesByDay->isEmpty())
        <div class="report-empty">No hay recibos emitidos en el período seleccionado.</div>
    @else
        <div class="report-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th class="report-numeric">Recibos</th>
                        <th class="report-numeric">Subtotal</th>
                        <th class="report-numeric">Descuento</th>
                        <th class="report-numeric">IVA</th>
                        <th class="report-numeric">Total</th>
                        <th class="report-numeric">Cobrado</th>
                        <th class="report-numeric">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($salesByDay as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="report-numeric">{{ $row['receipts'] }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['subtotal_cents']) }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['discount_cents']) }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['tax_cents']) }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['total_cents']) }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['paid_cents']) }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['balance_cents']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="report-numeric">{{ $salesTotals['receipts'] }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['subtotal_cents']) }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['discount_cents']) }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['tax_cents']) }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['total_cents']) }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['paid_cents']) }}</td>
                        <td class="report-numeric">{{ \App\Services\Format::money($salesTotals['balance_cents']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    <h2 class="report-heading">Pagos por método</h2>

    @if ($paymentsByMethod->isEmpty())
        <div class="report-empty">No hay pagos registrados en el período.</div>
    @else
        <div class="report-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Método</th>
                        <th class="report-numeric">Pagos</th>
                        <th class="report-numeric">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($paymentsByMethod as $row)
                        <tr>
                            <td>{{ $row['label'] }}</td>
                            <td class="report-numeric">{{ $row['payments'] }}</td>
                            <td class="report-numeric">{{ \App\Services\Format::money($row['amount_cents']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="report-columns">
        <div>
            <h2 class="report-heading">Citas por estado</h2>

            @if ($appointmentsByStatus->isEmpty())
                <div class="report-empty">No hay citas en el período.</div>
            @else
                <div class="report-scroll">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Estado</th>
                                <th class="report-numeric">Citas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointmentsByStatus as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td class="report-numeric">{{ $row['appointments'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div>
            <h2 class="report-heading">Citas por veterinario</h2>

            @if ($appointmentsByVet->isEmpty())
                <div class="report-empty">No hay citas en el período.</div>
            @else
                <div class="report-scroll">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Veterinario</th>
                                <th class="report-numeric">Citas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointmentsByVet as $row)
                                <tr>
                                    <td>{{ $row['vet'] }}</td>
                                    <td class="report-numeric">{{ $row['appointments'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <h2 class="report-heading">Stock bajo</h2>

    @if ($lowStockProducts->isEmpty())
        <div class="report-empty">Ningún producto está por debajo de su mínimo.</div>
    @else
        <div class="report-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th class="report-numeric">Stock</th>
                        <th class="report-numeric">Mínimo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lowStockProducts as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category?->name ?? 'Sin categoría' }}</td>
                            <td class="report-numeric">{{ $product->formattedStock() }}</td>
                            <td class="report-numeric">
                                {{ \App\Services\Format::quantity((float) $product->min_stock) }}
                                {{ $product->unit->abbreviation() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2 class="report-heading">Lotes por vencer</h2>
    <p class="report-subheading">Lotes con stock que caducan en los próximos 60 días o ya vencieron.</p>

    @if ($expiringBatches->isEmpty())
        <div class="report-empty">Ningún lote con stock está próximo a vencer.</div>
    @else
        <div class="report-scroll">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Lote</th>
                        <th>Caducidad</th>
                        <th class="report-numeric">Disponible</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($expiringBatches as $batch)
                        <tr>
                            <td>{{ $batch->product?->name ?? 'Producto eliminado' }}</td>
                            <td>{{ $batch->batch_number }}</td>
                            <td>
                                {{ $batch->expires_at?->format('d/m/Y') ?? '-' }}
                                <span class="report-subheading">{{ $batch->expiryStatus() }}</span>
                            </td>
                            <td class="report-numeric">{{ $batch->formattedRemaining() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
