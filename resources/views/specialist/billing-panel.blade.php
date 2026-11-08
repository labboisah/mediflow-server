<x-ui.card title="Bill and payments">
<p>{{ $c->service_name }}: NGN {{ number_format($c->charge,2) }}</p>
@if(!$c->bill)
@if(auth()->user()->hasPermission('specialist_billing.create'))<form method="POST" action="{{ route('specialist.consultations.action',[$c,'bill']) }}">@csrf<x-ui.button type="submit">Create service bill</x-ui.button></form>@endif
@else
<p>{{ $c->bill->bill_number }} / {{ $c->bill->status }} / Balance NGN {{ number_format($c->bill->balance,2) }}</p>
@foreach($c->bill->payments as $payment)<p>{{ $payment->payment_id }} / {{ $payment->amount }} / {{ $payment->status }} <a class="text-med-primary underline" href="{{ route('specialist.print',[$c,'receipt',$payment->id]) }}">Receipt</a></p>@endforeach
@if(auth()->user()->hasPermission('specialist_payment.record') && $c->bill->balance>0)<form method="POST" action="{{ route('specialist.consultations.action',[$c,'pay']) }}" class="mt-4 grid gap-3 md:grid-cols-3">@csrf<input name="token" type="hidden" value="{{ old('token',(string)Str::uuid()) }}">
<x-ui.input name="amount" label="Amount received" type="number" step="0.01" min="0.01" max="{{ $c->bill->balance }}" required/>
<x-ui.select name="payment_method_id" label="Payment method">@foreach($methods as $method)<option value="{{ $method->id }}">{{ $method->name }}</option>@endforeach</x-ui.select>
<x-ui.input name="reference_number" label="Reference (optional)"/><x-ui.button type="submit">Record payment</x-ui.button></form>@endif
@endif
</x-ui.card>
