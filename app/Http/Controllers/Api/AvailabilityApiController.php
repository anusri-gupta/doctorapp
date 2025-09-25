<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

use App\Models\Availability;
use App\Models\AvailabilitySlot;
use App\Models\Doctor;

class AvailabilityApiController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'slot_duration' => 'required|in:15,30,60',
            'days' => 'required|array',
            'slots' => 'required|array',
        ]);

        if (Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) > 90) {
            return response()->json([
                'error' => 'Date range cannot exceed 3 months'
            ], 422);
        }

        $requestedDays = array_map('ucfirst', $request->days);
        $weekdays = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
        $conflictingDays = [];

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

        foreach ($requestedDays as $day) {
            $dayKey = strtolower($day);
            foreach ($existingAvailabilities as $availability) {
                if ($availability->$dayKey) {
                    $conflictingDays[$day] = [
                        'start' => $availability->start_date,
                        'end' => $availability->end_date,
                    ];
                    break;
                }
            }
        }

        if (!empty($conflictingDays)) {
            $messages = [];
            foreach ($conflictingDays as $day => $range) {
                $messages[] = "$day already selected from {$range['start']} to {$range['end']}";
            }
            return response()->json([
                'error' => implode(', ', $messages)
            ], 409);
        }

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

        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        $slotsCreated = [];

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

                $conflict = AvailabilitySlot::where('slot_date', $date->toDateString())
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_time', [$start->format('H:i'), $end->format('H:i')])
                          ->orWhereBetween('end_time', [$start->format('H:i'), $end->format('H:i')])
                          ->orWhere(function ($q2) use ($start, $end) {
                              $q2->where('start_time', '<=', $start->format('H:i'))
                                 ->where('end_time', '>=', $end->format('H:i'));
                          });
                    })
                    ->whereHas('availability', function ($q) {
                        $q->where('doctor_id', auth()->id());
                    })
                    ->exists();

                if ($conflict) continue;

                $slotRecord = AvailabilitySlot::create([
                    'availability_id' => $availability->id,
                    'slot_date' => $date->toDateString(),
                    'day_of_week' => $dayName,
                    'start_time' => $start->format('H:i'),
                    'end_time' => $end->format('H:i'),
                    'max_appointments' => $maxAppointments,
                ]);

                $slotsCreated[] = $slotRecord;
            }
        }

        return response()->json([
            'message' => implode(', ', $requestedDays) . ' added successfully.',
            'availability' => $availability,
            'slots_created' => $slotsCreated
        ], 201);
    }

    public function show($id): JsonResponse
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'error' => 'Doctor not found'
            ], 404);
        }

        $availabilities = $doctor->availabilities()->with('slots')->get();

        return response()->json([
            'doctor' => $doctor,
            'availabilities' => $availabilities
        ]);
    }


}