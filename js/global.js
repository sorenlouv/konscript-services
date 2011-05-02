$(document).ready(function() {

    //show error details when click on deploy
    $("#deploylist tr.commit").click(function(){
        var id = $(this).attr('rel');
        $('.details.i_'+id).toggle();
    });
   
   //show tip to solve errors, when click on error 
    $("tr.error").click(function(){
        var id = $(this).attr("rel");
        $("#showTip div").hide();
        $("#showTip #tip_"+id).fadeIn();
    });    
})
