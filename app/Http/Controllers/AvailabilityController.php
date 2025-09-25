<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Availability;
use App\Models\AvailabilitySlot;
use App\Models\Doctor;

use Illuminate\Support\Carbon;


class AvailabilityController extends Controller
{
    public function create()
    {
        $doctor = auth()->user(); // assuming doctor is logged in

        return view('frontend.availability.create', compact('doctor'));
    }



public function store(Request $request)
{
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
        'slot_duration' => 'required|in:15,30,60',
        'days' => 'required|array',
        'slots' => 'required|array',
    ]);

    // ✅ Check 3-month limit
    if (Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) > 90) {
        return back()->withErrors(['end_date' => 'Date range cannot exceed 3 months']);
    }

    $requestedDays = array_map('ucfirst', $request->days); // Normalize casing
    $weekdays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
    $conflictingDays = [];

    // ✅ Query existing availabilities in the range
    $existingAvailabilities = Availability::where('doctor_id', auth()->id())
        ->where(function ($q) use ($request) {
            $q->whereBetween('start_date', [$request->start_date, $request->end_date])
              ->orWhereBetween('end_date', [$request->start_date, $request->end_date])
              ->orWhere(function ($q2) use ($request) {
                  $q2->where('start_date', '<=', $request->start_date)
                     ->where('end_date', '>=', $request->end_date);
              });
        })
        ->get();

    // ✅ Check weekday flags in existing availabilities
    foreach ($requestedDays as $day) {
        $dayKey = strtolower($day);

        foreach ($existingAvailabilities as $availability) {
            if ($availability->$dayKey) {
                $conflictingDays[$day] = [
                    'start' => $availability->start_date,
                    'end' => $availability->end_date,
                ];
                break; // No need to check further once conflict is found
            }
        }
    }

    // ✅ Return error if any conflicts found
    if (!empty($conflictingDays)) {
        $messages = [];
        foreach ($conflictingDays as $day => $range) {
            $messages[] = "$day already selected from {$range['start']} to {$range['end']}";
        }
        return back()->withErrors(['days' => implode(', ', $messages)]);
    }

    // ✅ Proceed with insert
    $availability = Availability::create([
        'doctor_id' => auth()->id(),
        'start_date' => $request->start_date,
        'end_date' => $request->end_date,
        'slot_duration' => $request->slot_duration,
        'monday'    => (int) in_array('Monday', $requestedDays),
        'tuesday'   => (int) in_array('Tuesday', $requestedDays),
        'wednesday' => (int) in_array('Wednesday', $requestedDays),
        'thursday'  => (int) in_array('Thursday', $requestedDays),
        'friday'    => (int) in_array('Friday', $requestedDays),
        'saturday'  => (int) in_array('Saturday', $requestedDays),
        'sunday'    => (int) in_array('Sunday', $requestedDays),
    ]);

    // ✅ Generate slots
    $startDate = Carbon::parse($request->start_date);
    $endDate = Carbon::parse($request->end_date);

    for ($date = $startDate; $date->lte($endDate); $date->addDay()) {
        $dayName = $date->format('l');
        if (!in_array($dayName, $requestedDays)) continue;
        if (!isset($request->slots[$dayName])) continue;

        foreach ($request->slots[$dayName] as $slot) {
            $start = Carbon::parse($slot['start']);
            $end = Carbon::parse($slot['end']);
            if ($start->gte($end)) continue;

            $duration = $request->slot_duration;
            $maxAppointments = floor($start->diffInMinutes($end) / $duration);

            AvailabilitySlot::create([
                'availability_id' => $availability->id,
                'slot_date' => $date->toDateString(),
                'day_of_week' => $dayName,
                'start_time' => $start->format('H:i'),
                'end_time' => $end->format('H:i'),
                'max_appointments' => $maxAppointments,
            ]);
        }
    }

    return redirect()->route('availability.show', auth()->id())
        ->with('success', implode(', ', $requestedDays) . ' added successfully.');
}
    public function show(Doctor $doctor)
    {
        $availabilities = $doctor->availabilities()->with('slots')->get();

        return view('frontend.availability.show', compact('doctor', 'availabilities'));
    }


    public function destroy(AvailabilitySlot $slot)
{
    $availability = $slot->availability;
    $day = strtolower($slot->day_of_week);
    $date = $slot->slot_date;

    $slot->delete();

    // ✅ Check if any slots remain for this day
    $remaining = AvailabilitySlot::where('availability_id', $availability->id)
        ->where('day_of_week', $slot->day_of_week)
        ->exists();

    if (!$remaining) {
        $availability->$day = 0;
        $availability->save();
    }

    return back()->with('success', "Slot deleted. {$slot->day_of_week} flag updated if no slots remain.");
}


}
