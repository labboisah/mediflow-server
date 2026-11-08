<?php

namespace App\Http\Controllers;

use App\Models\Labour;
use App\Models\LabourProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LabourProgressController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(Labour $labour)
    {
        $labour->load('patient.demographic', 'progressRecords.recordedBy');

        if ($labour->patient->demographic->gender !== 'Female') {
            return redirect()->route('midwife.labour.index')
                ->with('error', 'Labour progress entries can only be viewed for female patients.');
        }

        $progressRecords = $labour->progressRecords()->orderByDesc('recorded_at')->get();

        return view('midwife.labour-progress.index', compact('labour', 'progressRecords'));
    }

    public function create(Labour $labour)
    {
        if ($labour->patient->demographic->gender !== 'Female') {
            return redirect()->route('midwife.labour.index')
                ->with('error', 'Labour progress can only be added for female patients.');
        }

        return view('midwife.labour-progress.create', compact('labour'));
    }

    public function store(Request $request, Labour $labour)
    {
        $validated = $request->validate([
            'recorded_at' => 'nullable|date',
            'contraction_frequency' => 'nullable|integer|min:0|max:20',
            'contraction_duration' => 'nullable|integer|min:0|max:300',
            'contraction_intensity' => 'nullable|in:mild,moderate,strong',
            'cervical_dilation' => 'nullable|numeric|min:0|max:10',
            'cervical_effacement' => 'nullable|integer|min:0|max:100',
            'cervical_consistency' => 'nullable|in:firm,medium,soft',
            'cervical_position' => 'nullable|in:posterior,middle,anterior',
            'fetal_station' => 'nullable|integer|min:-5|max:5',
            'fetal_position' => 'nullable|in:cephalic,breech,oblique,transverse',
            'uterine_tone' => 'nullable|string|max:255',
            'uterine_tenderness' => 'nullable|string|max:255',
            'vaginal_examination_findings' => 'nullable|string|max:1000',
            'fetal_heart_rate' => 'nullable|integer|min:100|max:190',
            'fetal_heart_variability' => 'nullable|in:absent,minimal,moderate,marked',
            'fetal_movements' => 'nullable|string|max:500',
            'meconium_stained_liquor' => 'nullable|boolean',
            'blood_pressure' => 'nullable|string|max:20',
            'pulse_rate' => 'nullable|integer|min:40|max:180',
            'temperature' => 'nullable|numeric|min:34|max:42',
            'maternal_pain_relief' => 'nullable|string|max:500',
            'coping_mechanisms' => 'nullable|string|max:500',
            'interventions' => 'nullable|string|max:1000',
            'medications_given' => 'nullable|string|max:1000',
            'observations_and_notes' => 'nullable|string|max:2000',
        ]);

        $validated['recorded_at'] = $validated['recorded_at'] ?? now();
        $validated['recorded_by'] = Auth::id();

        $progress = $labour->progressRecords()->create($validated);

        

        return redirect()->route('midwife.labour.progress.show', [$labour, $progress])
            ->with('success', 'Labour progress entry created successfully.');
    }

    public function show(LabourProgress $labourProgress)
    {
        $labourProgress->load('labour.patient.demographic', 'recordedBy');

        return view('midwife.labour-progress.show', ['progress' => $labourProgress]);
    }

    public function edit(LabourProgress $labourProgress)
    {
        $labourProgress->load('labour.patient.demographic');

        return view('midwife.labour-progress.edit', ['progress' => $labourProgress]);
    }

    public function update(Request $request, LabourProgress $labourProgress)
    {
        $validated = $request->validate([
            'recorded_at' => 'nullable|date',
            'contraction_frequency' => 'nullable|integer|min:0|max:20',
            'contraction_duration' => 'nullable|integer|min:0|max:300',
            'contraction_intensity' => 'nullable|in:mild,moderate,strong',
            'cervical_dilation' => 'nullable|numeric|min:0|max:10',
            'cervical_effacement' => 'nullable|integer|min:0|max:100',
            'cervical_consistency' => 'nullable|in:firm,medium,soft',
            'cervical_position' => 'nullable|in:posterior,middle,anterior',
            'fetal_station' => 'nullable|integer|min:-5|max:5',
            'fetal_position' => 'nullable|in:cephalic,breech,oblique,transverse',
            'uterine_tone' => 'nullable|string|max:255',
            'uterine_tenderness' => 'nullable|string|max:255',
            'vaginal_examination_findings' => 'nullable|string|max:1000',
            'fetal_heart_rate' => 'nullable|integer|min:100|max:190',
            'fetal_heart_variability' => 'nullable|in:absent,minimal,moderate,marked',
            'fetal_movements' => 'nullable|string|max:500',
            'meconium_stained_liquor' => 'nullable|boolean',
            'blood_pressure' => 'nullable|string|max:20',
            'pulse_rate' => 'nullable|integer|min:40|max:180',
            'temperature' => 'nullable|numeric|min:34|max:42',
            'maternal_pain_relief' => 'nullable|string|max:500',
            'coping_mechanisms' => 'nullable|string|max:500',
            'interventions' => 'nullable|string|max:1000',
            'medications_given' => 'nullable|string|max:1000',
            'observations_and_notes' => 'nullable|string|max:2000',
        ]);

        $labourProgress->update($validated);


        return redirect()->route('midwife.labour.progress.show', [$labourProgress->labour, $labourProgress])
            ->with('success', 'Labour progress entry updated successfully.');
    }

    public function destroy(LabourProgress $labourProgress)
    {
        $labourProgress->delete();

        return redirect()->route('midwife.labour.progress.index', $labourProgress->labour_id)
            ->with('success', 'Labour progress entry deleted successfully.');
    }
}



