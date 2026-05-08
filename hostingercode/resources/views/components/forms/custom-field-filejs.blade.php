<script type="text/javascript">
(function() {
    'use strict';
    if (typeof $ !== 'undefined' && typeof dropifyMessages !== 'undefined') {
var drCustomFieldEvent = $('.custom-field-file .dropify').dropify({
    messages: dropifyMessages
});
drCustomFieldEvent.on("dropify.afterClear", function(event, element) {
        var elementName = element.element.name;
        // find the hidden input field and remove the value
        $('input[type=hidden][name="' + elementName + '"]').val('');
});
    }
})();
</script>
