$(document).ready(function(){
		$(".algo1").fadeIn();
        $(".algo2").hide();
        $(".algo12").hide();
});

$(".algo").click(function(){
    if($(this).val()==1){
    		$(".algo2").fadeIn();
    		$(".algo1").fadeOut();
    		$(".algo12").fadeOut();
    }
    else if($(this).val()==2){
    		$(".algo12").fadeIn();
    		$(".algo2").fadeOut();
    		$(".algo1").fadeOut();
    }
    else if($(this).val()==0){
    		$(".algo1").fadeIn();
    		$(".algo2").fadeOut();
    		$(".algo12").fadeOut();
    }

});