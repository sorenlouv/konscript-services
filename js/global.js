$(document).ready(function() {
	showFlashMessage();
	
    // show error details
    $("#deploylist tr.commit").click(function(){
        var id = $(this).attr('rel');
        $('.details.i_'+id).toggle();
    });
   
   // show tip to solve errors
    $("tr.error").click(function(){
        var id = $(this).attr("rel");
        $("#showTip div").hide();
        $("#showTip #tip_"+id).fadeIn();
    });    
    
    // confirm deployment    
    $("td.deploy a.existing").click(function(){
		if(!confirm('Do you really wish to deploy project to the existing production server? You are editing LIVE stuff, mate!')){
			return false;
		}
    });    
    
    // confirm delete
    $("a.delete").click(function(){
		if(confirm('Delete project and associated virtual hosts ?!')){
			if(confirm('You are DELETING the project! Proceed?')){
				return true;
			}
		}		
		return false;
    });    
    
})

// will gently show the most recent flash message
function showFlashMessage() {
	selector = '.flash-message';
	if ($(selector).html()){
		$(selector).animate({height: "show", opacity: 1}, 500);
		$(".flash-message-seperator").animate({height: "show", opacity: 1}, 500);
		$(selector).click(function() {
			$(selector).animate({height: "hide", opacity: 0}, 500);
			$(".flash-message-seperator").animate({height: "hide"}, 500);
		});
	}
} 

