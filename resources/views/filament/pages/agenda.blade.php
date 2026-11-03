<x-filament-panels::page>
    <style>
        .agenda-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: flex-end; }
        .agenda-field { display: flex; flex-direction: column; gap: .25rem; font-size: .8125rem; }
        .agenda-input { border: 1px solid rgba(107, 114, 128, .35); border-radius: .5rem; padding: .45rem .6rem; background: transparent; color: inherit; }
        .agenda-heading { margin-top: 1.25rem; font-size: 1.05rem; font-weight: 600; text-transform: capitalize; }
        .agenda-table { width: 100%; border-collapse: collapse; min-width: 680px; }
        .agenda-table th { text-align: left; font-size: .75rem; font-weight: 600; padding: .5rem; border-bottom: 2px solid rgba(107, 114, 128, .3); white-space: nowrap; }
        .agenda-table td { vertical-align: top; border: 1px solid rgba(107, 114, 128, .18); padding: .35rem; min-width: 170px; }
        .agenda-time { font-size: .75rem; font-weight: 600; color: rgba(107, 114, 128, 1); white-space: nowrap; width: 58px; }
        .agenda-card { border-left: 4px solid #14b8a6; background: rgba(20, 184, 166, .08); border-radius: .375rem; padding: .35rem .45rem; margin-bottom: .35rem; }
        .agenda-card-name { font-weight: 600; font-size: .8125rem; }
        .agenda-card-meta { font-size: .72rem; color: rgba(107, 114, 128, 1); }
        .agenda-scroll { overflow-x: auto; margin-top: .5rem; }
        .agenda-empty { margin-top: .75rem; font-size: .875rem; color: rgba(107, 114, 128, 1); }
        .agenda-alert { margin-top: .75rem; border-radius: .5rem; padding: .65rem .8rem; background: rgba(245, 158, 11, .12); font-size: .875rem; }
    </style>

    <div class="agenda-toolbar">
        <label class="agenda-field">
            <span>Fecha</span>
            <input type="date" wire:model.live="date" class="agenda-input">
        </label>

        <label class="agenda-field">
            <span>Vista</span>
            <select wire:model.live="viewMode" class="agenda-input">
                <option value="day">Día</option>
                <option value="week">Semana</option>
            </select>
        </label>

        <label class="agenda-field">
            <span>Veterinario</span>
            <select wire:model.live="vetId" class="agenda-input">
                <option value="">Todos</option>
                @foreach ($filterVets as $filterVet)
                    <option value="{{ $filterVet->getKey() }}">{{ $filterVet->name }}</option>
                @endforeach
            </select>
        </label>

        <x-filament::button wire:click="previous" color="gray" size="sm">‹ Anterior</x-filament::button>
        <x-filament::button wire:click="goToToday" color="gray" size="sm">Hoy</x-filament::button>
        <x-filament::button wire:click="next" color="gray" size="sm">Siguiente ›</x-filament::button>
    </div>

    <div class="agenda-heading">{{ $displayDate }}</div>

    @if ($holiday)
        <div class="agenda-alert">Feriado: {{ $holiday['name'] }}. No se agendan citas este día.</div>
    @endif

    @if ($vets->isEmpty())
        <div class="agenda-empty">No hay veterinarios activos para mostrar.</div>
    @elseif ($isWeek)
        <div class="agenda-scroll">
            <table class="agenda-table">
                <thead>
                    <tr>
                        <th>Veterinario</th>
                        @foreach ($weekDays as $day)
                            <th>
                                {{ $day['label'] }}
                                @if ($day['holiday'])
                                    <div class="agenda-card-meta">{{ $day['holiday']['name'] }}</div>
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vets as $vet)
                        <tr>
                            <td class="agenda-time">{{ $vet->name }}</td>
                            @foreach ($weekDays as $day)
                                <td>
                                    @foreach ($weekGrid[$vet->getKey()][$day['date']->format('Y-m-d')] ?? [] as $appointment)
                                        <div class="agenda-card">
                                            <div class="agenda-card-name">{{ $appointment->starts_at->format('H:i') }} · {{ $appointment->patient->name }}</div>
                                            <div class="agenda-card-meta">{{ $appointment->type->getLabel() }}</div>
                                            <div class="agenda-card-meta">{{ $appointment->owner->full_name }}</div>
                                            <a href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]) }}" class="agenda-card-meta">Editar</a>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($isClosed)
        <div class="agenda-empty">La clínica no atiende este día según el horario configurado.</div>
    @else
        <div class="agenda-scroll">
            <table class="agenda-table">
                <thead>
                    <tr>
                        <th>Hora</th>
                        @foreach ($vets as $vet)
                            <th>{{ $vet->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($timeSlots as $index => $slot)
                        <tr>
                            <td class="agenda-time">{{ $slot->format('H:i') }}</td>
                            @foreach ($vets as $vet)
                                <td>
                                    @foreach ($grid[$vet->getKey()][$index] ?? [] as $appointment)
                                        <div class="agenda-card">
                                            <div class="agenda-card-name">{{ $appointment->patient->name }}</div>
                                            <div class="agenda-card-meta">{{ $appointment->starts_at->format('H:i') }}–{{ $appointment->ends_at->format('H:i') }}</div>
                                            <div class="agenda-card-meta">{{ $appointment->type->getLabel() }}</div>
                                            <div class="agenda-card-meta">{{ $appointment->owner->full_name }}</div>
                                            <x-filament::badge :color="$appointment->status->getColor()" size="sm">
                                                {{ $appointment->status->getLabel() }}
                                            </x-filament::badge>
                                            <a href="{{ \App\Filament\Resources\Appointments\AppointmentResource::getUrl('edit', ['record' => $appointment]) }}" class="agenda-card-meta">Editar</a>
                                        </div>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
