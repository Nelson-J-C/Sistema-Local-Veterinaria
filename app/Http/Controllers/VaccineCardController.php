<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Vaccination;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class VaccineCardController extends Controller
{
    /**
     * Render the printable vaccination card for a patient.
     */
    public function __invoke(Patient $patient): View
    {
        Gate::authorize('view', $patient);
        Gate::authorize('viewAny', Vaccination::class);

        $patient->load([
            'owner',
            'species',
            'breed',
            'vaccinations.vaccine',
            'vaccinations.vet',
            'dewormings.vet',
        ]);

        return view('vaccinations.card', [
            'patient' => $patient,
            'vaccinations' => $patient->vaccinations->sortByDesc('administered_at'),
            'dewormings' => $patient->dewormings->sortByDesc('administered_at'),
        ]);
    }
}
