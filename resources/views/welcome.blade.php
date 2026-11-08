<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Mediflow | Healthcare Software Solution</title>
    <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ asset('css/modern-fallback.css') }}" data-modern-fallback>

    @php
        $viteManifest = public_path('build/manifest.json');
        $hasModernAssets = file_exists($viteManifest)
            && str_contains(file_get_contents($viteManifest), 'resources/css/modern.css')
            && str_contains(file_get_contents($viteManifest), 'resources/js/modern.js');
    @endphp

    @if($hasModernAssets)
        @vite(['resources/css/modern.css', 'resources/js/modern.js'])
    @else
        <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.css') }}">
        <link rel="stylesheet" href="{{ asset('css/modern-fallback.css') }}">
        @vite('resources/js/app.js')
    @endif
</head>
<body class="antialiased">
    <main class="welcome-page min-h-screen bg-med-canvas text-med-ink">
        <header class="sticky top-0 z-20 border-b border-med-line bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-screen-2xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ url('/') }}" class="flex items-center gap-3">
                    <span class="inline-flex h-11 w-11 items-center justify-center rounded-md border border-med-line bg-white shadow-sm">
                        <img src="{{ asset('images/logo.png') }}" alt="Mediflow" class="h-8 w-8 object-contain" width="32" height="32" style="width:32px;height:32px;max-width:32px;object-fit:contain;">
                    </span>
                    <span>
                        <span class="block text-lg font-semibold leading-5 text-med-ink">Mediflow</span>
                        <span class="block text-xs font-semibold uppercase tracking-wide text-med-muted">Health Suite</span>
                    </span>
                </a>

                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-med-primaryDark">Login</a>
                    @endauth
                @endif
            </div>
        </header>

        <section class="welcome-hero">
            <img src="{{ asset('images/welcome-healthcare-bg.png') }}" alt="" class="welcome-hero-bg" aria-hidden="true">
            <div class="welcome-hero-inner mx-auto grid max-w-screen-2xl items-center gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_0.92fr] lg:px-8">
            <div class="welcome-hero-copy">
                <div class="mb-6 inline-flex items-center gap-2 rounded-md border border-med-line bg-white px-3 py-2 text-sm font-semibold text-med-primary shadow-sm">
                    <span class="h-2 w-2 rounded-full bg-med-success"></span>
                    Modular healthcare operations platform
                </div>

                <h1 class="max-w-4xl text-4xl font-semibold leading-tight text-med-ink sm:text-5xl lg:text-6xl">Software solution for hospitals and other health sectors.</h1>
                <p class="mt-6 max-w-2xl text-lg leading-8 text-med-muted">Mediflow connects patient records, clinical care, diagnostics, pharmacy, maternity, finance, reports, roles, and licensed modules in one practical workspace.</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark">Open Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark">Login to Workspace</a>
                        @endauth
                    @endif

                    @if($canManageWifiSharing ?? false)
                        <button type="button" id="wifiConnectButton" class="mf-focus inline-flex items-center justify-center rounded-md border border-med-line bg-white px-5 py-3 text-sm font-semibold text-med-ink shadow-sm transition hover:bg-med-canvas {{ ($wifiSharing['connected'] ?? false) ? 'hidden' : '' }}" data-connect-url="{{ route('wifi-sharing.connect') }}" data-status-url="{{ route('wifi-sharing.status') }}">Connect Others</button>
                    @endif
                </div>

                @if($canManageWifiSharing ?? false)
                    <div id="wifiConnectStatus" class="mt-4 max-w-xl rounded-md border border-med-success bg-green-50 px-4 py-3 text-sm font-semibold text-med-success {{ ($wifiSharing['connected'] ?? false) ? '' : 'hidden' }}">
                        Connected for Wi-Fi access
                        <span class="block font-normal text-med-muted">{{ $wifiSharing['url'] ?? '' }}</span>
                    </div>
                @endif
            </div>
            </div>
        </section>

                        <section class="about-mediflow-section bg-white">
            <div class="about-mediflow-content mx-auto grid max-w-screen-2xl items-center gap-8 px-4 py-14 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:px-8">
                <div>
                    <h2 class="text-3xl font-semibold text-med-ink">About Mediflow</h2>
                    <p class="mt-3 text-lg font-semibold leading-7 text-med-primary">A practical digital backbone for modern healthcare facilities.</p>
                    <p class="mt-4 text-base leading-7 text-med-muted">Mediflow helps hospitals, clinics, diagnostic centers, maternity facilities, and pharmacies run daily operations from one organized workspace. It brings patient care, billing, stock, reports, licensing, and staff access together so teams can work faster with clearer information.</p>
                </div>

                <div class="mission-vision-block mission-vision-card rounded-md border border-med-line bg-white p-5">
                    <div class="mission-vision-statements">
                        <div>
                            <h3 class="text-base font-semibold text-med-ink">Mission</h3>
                            <p class="mt-3 text-sm leading-6 text-med-muted">To simplify healthcare administration and clinical workflows with reliable software that improves coordination, accountability, and service delivery.</p>
                        </div>
                        <div class="mt-5 border-t border-med-line pt-5">
                            <h3 class="text-base font-semibold text-med-ink">Vision</h3>
                            <p class="mt-3 text-sm leading-6 text-med-muted">To become a trusted health technology platform that supports every level of care, from small clinics to enterprise hospital networks.</p>
                        </div>
                    </div>

                    <div class="mission-vision-image" aria-hidden="true">
                        <img src="{{ asset('images/mediflow.png') }}" alt="Mediflow" class="w-full object-contain" style="max-height:170px;object-fit:contain;">
                    </div>
                </div>
            </div>
        </section>
                <section class="how-it-works-section border-y border-med-line bg-med-canvas">
            <div class="mx-auto max-w-screen-2xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-semibold text-med-ink">How it works</h2>
                    <p class="mt-4 text-base leading-7 text-med-muted">Mediflow can work in two simple ways. If you want users to access it through the internet, choose online installation. If you want it to work only inside your hospital, clinic, pharmacy, or diagnostic center, choose offline installation. Both options give your staff the same organized workspace.</p>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach([
                        ['01', 'Choose how you want to use it', 'Use Mediflow online when staff need access through the internet, or offline when the system should work only inside your facility.', []],
                        ['02', 'Online works through the internet', 'With online setup, the system is hosted on a server and users can log in from approved locations using a secure web address.', ['Domain name for opening the system', 'VPS server where Mediflow is hosted', 'Active subscription for modules, updates, and support']],
                        ['03', 'Offline works inside the facility', 'With offline setup, one laptop or desktop can serve as the local server. Staff connect through LAN or Wi-Fi, even without internet.', ['LAN or Wi-Fi network inside the facility', 'Laptop or desktop to serve as the local server', 'For small clinics, one router can serve nearby devices']],
                        ['04', 'Staff start their daily work', 'After setup, each staff member logs in, sees only the tools they are allowed to use, and manages patients, billing, stock, reports, or clinical work.', []],
                    ] as $step)
                        <article class="how-step-card rounded-md border border-med-line bg-white p-5 shadow-sm">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-green-50 text-sm font-semibold text-med-primary">{{ $step[0] }}</span>
                            <h3 class="mt-4 text-base font-semibold text-med-ink">{{ $step[1] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-med-muted">{{ $step[2] }}</p>

                            @if(! empty($step[3]))
                                <div class="mt-4 border-t border-med-line pt-4">
                                    <p class="text-sm font-semibold text-med-ink">Requirements</p>
                                    <ul class="mt-3 space-y-2 text-sm leading-6 text-med-muted">
                                        @foreach($step[3] as $requirement)
                                            <li class="flex gap-2"><span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-med-primary"></span><span>{{ $requirement }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        
        <section class="plans-section bg-white">
            <div class="mx-auto max-w-screen-2xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-semibold text-med-ink">Plans for every healthcare facility</h2>
                    <p class="mt-4 text-base leading-7 text-med-muted">Choose a plan that matches the way your facility works. Each plan activates the right modules, keeps the sidebar clean, and gives your team only the tools they need.</p>
                </div>

                <div class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        ['Diagnostic Center', 'Laboratory and radiology facilities', ['Patient registration', 'Lab requests', 'Radiology requests', 'Result approvals', 'Financial management', 'Administration', 'Access control', 'Reports'], 'From NGN 150,000 / year'],
                        ['Maternity Clinic', 'Antenatal, delivery, and newborn care centers', ['Patient records', 'ANC visits', 'Labour monitoring', 'Delivery records', 'Newborn records', 'Financial management', 'Administration', 'Access control', 'Maternity reports'], 'From NGN 180,000 / year'],
                        ['General Clinic', 'Outpatient clinics and small medical centers', ['Patient records', 'Appointments', 'Consultation', 'Prescriptions', 'Financial management', 'Administration', 'Access control', 'Basic reports'], 'From NGN 200,000 / year'],
                        ['Pharmacy', 'Retail and facility pharmacy operations', ['Inventory', 'Dispensing', 'Sales', 'Purchases', 'Financial management', 'Administration', 'Access control', 'Expiry tracking', 'Stock reports'], 'From NGN 120,000 / year'],
                        ['Hospital', 'Single hospital with clinical and admin departments', ['Patients', 'Clinical care', 'Admissions', 'Wards', 'Diagnostics', 'Pharmacy', 'Financial management', 'Administration', 'Access control', 'Reports'], 'From NGN 350,000 / year'],
                        ['Enterprise Hospital', 'Multi-branch hospital group', ['Branch management', 'Central reporting', 'Multi-facility users', 'All hospital modules', 'Financial management', 'Administration', 'Access control', 'Enterprise support'], 'Custom pricing'],
                    ] as $plan)
                        <article class="plan-card rounded-md border border-med-line bg-med-canvas p-5 shadow-sm">
                            <div class="flex min-h-24 flex-col justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-med-ink">{{ $plan[0] }}</h3>
                                    <p class="mt-2 text-sm leading-6 text-med-muted">{{ $plan[1] }}</p>
                                </div>
                                <p class="text-base font-semibold text-med-primary">{{ $plan[3] }}</p>
                            </div>

                            <div class="mt-5 border-t border-med-line pt-4">
                                <p class="text-sm font-semibold text-med-ink">Included modules</p>
                                <ul class="mt-3 space-y-2 text-sm leading-6 text-med-muted">
                                    @foreach($plan[2] as $module)
                                        <li class="plan-module-item flex gap-2"><span class="plan-module-icon" aria-hidden="true"></span><span>{{ $module }}</span></li>
                                    @endforeach
                                </ul>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
                <section class="bg-med-canvas">
            <div class="mx-auto max-w-screen-2xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="max-w-3xl">
                    <h2 class="text-3xl font-semibold text-med-ink">Facility Workspace</h2>
                    <p class="mt-4 text-base leading-7 text-med-muted">Mediflow follows patient information from the first record to payment, care, admission, and final discharge. Each department sees the part of the journey they need, while the facility keeps one connected patient history.</p>
                </div>

                <section class="patient-flow-card mt-8 rounded-md border border-med-line bg-white p-5 shadow-panel">
                    <div class="flex flex-col gap-2 border-b border-med-line pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-med-ink">Patient information flow</h3>
                            <p class="mt-1 text-sm text-med-muted">A simple view of how patient activity moves across the facility.</p>
                        </div>
                        <span class="rounded-md bg-green-50 px-3 py-1 text-xs font-semibold text-med-primary">Connected workflow</span>
                    </div>

                    <div class="patient-flow-grid mt-5">
                        @foreach([
                            ['01', 'Record', 'Patient registration and file creation.'],
                            ['02', 'Payment', 'Initial billing and payment capture.'],
                            ['03', 'Vital sign recording', 'Nursing team records patient vitals.'],
                            ['04', 'Consultation', 'Doctor reviews the patient and documents findings.'],
                            ['05', 'Investigation request', 'Doctor requests lab or diagnostic investigation.'],
                            ['06', 'Investigation payment', 'Patient pays for requested investigation.'],
                            ['07', 'Lab result entry', 'Laboratory enters and submits result.'],
                            ['08', 'Back to doctor', 'Doctor reviews result and updates care plan.'],
                            ['09', 'Admission', 'Patient is admitted when inpatient care is required.'],
                            ['10', 'Patient care', 'Ward care, treatment, observations, and follow-up.'],
                            ['11', 'Discharge or exit', 'Patient is discharged, signs against medical advice, or absconds.'],
                        ] as $flow)
                            <article class="patient-flow-step">
                                <span class="patient-flow-number">{{ $flow[0] }}</span>
                                <div>
                                    <h4>{{ $flow[1] }}</h4>
                                    <p>{{ $flow[2] }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="mt-10">
                    <div class="mb-4">
                        <h2 class="text-base font-semibold text-med-ink">Solutions by facility type</h2>
                        <p class="mt-1 text-sm text-med-muted">Each license plan activates only the modules that belong to the client package.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach([
                            ['Hospital', 'Admissions, wards, clinical care, finance.'],
                            ['General Clinic', 'Visits, appointments, consultations.'],
                            ['Diagnostic Center', 'Laboratory and radiology workflows.'],
                            ['Pharmacy', 'Stock, dispensing, sales, reconciliation.'],
                            ['Maternity Clinic', 'ANC, labour, newborn, postnatal.'],
                            ['Enterprise Hospital', 'Multi-branch licensing and oversight.'],
                        ] as $solution)
                            <article class="rounded-md border border-med-line bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-panel">
                                <h3 class="text-sm font-semibold text-med-ink">{{ $solution[0] }}</h3>
                                <p class="mt-2 text-sm leading-6 text-med-muted">{{ $solution[1] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>
            </div>
        </section>
<section class="partner-section border-y border-med-line bg-med-canvas">
            <div class="mx-auto grid max-w-screen-2xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
                <div>
                    <h2 class="text-3xl font-semibold text-med-ink">Partner with us</h2>
                    <p class="mt-4 text-base leading-7 text-med-muted">We welcome sales agents and technical support partners who can help introduce Mediflow to healthcare providers, assist with onboarding, or support clients where needed.</p>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-md border border-med-line bg-white p-5 shadow-sm"><h3 class="text-base font-semibold text-med-ink">Sales agent</h3><p class="mt-3 text-sm leading-6 text-med-muted">Promote Mediflow to hospitals, clinics, diagnostic centers, maternity facilities, and pharmacies in your network.</p></div>
                        <div class="rounded-md border border-med-line bg-white p-5 shadow-sm"><h3 class="text-base font-semibold text-med-ink">Technical support</h3><p class="mt-3 text-sm leading-6 text-med-muted">Support installation, user training, troubleshooting, and client success for facilities using Mediflow.</p></div>
                    </div>
                </div>

                <form class="partner-form rounded-md border border-med-line bg-white p-6 shadow-panel" method="POST" action="#">
                    <div><h3 class="text-xl font-semibold text-med-ink">Partnership application</h3><p class="mt-2 text-sm leading-6 text-med-muted">Submit your interest and our team will contact you with the next steps.</p></div>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <label class="block"><span class="text-sm font-semibold text-med-ink">Full name</span><input type="text" name="name" class="mt-2 w-full rounded-md border border-med-line px-3 py-2 text-sm" placeholder="Your name"></label>
                        <label class="block"><span class="text-sm font-semibold text-med-ink">Phone number</span><input type="text" name="phone" class="mt-2 w-full rounded-md border border-med-line px-3 py-2 text-sm" placeholder="Phone number"></label>
                        <label class="block"><span class="text-sm font-semibold text-med-ink">Email address</span><input type="email" name="email" class="mt-2 w-full rounded-md border border-med-line px-3 py-2 text-sm" placeholder="Email address"></label>
                        <label class="block"><span class="text-sm font-semibold text-med-ink">Partner type</span><select name="partner_type" class="mt-2 w-full rounded-md border border-med-line px-3 py-2 text-sm"><option>Sales agent</option><option>Technical support</option><option>Sales and technical support</option></select></label>
                    </div>
                    <label class="mt-4 block"><span class="text-sm font-semibold text-med-ink">Location and experience</span><textarea name="message" rows="4" class="mt-2 w-full rounded-md border border-med-line px-3 py-2 text-sm" placeholder="Tell us your location, healthcare network, or technical experience"></textarea></label>
                    <button type="submit" class="mf-focus mt-5 inline-flex items-center justify-center rounded-md border border-med-primary bg-med-primary px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-med-primaryDark">Submit application</button>
                </form>
            </div>
        </section>


        <footer class="mx-auto max-w-screen-2xl px-4 py-6 text-sm text-med-muted sm:px-6 lg:px-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; 2026 Mediflow Health Suite. All rights reserved.</p>
                <p>Hospitals | Clinics | Pharmacies | Diagnostics | Maternity Care</p>
            </div>
        </footer>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const button = document.getElementById('wifiConnectButton');
            const status = document.getElementById('wifiConnectStatus');

            if (! button || ! status) {
                return;
            }

            const token = document.querySelector('meta[name="csrf-token"]')?.content;

            button.addEventListener('click', async function () {
                const original = button.textContent;
                button.disabled = true;
                button.textContent = 'Connecting...';

                try {
                    const response = await fetch(button.dataset.connectUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    });
                    const data = await response.json();

                    if (! response.ok || ! data.connected) {
                        throw new Error('Connection failed');
                    }

                    button.classList.add('hidden');
                    status.classList.remove('hidden');
                    status.innerHTML = 'Connected for Wi-Fi access' + (data.url ? '<span class="block font-normal text-med-muted">' + data.url + '</span>' : '');
                } catch (error) {
                    button.disabled = false;
                    button.textContent = original;
                    status.classList.remove('hidden');
                    status.classList.remove('border-med-success', 'bg-green-50', 'text-med-success');
                    status.classList.add('border-med-danger', 'bg-red-50', 'text-med-danger');
                    status.textContent = 'Unable to start Wi-Fi connection. Please make sure port 8080 is free, then try again.';
                }
            });
        });
    </script>
</body>
</html>






































