const loading_table_spinner = document.getElementById('loading_table_spinner');
const table_wrapper = document.getElementById('table_wrapper');

// Make sure they are there before we try loading the table
if (table_wrapper !== null && loading_table_spinner !== null) {

    // Load it like this to avoid weird race conditions with chat widget
    document.addEventListener('autoLoaderPostLoad', async() => {
        const datatables = await import('/wdn/templates_6.0/js/plugins/plugin.datatables.js');
        loadTable(datatables);
    });
}

async function loadTable(datatables) {
    const $ = await datatables.initialize();
    
    $('#groups').DataTable({
        responsive: true,
    
        // Sort by "Last Redirect" column descending by default
        order: [[0, 'desc']],
    
        buttons: [
            {
                extend: 'csvHtml5',
                text: 'Download CSV',
                title: 'your_go_groups',
                bom: true,
    
                // Exclude Actions column from export
                exportOptions: {
                    columns: [0]
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