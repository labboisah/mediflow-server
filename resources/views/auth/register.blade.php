<x-guest-layout>
    <form method="POST" action="{{ route('register') }}" novalidate class="needs-validation">
        @csrf

        <h5 style="margin-bottom: 1.5rem; color: #27AE60; font-weight: 700;">
            <i class="bi bi-person-plus"></i> Create Staff Account
        </h5>

        <!-- Name -->
        <div class="mb-3">
            <label for="name" class="form-label">Full Name</label>
            <div class="input-group">
                <span class="input-group-text" style="background: #f8f9fa; border: 2px solid #e0e0e0;">
                    <i class="bi bi-person" style="color: #FF8C42;"></i>
                </span>
                <input 
                    id="name" 
                    class="form-control @error('name') is-invalid @enderror" 
                    type="text" 
                    name="name" 
                    value="{{ old('name') }}" 
                    required 
                    autofocus 
                    autocomplete="name"
                    placeholder="John Doe"
                />
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text" style="background: #f8f9fa; border: 2px solid #e0e0e0;">
                    <i class="bi bi-envelope" style="color: #FF8C42;"></i>
                </span>
                <input 
                    id="email" 
                    class="form-control @error('email') is-invalid @enderror" 
                    type="email" 
                    name="email" 
                    value="{{ old('email') }}" 
                    required 
                    autocomplete="username"
                    placeholder="john@hospital.com"
                />
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Password -->
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text" style="background: #f8f9fa; border: 2px solid #e0e0e0;">
                    <i class="bi bi-lock" style="color: #FF8C42;"></i>
                </span>
                <input 
                    id="password" 
                    class="form-control @error('password') is-invalid @enderror"
                    type="password"
                    name="password"
                    required 
                    autocomplete="new-password"
                    placeholder="Create a strong password"
                />
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <small class="form-text d-block mt-2" style="color: #666;">
                <i class="bi bi-info-circle"></i> At least 8 characters, mix of uppercase, lowercase, and numbers
            </small>
        </div>

        <!-- Confirm Password -->
        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <div class="input-group">
                <span class="input-group-text" style="background: #f8f9fa; border: 2px solid #e0e0e0;">
                    <i class="bi bi-lock-check" style="color: #FF8C42;"></i>
                </span>
                <input 
                    id="password_confirmation" 
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    type="password"
                    name="password_confirmation" 
                    required 
                    autocomplete="new-password"
                    placeholder="Confirm your password"
                />
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="mb-3">
            <div class="form-check">
                <input 
                    id="terms" 
                    type="checkbox" 
                    class="form-check-input" 
                    name="agree_terms"
                    required
                />
                <label class="form-check-label" for="terms">
                    I agree to Fatima Yahaya Hospital <strong><u>Terms of Service</u></strong>
                </label>
            </div>
        </div>

        <!-- Actions -->
        <div class="button-group">
            <button type="submit" class="btn btn-success" style="width: 100%;">
                <i class="bi bi-person-plus-fill"></i> Create Account
            </button>
        </div>

        <!-- Login Link -->
        <div class="auth-links">
            <p style="margin: 0; color: #666; font-size: 0.9rem;">
                Already have an account? 
                <a href="{{ route('login') }}">
                    <i class="bi bi-box-arrow-in-right"></i> Login Here
                </a>
            </p>
        </div>
    </form>
</x-guest-layout>
