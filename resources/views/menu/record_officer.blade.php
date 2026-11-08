@extends('layouts.modern')

@section('title', 'Record Officer.Blade')
@section('page-title', 'Record Officer.Blade')
@section('page-subtitle', 'Mediflow workspace.')

@section('title', 'Record Officer Menu')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold text-gray-900 mb-4">Record Officer Portal</h1>
            <p class="text-xl text-gray-600">Manage patient records, appointments, and documentation</p>
        </div>

        <!-- Main Menu Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <!-- Patient Registration Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-100 mb-4">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Register Patient</h3>
                    <p class="text-gray-600 text-sm mb-4">Register new patients and walk-in patients in the system</p>
                    <a href="{{ route('record_officer.patients.register.form') }}" class="inline-block bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
                        Get Started →
                    </a>
                </div>
            </div>

            <!-- Patient Management Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-green-600 to-green-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-green-100 mb-4">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 10H9m6 0a6 6 0 11-12 0 6 6 0 0112 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Patient Management</h3>
                    <p class="text-gray-600 text-sm mb-4">View, search, and manage patient records and information</p>
                    <a href="{{ route('record_officer.patients.list') }}" class="inline-block bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition font-medium">
                        Get Started →
                    </a>
                </div>
            </div>

            <!-- Patient Search Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-purple-100 mb-4">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Search Patient</h3>
                    <p class="text-gray-600 text-sm mb-4">Find patients by hospital number, payment ID, or phone number</p>
                    <a href="{{ route('record_officer.patients.search') }}" class="inline-block bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition font-medium">
                        Get Started →
                    </a>
                </div>
            </div>

            <!-- Appointments Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-yellow-100 mb-4">
                        <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Manage Appointments</h3>
                    <p class="text-gray-600 text-sm mb-4">Schedule and manage patient appointments</p>
                    <a href="{{ route('record_officer.appointments') }}" class="inline-block bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition font-medium">
                        Get Started →
                    </a>
                </div>
            </div>

            <!-- Visit Management Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-red-600 to-red-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-red-100 mb-4">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Patient Visits</h3>
                    <p class="text-gray-600 text-sm mb-4">Record and maintain patient visit histories and notes</p>
                    <button onclick="alert('Select a patient to record a visit')" class="inline-block bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition font-medium">
                        Get Started →
                    </button>
                </div>
            </div>

            <!-- Admissions & Discharges Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-indigo-100 mb-4">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Admissions & Discharges</h3>
                    <p class="text-gray-600 text-sm mb-4">Document patient admissions, discharges, and transfers</p>
                    <button onclick="alert('Select a patient to record admission/discharge')" class="inline-block bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-medium">
                        Get Started →
                    </button>
                </div>
            </div>

            <!-- Referrals Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-pink-600 to-pink-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-pink-100 mb-4">
                        <svg class="h-6 w-6 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.658 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Referrals</h3>
                    <p class="text-gray-600 text-sm mb-4">Manage patient referrals to other departments</p>
                    <button onclick="alert('Select a patient to create a referral')" class="inline-block bg-pink-600 text-white px-4 py-2 rounded-lg hover:bg-pink-700 transition font-medium">
                        Get Started →
                    </button>
                </div>
            </div>

            <!-- Dashboard Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition transform hover:-translate-y-1">
                <div class="bg-gradient-to-r from-teal-600 to-teal-700 h-2"></div>
                <div class="p-6">
                    <div class="flex items-center justify-center h-12 w-12 rounded-md bg-teal-100 mb-4">
                        <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Dashboard</h3>
                    <p class="text-gray-600 text-sm mb-4">View statistics and quick access to important information</p>
                    <a href="{{ route('record_officer.dashboard') }}" class="inline-block bg-teal-600 text-white px-4 py-2 rounded-lg hover:bg-teal-700 transition font-medium">
                        Get Started →
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats Section -->
        <div class="mt-16 bg-white rounded-lg shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-8">Record Officer Responsibilities</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Patient Registration</h3>
                        <p class="mt-2 text-gray-600">Register new patients and walk-in patients with all demographic details</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Record Maintenance</h3>
                        <p class="mt-2 text-gray-600">Maintain accurate and complete patient records and visit histories</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Appointment Scheduling</h3>
                        <p class="mt-2 text-gray-600">Schedule and manage patient appointments efficiently</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Hospital Number Generation</h3>
                        <p class="mt-2 text-gray-600">Automatically generate unique hospital numbers for registered patients</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Admissions & Discharges</h3>
                        <p class="mt-2 text-gray-600">Document patient admissions, discharges, and manage bed assignments</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">Referral Management</h3>
                        <p class="mt-2 text-gray-600">Process and manage patient referrals to other departments</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

