const loading_table_spinner = document.getElementById('loading_table_spinner');
const table_wrapper = document.getElementById('table_wrapper');

// Make sure they are there before we try loading the table
if (table_wrapper !== null && loading_table_spinner !== null) {

    // Load it like this to avoid weird race conditions with chat widget
    document.addEventListener('autoLoaderPostLoad', async() => {
        const datatables = await import('/wdn/templates_6.0/js/plugins/plugin.datatables.js');

        // Give it a little extra time since the chat widget isn't loaded by the plugin autoloader
        setTimeout(() => {
            loadTable(datatables);
        }, 500);
    });
}

async function loadTable(datatables) {
    const $ = await datatables.initialize();

    // console.log(JSON.stringify(window.UNL.chat));
    
    $('#go-urls').DataTable({
        responsive: true,
    
        // Sort by "Last Redirect" column descending by default
        order: [[4, 'desc']],
    
        columnDefs: [
            // Last Redirect column
            {
                targets: 4,
                type: 'date'
            },
    
            // Created On column
            {
                targets: 5,
                type: 'date'
            },
    
            // Actions column should not be sortable/searchable
            {
                targets: 6,
                orderable: false,
                searchable: false
            },
    
            // Center redirect counts
            {
                targets: 3,
                className: 'dt-center'
            }
        ],
    
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Download CSV',
                title: 'your_go_urls',
                bom: true,
    
                // Exclude Actions column from export
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5]
                }
            }
        ],
    
        layout: {
            topStart: 'pageLength',
            topEnd: ['search', 'buttons'],
            bottomStart: 'info',
            bottomEnd: 'paging'
        },
    
        pageLength: 25,
    
        language: {
            search: 'Search:',
            lengthMenu: 'Show _MENU_ URLs per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ URLs',
            emptyTable: 'No URLs found'
        }
    });

    loading_table_spinner.classList.add('dcf-d-none!');
    table_wrapper.classList.remove('dcf-d-none!');
}

document.dispatchEvent(new Event('goReady'));