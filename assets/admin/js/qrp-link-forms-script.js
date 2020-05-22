jQuery(document).ready(function($){

    var maxField = 10; //Input fields increment limitation
    var addButton = $('.add_button'); //Add button selector
    var wrapper = $('.field_wrapper'); //Input field wrapper
    var fieldHTML = function (iterator) {
        return '<div><input type="text" name="qrp_form_item['+ iterator +'][cf_id]" value="" placeholder="Caldera Form ID" required/>\n' +
            '        <input type="text" name="qrp_form_item['+ iterator +'][group]" value="" placeholder="Form Group" required/><a href="javascript:void(0);" class="remove_button button">Unlink Form</a></div>'; //New input field html
    }
    var x = 1; //Initial field counter is 1

    //Once add button is clicked
    $(addButton).click(function(){
        //Check maximum number of input fields
        if(x < maxField){
            x++; //Increment field counter
            $(wrapper).append(fieldHTML(wrapper.children().length + 1)); //Add field html
        }
    });

    //Once remove button is clicked
    $(wrapper).on('click', '.remove_button', function(e){
        e.preventDefault();
        $(this).parent('div').remove(); //Remove field html
        x--; //Decrement field counter
    });

    // add once
    $(wrapper).prepend(fieldHTML(wrapper.children().length + 1)); //Add field html
});