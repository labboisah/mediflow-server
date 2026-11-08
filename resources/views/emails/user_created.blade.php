<div style="font-family:Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; color:#222">
    <h2>Hello {{ $user->name }},</h2>

    <p>Your account for {{ config('app.name') }} has been created by an administrator.</p>

    <p>
        <strong>Login email:</strong> {{ $user->email }}<br>
        <strong>Temporary password:</strong> {{ $password }}
    </p>

    <p>Please login and change your password as soon as possible.</p>

    <p>Login here: <a href="{{ url('/login') }}">{{ url('/login') }}</a></p>

    <p>Regards,<br>{{ config('app.name') }} Team</p>
</div>
