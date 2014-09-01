jQuery(document).ready(function($){
    $('#email').focus();
    
    $('#saveButton').bind('click', function (event) {
        var err = false;
        var errmsg = "";
        var field = $('#simpleTuring');
        if(field && !field.prop("checked") ) {
          errmsg += 'You must tick the box that asks if you are not a robot.\n';
          if(!err) field.focus();
          err = true;
        }
        field = $('#email');
        if(!checkEmail(field.val())) {
          errmsg += 'You must provide a valid email address.\n';
          if(!err) field.focus();
          err = true;
        }
        if(err) {
          alert(errmsg);
          return false;
        }
        var submitdata = $(document.forms['register']).serialize();
        $('#ajax-loading').removeClass('farleft');
        $('#returnMessage').html('&nbsp;');
        $('#saveButton').prop('disabled', true);
        $.post(data.ajaxUrl, submitdata, function( response ){
             var ajaxdata = $.parseJSON(response);
             if( ajaxdata.error ) {
                 $('#returnMessage').html( ajaxdata.error );
                 $('#saveButton').prop('disabled', false);
             } else if( ajaxdata.success ) {
                 $('#returnMessage').html( ajaxdata.success );
             } else {
                 $('#returnMessage').html ( ajaxdata );
             }
             $('#ajax-loading').addClass('farleft');
          });
      } );
    });

function checkEmail(inputvalue){	
var pattern=/^([a-zA-Z0-9_.-])+@([a-zA-Z0-9_.-])+\.([a-zA-Z])+([a-zA-Z])+/;
var bool = pattern.test(inputvalue);
return bool;
}
