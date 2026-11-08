@extends('layouts.modern')

@section('page-title', 'Record New Bill')

@section('content')

<div class="max-w-5xl mx-auto bg-white p-8 rounded-2xl shadow">

<form method="POST" action="{{ route('bills.store') }}">
@csrf

<!-- Patient -->
<div class="mb-6">
    <label class="block text-sm mb-2 font-medium">Patient Name</label>
    <input type="text" name="patient_name"
           class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">
</div>
<div class="mb-6">
    <label class="block text-sm mb-2 font-medium">Discount (₦)</label>
    <input type="number" step="0.01" name="discount"
           class="w-full px-4 py-3 border rounded-xl">
</div>

<!-- SERVICE SELECT -->
<div class="mb-6">

    <label class="block text-sm mb-2 font-medium">Select Service</label>

    <div class="flex gap-4">

        <select id="serviceSelect"
                class="flex-1 px-4 py-3 border rounded-xl focus:ring-2 focus:ring-accent focus:outline-none">

            <option value="">Choose Service</option>

            @foreach($services as $service)
                <option 
                    value="{{ $service->id }}"
                    data-price="{{ $service->price }}">
                    {{ $service->name }}
                </option>
            @endforeach

        </select>

        <button type="button"
                onclick="addService()"
                class="bg-accent text-white px-6 py-3 rounded-xl hover:bg-green-600 transition">
            Add
        </button>

    </div>

</div>

<!-- SELECTED SERVICES TABLE -->
<div class="mb-6">

    <table class="w-full text-sm border rounded-xl overflow-hidden" id="servicesTable">

        <thead class="bg-lightbg">
            <tr>
                <th class="py-3 px-4 text-left">Service</th>
                <th class="px-4">Price</th>
                <th class="px-4">Action</th>
            </tr>
        </thead>

        <tbody id="selectedServices">
            <!-- Dynamically added rows -->
        </tbody>

        <tfoot class="bg-gray-50">
            <tr>
                <td class="py-3 px-4 font-bold text-right">
                    Total:
                </td>
                <td class="px-4 font-bold text-primary" id="totalAmount">
                    ₦0.00
                </td>
                <td></td>
            </tr>
        </tfoot>

    </table>

</div>

<button type="submit"
        class="bg-primary text-white px-8 py-3 rounded-xl hover:bg-secondary transition">
    Save Bill
</button>

</form>

</div>


<script>

let selectedServices = [];
let total = 0;

function addService() {

    const select = document.getElementById('serviceSelect');
    const selectedOption = select.options[select.selectedIndex];

    if (!select.value) return;

    const serviceId = select.value;
    const serviceName = selectedOption.text;
    const price = parseFloat(selectedOption.dataset.price);

    // Prevent duplicate
    if (selectedServices.includes(serviceId)) {
        alert("Service already added.");
        return;
    }

    selectedServices.push(serviceId);
    total += price;

    // Add row
    const row = document.createElement('tr');
    row.classList.add("border-b");

    row.innerHTML = `
        <td class="py-3 px-4">${serviceName}</td>
        <td class="px-4">₦${price.toFixed(2)}</td>
        <td class="px-4">
            <button type="button"
                class="text-red-600"
                onclick="removeService('${serviceId}', ${price}, this)">
                Remove
            </button>
            <input type="hidden" name="services[]" value="${serviceId}">
        </td>
    `;

    document.getElementById('selectedServices').appendChild(row);

    updateTotal();

    select.value = "";
}

function removeService(id, price, button) {

    selectedServices = selectedServices.filter(item => item !== id);
    total -= price;

    button.closest('tr').remove();

    updateTotal();
}

function updateTotal() {
    document.getElementById('totalAmount').innerText = "₦" + total.toFixed(2);
}

</script>

@endsection
