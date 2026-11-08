@once
    <script>
        function printPharmacyReceipt(html) {
            const source = document.createElement('template');
            source.innerHTML = html.trim();
            const receipt = source.content.querySelector('.thermal-receipt');
            if (!receipt || !receipt.textContent.trim()) {
                alert('There is no receipt content to print.');
                return;
            }

            const receiptWindow = window.open('', '_blank', 'width=360,height=640');
            if (!receiptWindow) {
                alert('Please allow popups to print the pharmacy receipt.');
                return;
            }

            receiptWindow.document.write(`<!doctype html><html><head><meta charset="utf-8"><title>Pharmacy Receipt</title>
                <style>
                    @page { size: auto; margin: 0; }
                    * { box-sizing: border-box; }
                    html, body { margin: 0; padding: 0; height: auto; min-height: 0; }
                    body { width: 80mm; padding: 0 4mm; color: #000; font-family: monospace; font-size: 12px; line-height: 1.25; }
                    .thermal-receipt { display: flow-root; width: 72mm; max-width: 100%; height: auto; min-height: 0; margin: 0; padding: 0; overflow-wrap: anywhere; }
                    h5 { font-size: 14px; margin: 0 0 3px; }
                    p { margin: 0 0 3px; }
                    .thermal-receipt > :first-child { margin-top: 0; }
                    .thermal-receipt > :last-child { margin-bottom: 0; padding-bottom: 0; }
                    table { width: 100%; border-collapse: collapse; }
                    td { padding: 2px 0; border: 0; vertical-align: top; }
                    tr { break-inside: avoid; }
                    .divider { border-top: 1px dashed #000; margin: 5px 0; }
                    .text-center { text-align: center; }
                    .text-right { text-align: right; }
                    .fw-bold { font-weight: 700; }
                    .small { font-size: 11px; }
                </style></head><body>${receipt.outerHTML}</body></html>`);
            receiptWindow.document.close();
            receiptWindow.focus();

            setTimeout(function () {
                // Keep the printer's roll size. Custom short page dimensions can
                // rotate driver paper to landscape and feed extra blank paper.
                receiptWindow.print();
                receiptWindow.close();
            }, 300);
        }
    </script>
@endonce
