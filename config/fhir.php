<?php

$serverUrl = rtrim((string) env('FHIR_SERVER_URL', ''), '/');
$patientIdentifierSystem = trim((string) env('FHIR_PATIENT_IDENTIFIER_SYSTEM', ''));

return [
    'enabled' => (bool) env('FHIR_API_ENABLED', false),
    'base_url' => $serverUrl !== ''
        ? $serverUrl
        : rtrim((string) env('APP_URL', 'http://localhost'), '/').'/api/fhir/R4',
    'patient_identifier_system' => $patientIdentifierSystem !== ''
        ? $patientIdentifierSystem
        : rtrim((string) env('APP_URL', 'http://localhost'), '/').'/identifiers/hospital-number',
    'version' => '4.0.1',
];
