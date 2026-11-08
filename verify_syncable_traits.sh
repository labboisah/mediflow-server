#!/bin/bash

# Verification script for Syncable trait implementation

echo "=========================================="
echo "Syncable Trait Verification Report"
echo "=========================================="
echo ""

models=(
    "Patient"
    "PatientAdmission"
    "PatientVisit"
    "Payment"
    "Bill"
    "Prescription"
    "VitalSign"
    "Observation"
    "AntenatalCare"
    "Labour"
    "LabourProgress"
    "Delivery"
    "InvestigationRequest"
    "InvestigationResult"
    "FluidBalance"
    "NewbornExamination"
    "DrugChart"
    "Discharge"
    "Diagnose"
    "Continuation"
    "ChildFollowUp"
    "Admission"
)

echo "Checking models for Syncable trait..."
echo ""

success=0
failed=0

for model in "${models[@]}"; do
    file="app/Models/${model}.php"
    if [ -f "$file" ]; then
        if grep -q "use Syncable;" "$file"; then
            echo "✅ $model"
            ((success++))
        else
            echo "❌ $model - Missing Syncable trait"
            ((failed++))
        fi
    else
        echo "⚠️  $file not found"
        ((failed++))
    fi
done

echo ""
echo "=========================================="
echo "Results: $success passed, $failed failed"
echo "=========================================="

if [ $failed -eq 0 ]; then
    echo "✅ All models have Syncable trait!"
    exit 0
else
    echo "❌ Some models are missing the trait"
    exit 1
fi
