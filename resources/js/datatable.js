import $ from 'jquery';

window.$ = window.jQuery = $;

// DataTables
import DataTable from 'datatables.net-bs5';
DataTable(window, $);

// Buttons
import 'datatables.net-buttons-bs5';
import 'datatables.net-buttons/js/buttons.html5';
import 'datatables.net-buttons/js/buttons.print';

// Export dependencies
import JSZip from 'jszip';
window.JSZip = JSZip;

import pdfMake from 'pdfmake/build/pdfmake';
import pdfFonts from 'pdfmake/build/vfs_fonts';

pdfMake.vfs = pdfFonts.vfs;
window.pdfMake = pdfMake;

// Your custom DataTable initialization
import './datatable';