// import other datable extensions if needed, e.g.:
// import 'datatables.net-responsive-bs5';
// import 'datatables.net-buttons-bs5';
// import 'datatables.net-buttons/js/buttons.html5.js';
// import 'datatables.net-buttons/js/buttons.print.js';
document.addEventListener('DOMContentLoaded', function() {
                if (window.jQuery && $.fn.DataTable) {
                    $('table.datatable').each(function () {
                        if (!$.fn.DataTable.isDataTable(this)) {
                            // Get table title from card header or use generic name
                            var $card = $(this).closest('.card');
                            var tableTitle = $card.length > 0 
                                ? $card.find('.card-header h5').text().replace(/<[^>]*>/g, '').trim() 
                                : 'Table_Export_' + new Date().getTime();

                            var dataOrder = $(this).data('order');
                            var ajaxUrl = $(this).data('ajax');
                            if (typeof dataOrder === 'string') {
                                try {
                                    dataOrder = JSON.parse(dataOrder.replace(/'([^']+)'/g, '"$1"'));
                                } catch (e) {
                                    dataOrder = null;
                                }
                            }
                            if (!dataOrder) {
                                var thCount = $(this).find('thead th').length;
                                dataOrder = [[Math.max(thCount - 1, 0), 'desc']];
                            }
                            var dtOptions = {
                                responsive: true,
                                processing: !!ajaxUrl,
                                serverSide: !!ajaxUrl,
                                pageLength: 10,
                                lengthMenu: [10, 25, 50, 100],
                                autoWidth: false,
                                deferRender: !ajaxUrl,
                                language: {
                                    search: "_INPUT_",
                                    searchPlaceholder: "Search table..."
                                },
                                columnDefs: [
                                    { orderable: false, targets: 'no-sort' }
                                ],
                                order: dataOrder,
                                dom: 'Bfrtip',
                                buttons: [
                                    {
                                        extend: 'copy',
                                        className: 'btn btn-sm btn-outline-success me-2',
                                        text: '<i class="bi bi-clipboard me-1"></i>Copy',
                                        title: tableTitle,
                                        exportOptions: {
                                            columns: ':visible:not(.no-export)'
                                        }
                                    },
                                    {
                                        extend: 'csv',
                                        className: 'btn btn-sm btn-outline-warning me-2',
                                        text: '<i class="bi bi-filetype-csv me-1"></i>CSV',
                                        title: tableTitle,
                                        filename: tableTitle + '_' + new Date().toISOString().split('T')[0],
                                        exportOptions: {
                                            columns: ':visible:not(.no-export)'
                                        }
                                    },
                                    {
                                        extend: 'excel',
                                        className: 'btn btn-sm btn-outline-success me-2',
                                        text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel',
                                        title: tableTitle,
                                        filename: tableTitle + '_' + new Date().toISOString().split('T')[0],
                                        exportOptions: {
                                            columns: ':visible:not(.no-export)'
                                        }
                                    },
                                    {
                                        extend: 'pdf',
                                        className: 'btn btn-sm btn-outline-warning me-2',
                                        text: '<i class="bi bi-file-earmark-pdf me-1"></i>PDF',
                                        title: tableTitle,
                                        filename: tableTitle + '_' + new Date().toISOString().split('T')[0],
                                        exportOptions: {
                                            columns: ':visible:not(.no-export)'
                                        },
                                        orientation: 'landscape',
                                        pageSize: 'A4'
                                    },
                                    {
                                        extend: 'print',
                                        className: 'btn btn-sm btn-outline-success',
                                        text: '<i class="bi bi-printer me-1"></i>Print',
                                        title: tableTitle,
                                        exportOptions: {
                                            columns: ':visible:not(.no-export)'
                                        }
                                    }
                                ]
                            };

                            if (ajaxUrl) {
                                dtOptions.ajax = ajaxUrl;
                            }

                            var dt = $(this).DataTable(dtOptions);

                            var refreshInterval = parseInt($(this).data('refresh'), 10);
                            if (ajaxUrl && !isNaN(refreshInterval) && refreshInterval > 0) {
                                setInterval(function () {
                                    if (dt.ajax && typeof dt.ajax.reload === 'function') {
                                        dt.ajax.reload(null, false);
                                    } else if (typeof dt.draw === 'function') {
                                        dt.draw(false);
                                    }
                                }, refreshInterval);
                            }

                            // Style the button container
                            var buttonContainer = dt.buttons().container();
                            $(buttonContainer).addClass('d-flex gap-2 mb-3 flex-wrap');
                            
                            // Insert buttons before table
                            $(this).closest('.table-responsive').before(buttonContainer);

                            // Hide server-rendered pagination (if any) to avoid duplicate controls
                            try {
                                $(this).closest('.card').find('.card-footer').hide();
                            } catch (e) {
                                // ignore
                            }
                        }
                    });
                }
            });