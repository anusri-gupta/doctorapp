<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- jQuery UI -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

@extends('frontend.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow">
    <h2 class="text-2xl font-bold mb-6">Define Your Availability</h2>
    @if ($errors->has('days'))
        <div class="text-red-600 text-sm mb-2">
            {{ $errors->first('days') }}
        </div>
    @endif
    @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('availability.store') }}" onsubmit="return validateTimeSlots()">
        @csrf

        <!-- Date Range -->
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block font-medium mb-1">Start Date</label>
                <input type="text" id="start_date" name="start_date" autocomplete="off" class="w-full border rounded px-2 py-1" required>
            </div>
            <div>
                <label class="block font-medium mb-1">End Date</label>
                <input type="text" id="end_date" name="end_date" autocomplete="off" class="w-full border rounded px-2 py-1" required>
            </div>
        </div>

        <!-- Days of Week -->
        <div class="mb-4">
            <label class="block font-medium mb-2">Days Available</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="days[]" value="{{ $day }}" onchange="toggleDaySlots('{{ $day }}')">
                        <span>{{ $day }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Slot Duration -->
        <div class="mb-4">
            <label class="block font-medium mb-1">Slot Duration</label>
            <select name="slot_duration" id="slot_duration" class="w-full border rounded px-2 py-1" required>
                <option value="15">15 mins</option>
                <option value="30">30 mins</option>
                <option value="60">60 mins</option>
            </select>
        </div>

        <!-- Time Slots -->
        <div id="slot-containers" class="space-y-6"></div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save Availability</button>
    </form>
</div>

<script>
$(function () {
    const today = new Date();

    $("#start_date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: today,
        onSelect: function (selectedDate) {
            const start = $.datepicker.parseDate("yy-mm-dd", selectedDate);
            const maxEndDate = new Date(start);
            maxEndDate.setDate(start.getDate() + 90);

            $("#end_date").datepicker("option", {
                minDate: start,
                maxDate: maxEndDate
            });
        }
    });

    $("#end_date").datepicker({
        dateFormat: "yy-mm-dd",
        minDate: today,
        maxDate: new Date(today.getTime() + 90 * 24 * 60 * 60 * 1000)
    });
});

// ------------------ Availability Slots ------------------

function toggleDaySlots(day) {
    const container = document.getElementById('slot-containers');
    const id = `${day.toLowerCase()}-slots`;

    if (document.querySelector(`input[value="${day}"]`).checked) {
        const slotDiv = document.createElement('div');
        slotDiv.id = id;
        slotDiv.innerHTML = `
            <label class="block font-medium mb-2">Time Slots for ${day}</label>
            <div class="space-y-2" id="${id}-list"></div>
            <button type="button" onclick="addSlot('${day}')" class="text-blue-600 hover:underline text-sm">+ Add Slot</button>
        `;
        container.appendChild(slotDiv);
        addSlot(day);
    } else {
        const existing = document.getElementById(id);
        if (existing) existing.remove();
    }
}

function buildTimeOptions() {
    let options = '<option value="">-- Select --</option>';
    for (let h = 0; h < 24; h++) {
        for (let m = 0; m < 60; m += 5) {
            const hh = h.toString().padStart(2, '0');
            const mm = m.toString().padStart(2, '0');
            options += `<option value="${hh}:${mm}">${hh}:${mm}</option>`;
        }
    }
    return options;
}

function addSlot(day) {
    const list = document.getElementById(`${day.toLowerCase()}-slots-list`);
    const index = list.children.length;

    const slot = document.createElement('div');
    slot.className = 'flex gap-2 items-center slot-row';
    slot.innerHTML = `
        <select name="slots[${day}][${index}][start]" class="start-time border px-2 py-1 rounded w-28" required>
            ${buildTimeOptions()}
        </select>
        <select name="slots[${day}][${index}][end]" class="end-time border px-2 py-1 rounded w-28" required>
            ${buildTimeOptions()}
        </select>
        <input type="number" name="slots[${day}][${index}][max_appointments]" class="max-appointments border px-2 py-1 rounded w-20 bg-gray-100 text-gray-700" readonly>
        <button type="button" class="remove-slot text-red-600 hover:underline text-sm">Remove</button>
    `;

    list.appendChild(slot);

    // Listen for changes
    slot.querySelectorAll('.start-time, .end-time').forEach(el => {
        el.addEventListener('change', () => calculateMaxAppointments(slot));
    });

    updateRemoveButtons(day);
}

function calculateMaxAppointments(slotRow) {
    const start = slotRow.querySelector('.start-time').value;
    const end = slotRow.querySelector('.end-time').value;
    const maxInput = slotRow.querySelector('.max-appointments');
    const duration = parseInt(document.getElementById('slot_duration').value) || 15;

    if (start && end) {
        const diff = toMinutes(end) - toMinutes(start);
        const max = diff > 0 ? Math.floor(diff / duration) : 0;
        maxInput.value = max;
    } else {
        maxInput.value = '';
    }
}

function toMinutes(timeStr) {
    const [h, m] = timeStr.split(':').map(Number);
    return h * 60 + m;
}

function updateRemoveButtons(day) {
    const list = document.getElementById(`${day.toLowerCase()}-slots-list`);
    const slots = list.querySelectorAll('.slot-row');

    slots.forEach((slot, i) => {
        const removeBtn = slot.querySelector('.remove-slot');
        if (removeBtn) {
            removeBtn.disabled = slots.length === 1;
            removeBtn.classList.toggle('opacity-0', slots.length === 1);
            removeBtn.classList.toggle('cursor-not-allowed', slots.length === 1);
            removeBtn.onclick = function () {
                slot.remove();
                updateRemoveButtons(day);
            };
        }
    });
} 

// ------------------ Validation ------------------

function validateTimeSlots() {
    let valid = true;
    const duration = parseInt(document.getElementById('slot_duration').value);
    const slotGroups = document.querySelectorAll('[id$="-slots-list"]');

    // Must select at least one day
    const checkedDays = document.querySelectorAll('input[name="days[]"]:checked');
    if (checkedDays.length === 0) {
        alert('Please select at least one day of availability.');
        return false;
    }

    slotGroups.forEach(group => {
        const slots = group.querySelectorAll('.slot-row');
        slots.forEach(slot => {
            const start = slot.querySelector('.start-time').value;
            const end = slot.querySelector('.end-time').value;

            if (start && end) {
                const diff = toMinutes(end) - toMinutes(start);
                if (diff < duration) {
                    valid = false;
                    slot.querySelector('.start-time').classList.add('border-red-500');
                    slot.querySelector('.end-time').classList.add('border-red-500');
                } else {
                    slot.querySelector('.start-time').classList.remove('border-red-500');
                    slot.querySelector('.end-time').classList.remove('border-red-500');
                }
            }
        });
    });

    if (!valid) {
        alert('Each slot must have an end time later than start time and greater than slot duration.');
    }
    return valid;
}
</script>
@endsection
