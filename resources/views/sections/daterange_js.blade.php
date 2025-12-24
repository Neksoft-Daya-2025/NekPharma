<script src="{{ asset('vendor/jquery/daterangepicker.min.js') }}"></script>

<script type="text/javascript">
    $(function() {

        var start = moment().subtract(89, 'days');
        var end = moment();

        // Ensure daterangeLocale and daterangeConfig are available
        if (typeof window.daterangeLocale === 'undefined' || typeof window.daterangeConfig === 'undefined') {
            console.warn('daterangeLocale or daterangeConfig not available. Please ensure they are defined in the layout.');
        }
        
        $('#datatableRange').daterangepicker({
            autoUpdateInput: false,
            locale: window.daterangeLocale || daterangeLocale,
            linkedCalendars: false,
            startDate: start,
            endDate: end,
            showDropdowns: true,
            ranges: window.daterangeConfig || daterangeConfig
        }, cb);


        $('#datatableRange').on('apply.daterangepicker', function(ev, picker) {
            showTable();
        });

    });

</script>
