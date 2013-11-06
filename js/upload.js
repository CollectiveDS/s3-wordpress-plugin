jQuery(document).ready(function()
{
    var inputVideo = jQuery("#inputVideo");
    inputVideo.change(function(e){ 
      var ext = this.value.match(/\.(.+)$/)[1];
      switch(ext)
      {  
        case 'avi':
        case 'mov':
        case 'mp4':
        case 'wmv':
        case 'mpg':
        case 'mpeg':
        case 'flv':
        case 'jpg':
        case 'png':
        case 'jpeg':
        case 'gif':
            jQuery('#uploadBtn').attr("disabled", false);
            break;
        default:
            alert('file type not allowed');
            jQuery('#uploadBtn').attr("disabled", true);
            this.value='';
            break;
      }
    });

    jQuery('#uploadBtn').click(function(evt){
      
       if(jQuery('[name=fullname]').val() == '' ||
          jQuery('[name=email]').val() == '' ||
          jQuery('[name=ytusername]').val() == '' ||
          jQuery('[name=petname]').val() == '' )
       {
          alert('Fill in missing inputs');
          evt.preventDefault();
          return false;
       }

       if(jQuery('[name=age]').is(':checked') == false)
       {
          alert('Must be 18 years and above');
          evt.preventDefault();
          return false;
       }

       //check input type file is not empty
       if(jQuery("#inputVideo").val() == '')
       {
          alert('Select file to upload');
          evt.preventDefault();
          return false;
       }
    });

    var options = { 
    beforeSend: function() 
    {
        jQuery("#progress").show();
        //clear everything
        jQuery("#bar").width('0%');
        jQuery("#message").html("");
        jQuery("#percent").html("0%");

        
    },
    uploadProgress: function(event, position, total, percentComplete) 
    {
        jQuery("#bar").width(percentComplete+'%');
        
        console.log(percentComplete);
        if(percentComplete > 98)
        {
          jQuery("#percent").html('99%');
          jQuery("#bar").width('99%');
          jQuery("#message").html("<font color='green'>Almost done, finishing upload.</font>");
        }
        else 
        {
          jQuery("#percent").html(percentComplete+'%');
          jQuery("#bar").width(percentComplete+'%');
        }
    },
    success: function(response) 
    {
        jQuery("#bar").width('100%');
        jQuery("#percent").html('100%');
 
    },
    complete: function(response) 
    {
     console.log(response.responseText);
     var r = jQuery.parseJSON(response.responseText);
        if(r.data.msg == "success")
          jQuery("#message").html("<font color='green'>Upload Completed</font>");
        else
          jQuery("#message").html("<font color='green'>Upload fail. Please try again</font>");
    },
    error: function()
    {
        jQuery("#message").html("<font color='red'> ERROR: unable to upload files</font>");
    }
 
    }; 
    jQuery("#uploadForm").ajaxForm(options);
});