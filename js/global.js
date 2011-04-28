$(document).ready(function() {
    $("#deploylist tr.commit").click(function(){
        var id = $(this).attr('rel');
        $('.details.i_'+id).toggle();
    });
})
