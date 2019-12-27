$(document).ready(function() {

    $(window).scroll(function() {    
        var scroll = $(window).scrollTop();
         //console.log(scroll);
        if (scroll >= 50) {
            //console.log('a');
            $(".navbar").addClass("change");
        } else {
            //console.log('a');
            $(".navbar").removeClass("change");
        }
    });

    function showRecipeMessage(message) {        
        var $message = $("<div class='message'></div>");
        $message.addClass(message.type);
        $message.html(message.content);
        $("#recipe-add-messages").html("");
        $("#recipe-add-messages").append($message);
    }

    $("#recipe-add-form").on("submit", function(e) {
        e.preventDefault();

        $("#recipe-add-submit").prop('disabled', true);
        var formData = new FormData($(this)[0]);

        $.ajax({
            type: "POST",
            enctype: 'multipart/form-data',
            data: formData,
            processData: false,  // tell jQuery not to process the data
            contentType: false,
            success: function(data)
            {
                if(data.status == "success") {
                    $("#recipe-add-form").trigger("reset");
                    showRecipeMessage(data.message);
                } else {
                    showRecipeMessage(data.message);
                }
            },
            error: function(data) {
                showRecipeMessage({
                    'type': 'error',
                    'content': 'An unexpected error occured'
                });
            }            
        }).always(function() {
            $("#recipe-add-submit").prop('disabled', false);
        });
    });

});