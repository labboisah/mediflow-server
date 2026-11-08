# Pharmacy service billing

Head of Pharmacy: open **Medications > Pharmacy Services** (`/pharmacy/services`). Add service names and charges, or edit and deactivate existing services. No default prices are supplied. Both the named Head of Pharmacy role and a department head assigned to Pharmacy can manage this catalogue.

Pharmacist / Pharmacy Technician: open **Transactions > Add Transaction**. Add medicines and/or services to the same cart. The quantity field applies to either item type. Enter the patient name (required for services) and optional phone number, select the payment method, and press Pay. This creates one paid bill, one payment and one combined receipt. Services alone are supported. Patient details are recorded as a walk-in billing record without requiring patient-records or clinical modules.

Receipts can be reprinted from Transactions; pharmacy managers can also reprint through Payments. Charged service names and prices are saved independently of later catalogue edits. Service items never deduct medicine stock. Add any separately charged medicine or supply as a medicine line.

Checkout validates active services, active payment methods, quantities, expiry and available stock on the server. A changed service charge requires removing and adding that item before payment. All sale records and stock deductions are committed together or rolled back. Pharmacy and billing modules must be enabled.

Financial reports include service income. Their existing gross-profit calculation deducts medicine purchase costs only; service labour and supplies are not costed.

Deployment: run `php artisan migrate --path=database/migrations/2026_09_28_000001_add_pharmacy_services.php --force`, then `php artisan db:seed --class=PharmacyServiceModuleSeeder --force`. Refresh cached routes/views as required. No real transactions or service prices are seeded.
