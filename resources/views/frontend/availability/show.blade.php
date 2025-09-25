@extends('frontend.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Your Availability</h2>
    <div class="mb-4">
        <a href="{{ route('availability.create') }}" 
           class="inline-block bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm px-4 py-2 rounded">
            ← Back to Form Page
        </a>
    </div>
    @if($availabilities->isEmpty())
        <p class="text-gray-600">No availability defined yet.</p>
    @else
        <table class="w-full text-sm border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border px-2 py-1 text-left">Date</th>
                    <th class="border px-2 py-1 text-left">Day</th>
                    <th class="border px-2 py-1 text-left">Start Time</th>
                    <th class="border px-2 py-1 text-left">End Time</th>
                    <th class="border px-2 py-1 text-left">Max Appointments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($availabilities as $availability)
                    @php
                        $groupedSlots = $availability->slots->sortBy(['slot_date', 'start_time'])->groupBy(function($slot) {
                            return $slot->slot_date . '|' . $slot->day_of_week;
                        });
                    @endphp

                    @foreach($groupedSlots as $key => $slots)
                        @php
                            [$date, $day] = explode('|', $key);
                        @endphp
                        @foreach($slots as $index => $slot)
                            <tr>
                                <td class="border px-2 py-1">
                                    @if($index === 0) {{ \Carbon\Carbon::parse($date)->format('d M Y') }} @endif
                                </td>
                                <td class="border px-2 py-1">
                                    @if($index === 0) {{ $day }} @endif
                                </td>
                                <td class="border px-2 py-1">{{ $slot->start_time }}</td>
                                <td class="border px-2 py-1">{{ $slot->end_time }}</td>
                                <td class="border px-2 py-1">{{ $slot->max_appointments }}</td>
                                <td class="border px-2 py-1">
                                <form action="{{ route('availability.slot.destroy', $slot->id) }}" method="POST" onsubmit="return confirm('Delete this slot?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                </form>
                            </td>

                            </tr>
                        @endforeach
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
