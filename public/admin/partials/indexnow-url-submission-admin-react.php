<div id="bwAppRoot"></div>
<script>
    (function( $ ) {
        var bwApp = $('#bwAppRoot');
        if (bwApp) {
            var wp_body = bwApp.parents()[0];
            var update_nags = wp_body.querySelectorAll('.update-nag');
            update_nags.forEach(update_nag => {
                update_nag.style.display = 'none';
            });
            document.querySelector('#wpcontent').setAttribute('style', 'padding-left: 0')
        }

    })( jQuery );
</script>
