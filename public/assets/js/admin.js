jQuery( document ).ready(function( $ ) {
    $(".multiple").click(function() {
        $(this).before($("<br />"));
        $(this).before($(this).prev().prev().clone().attr('value', ""));
    });
});