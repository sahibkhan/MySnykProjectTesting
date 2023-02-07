/*+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/
var val = $('#record').val();
var user_id = $('#user_id').val();
var declaration_id = $('#declaration_id').val();
/*var isreferal = $('#check_referal_hidden').val();
// alert(isreferal);
if(isreferal == 'yes') {
    $('#check_referal').show();
} else {
    $('#check_referal').hide();
}*/
jQuery(function(){

    // localStorage.setItem("wisBtn", 0);
    // press post to wis button event
    $('#postwis').on('click', function (e) {
        e.preventDefault();

        // alert(val);

        var btn = $(this);

        $(btn).innerHTML('start');


        $.ajax({
            type:'POST',
            url:'include/CargoInsurance/wis.php',
            dataType : 'json',
            data: {record: val, user_id: user_id},
            success: function(data) {
                $('#diserror').html(''); //clear previous data
                $('#display').html('');
                localStorage.setItem(val, '0');
                $(btn).buttonLoader('stop');
                // var json = JSON.parse(data);
				console.log(data);
				
                if(data.status == 1) {

                    $('#display').html(data.message);

                    var delayInMilliseconds = 3000; //5 second

                     if ( localStorage.getItem(val) == 0 ) {
                        localStorage.setItem(val, '1');
                     }

                    setTimeout(function() {
                        //your code to be executed after 5 second
                        window.location.reload();

                    }, delayInMilliseconds);

                } else if (data.status == -1) {
                    //send ajax to
                    // alert('hit -1');
                    $("#postwis").hide();
                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                    // $('#referal').append("<input type='button' id='check_referal' value='check referal' >");
                    // document.getElementById("check_referal").style.display = "block";
                    // check_referal
                    $("#check_referal").show();

                    $('#check_referal').on('click', function (e) {
                        e.preventDefault();
                        // alert('check_referal');
                        // console.log('check_referal');
                        $.ajax({
                            type: 'POST',
                            url: 'include/CargoInsurance/webhookstatus.php',
                            dataType: 'json',
                            data: { record: val },
                            success: function (data) {
                                $('#display').html('');
                                $('#diserror').html(''); //clear previous data
                                $('#refstatus').html("Referal status is: " + data.refstatus);
                            }

                        });
                    });



//<input type='button' value='check referal' >
                }
                else if (data.status == -2) {
                    $('#quotes').html( data.message );

                    $("#quoteContinue").click(function () {
                        var btn = $(this);

                        $(btn).buttonLoader('start');

                        var chosen_quote_id = $("input[id='quoteIds']:checked").val();


                        $.ajax({
                            type: 'POST',
                            url: 'include/CargoInsurance/quote.php',
                            dataType: 'json',
                            data: { chosenQuoteId: chosen_quote_id,
                                    declarationId: data.declarationId,
                                    insuranceId: val,
                                    userId: user_id},
                            success: function (data) {

                                $(btn).innerHTML('stop');
                                $('#display').html('');
                                $('#diserror').html(''); //clear previous data

                                if(data.status == 1) {

                                    $('#display').html(data.message);

                                    var delayInMilliseconds = 3000; //5 second

                                    if ( localStorage.getItem(val) == 0 ) {
                                        localStorage.setItem(val, '1');
                                    }

                                    setTimeout(function() {
                                        //your code to be executed after 5 second
                                        window.location.reload();

                                    }, delayInMilliseconds);

                                } else if (data.status == -1) {
                                    //send ajax to
                                    $("#postwis").hide();
                                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                                    $("#check_referal").show();

                                    $('#check_referal').on('click', function (e) {
                                        e.preventDefault();
                                        $.ajax({
                                            type: 'POST',
                                            url: 'include/CargoInsurance/webhookstatus.php',
                                            dataType: 'json',
                                            data: { record: val, declarationId: declaration_id },
                                            success: function (data) {
                                                $('#display').html('');
                                                $('#diserror').html(''); //clear previous data
                                                $('#refstatus').html("Referal status is: " + data.refstatus);
                                            }

                                        });
                                    });

                                } else {

                                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                                }

                            }
                        });
                    });

                }
                else {

                    $('#diserror').html(data.type + " . " + data.message + " . " + data.errors);
                }

            }
        });

        if( localStorage.getItem(val) == '1' ) {
            document.getElementById("hidebutton").style.display = "block";
        }

        localStorage.removeItem(val);
    });

    // cancel declaration
    $("#cancelButton").click(function() {
        var canBeCancelled = $("#can_be_cancelled").val();
        // alert(canBeCancelled);
        // canBeCancelled = true;
        if(canBeCancelled == false) {
            alert('Перед отменой нужно обнулить поля Freight, Value в блоке Cargo Insurance Amount');
        } else {
            $("#form1").toggle();

            $('#cancelSubmit').on('click', function (e) {
                // e.preventDefault();
                // alert('hit submit');
                var reason = $("#cancellationReason").val();
                // var declaration_id = $("#cancelDeclarationId").val();
                // console.log( reason );
                // alert(reason);

                $.ajax({
                    type: 'POST',
                    url: 'include/CargoInsurance/wiscanceldeclaration.php',
                    dataType: 'json',
                    data: { cancellationReason: reason, insuranceId: val },
                    success: function (data) {
                        $('#display').html('');
                        $('#diserror').html(''); //clear previous data
                        $('#refstatus').html("Cancellation status is: " + data.refstatus);
                        $('#form1').hide();

                    }


                });

            });

        }

    });







});

// check referal status button

$('#check_referal').on('click', function (e) {
    // alert('hit');
    // e.preventDefault();
    // alert('check_referal');
    // console.log('check_referal');
    $.ajax({
        type: 'POST',
        url: 'include/CargoInsurance/webhookstatus.php',
        dataType: 'json',
        data: { record: val },
        success: function (data) {
            $('#display').html('');
            $('#diserror').html(''); //clear previous data
            $('#refstatus').html( data );
        }

    });
});


// alert(localStorage.getItem(val));


