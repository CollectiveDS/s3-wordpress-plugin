<?php
/**
 * Plugin Name: A simple Widget2
 * Description: A widget that displays authors name.
 * Version: 0.1
 * Author: Bilal Shaheen
 * Author URI: http://gearaffiti.com/about
 */

require_once dirname( __FILE__ ) . '/aws/aws-sdk-php/vendor/autoload.php';
use Aws\Common\Aws;
use Aws\S3\Exception\S3Exception;

add_action( 'widgets_init', 'my_widget' );

function my_widget() {
  register_widget( 'MY_Widget' );
}

class MY_Widget extends WP_Widget {

  function MY_Widget() {
    $widget_ops = array( 'classname' => 'example', 'description' => __('A widget that displays the authors name ', 'example') );
    
    $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'example-widget' );
    
    $this->WP_Widget( 'example-widget', __('Example Widget', 'example'), $widget_ops, $control_ops );

    add_action('init', array($this, 'widget_init') );

  }

  function widget_init(){
    wp_register_style( 'mediaStylesheet', plugins_url('css/cds-media.css', __FILE__) );
    wp_enqueue_style( 'mediaStylesheet' );

    wp_register_style( 'uploadStylesheet', plugins_url('css/upload.css', __FILE__) );
    wp_enqueue_style( 'uploadStylesheet' );

      $args = array('name'=>'CDS Upload',
              'id'=>'cds-upload',
              'description' => 'Upload CDS Video to MC Backend',
              'before_widget' => '<div class="widget">',
              'after_widget' => '</div>',
              'before_title' => '',
              'after_title' => '');
      register_sidebar($args);



  }
  
  function widget( $args, $instance ) {
    extract( $args );

    //Our variables from the widget settings.
    $title = apply_filters('widget_title', $instance['title'] );
    $name = $instance['name'];
    $show_info = isset( $instance['show_info'] ) ? $instance['show_info'] : false;

    //echo $before_widget;

    // // Display the widget title 
    // if ( $title )
    //  echo $before_title . $title . $after_title;

    // //Display the name 
    // if ( $name )
    //  printf( '<p>' . __('Hey their Sailor! My name is %1$s.', 'example') . '</p>', $name );

    
    // if ( $show_info )
    //  printf( $name );

    
    // echo $after_widget;
    ?>
    <div class="container">
        <div class="contr">
          <h1>Upload New CDS Video</h1>
          <p>or if you already have a video uploaded you can provide the URL here: <input type="button" value="click here" onclick="location.href='/wp-admin/post-new.php?post_type=cdsvideo';"> </p>
        </div>
        <div class="upload_form_cont">
          <?php if ( $can_edit_files ){ ?>
          <select>
            <option value="">Community (Default)</option>
            <?php
              $cds_options = get_option( 'cds_options' );
              $counter = 0;
              $selected = true;
              if (empty($cds_options['option_accounts'])) {
                $cds_options['option_accounts'] = array();
              }
              foreach ($cds_options['option_accounts'] as $counter=>$user_name){
                ?>
                  <option <?php echo ($selected ? 'selected' : '') ?> value="<?php echo esc_attr( $cds_options['option_accounts'][$counter] ); ?>"><?php echo esc_attr( $cds_options['option_accounts'][$counter] ); ?></option>
                <?php
                $selected = false;
              }
            ?>
          </select>
          <?php } ?>


          <div id="dropArea">Drop Files Here
            <div id="selectFileArea" >
              <small id="orText">or</small>
              <input id="fileSelect" class="button" type="button" value="Select File" />
              <input id="myInput" type="file"  style="visibility:hidden" />

            </div>
          </div>
          <div class="info">
            <div style="display:none;">Files left: <span id="count">0</span></div>
             <div style="display:none">Destination url: <input id="url" value="http://akupload.metacafe.com/uploads/87914/1234/"/></div>
            <h2>Result:</h2>
            <div id="result"></div>
          </div>
        </div>
      </div>
      <script>
        // variables
        var dropArea = document.getElementById('dropArea');
        var count = document.getElementById('count');
        var destinationUrl = document.getElementById('url');
        var result = document.getElementById('result');
        var list = [];
        var totalSize = 0;
        var totalProgress = 0;

        var uploadInProgress = false;

        //counter for counting the number of files that's been uploaded
        var counter = 0;

        // main initialization
        (function(){

          var percentText = jQuery('#percentage');

           //handles the file select option
          jQuery('#fileSelect').click(function(){

            if(!uploadInProgress){

              jQuery('#myInput').change(function(){
                processFiles(this.files);

                jQuery(this).replaceWith('<input id="myInput" type="file"  style="visibility:hidden" />');
              });

              jQuery('#myInput').click();
            }else{
              this.attr("disabled");
            }
          });



          // init handlers
          function initHandlers() {
            dropArea.addEventListener('drop', handleDrop, false);
            dropArea.addEventListener('dragover', handleDragOver, false);
            dropArea.addEventListener('dragleave', handleDragLeave, false);
          }

          // draw progress
          function drawProgress(progress) {
            var canvs = jQuery('.progressBar').last();
            var context = canvs[0].getContext('2d');

            //fill with white canvas
            context.clearRect(0, 0, canvs.width, canvs.height); // clear context
            context.beginPath();
            context.strokeStyle = '#EEEEEE';
            context.fillStyle = '#EEEEEE';
            context.fillRect(0, 0, 1 * 460, 20);
            context.closePath();

            //fill with green progress bar
            context.clearRect(0, 0, canvs.width, canvs.height); // clear context
            context.beginPath();
            context.strokeStyle = '#73E069';
            context.fillStyle = '#73E069';
            context.fillRect(0, 0, progress * 460, 20);
            context.closePath();
            //context.fillText('                                          ', 50, 15);
            //context.font = '16px Verdana';
            //context.fillStyle = '#000';
            //context.fillText('Progress: ' + Math.floor(progress*100) + '%', 50, 15);

            jQuery('.percent' + counter).text(Math.floor(progress*100) + '%');

          }

          // drag over
          function handleDragOver(event) {
            event.stopPropagation();
            event.preventDefault();

            if(!uploadInProgress)
              dropArea.className = 'hover';
          }

          // drag drop
          function handleDrop(event) {
            event.stopPropagation();
            event.preventDefault();

            if(!uploadInProgress)
              processFiles(event.dataTransfer.files);
          }

          function handleDragLeave(event){
            dropArea.className = '';
          }

          // process bunch of files
          function processFiles(filelist) {
            if (!filelist || !filelist.length || list.length) return;

            totalSize = 0;
            totalProgress = 0;
            // result.textContent = '';

            for (var i = 0; i < filelist.length && i < 5; i++) {
              list.push(filelist[i]);
              totalSize += filelist[i].size;
            }
            uploadNext();

          }


          // on complete - start next file
          function handleComplete() {

            drawProgress(1);
            uploadNext();
          }

          // update progress
          function handleProgress(event) {
            var progress = totalProgress + event.loaded;

            if((progress / totalSize) > 0.01)
              drawProgress(progress / totalSize - 0.01);
            else
              drawProgress(progress / totalSize);
          }

          // upload file
          function uploadFile(file, status, index) {
            //get user from selected option
            var user = jQuery('select option:selected').length ? jQuery('select option:selected').val() : '';

            jQuery.ajax({
              url: '/wp-admin/admin-ajax.php',
              data: {
                cds_user_id: user,
                action: 'get_cds_upload_folder',
              },
              cache: false,
              type: 'GET',
              success: function(response){
                if (response.data){

                  jQuery('.info').show();
                  upload_cds_file_to_storage(response.data.uploadFolder, file);

                } else {

                  alert("Something went wrong, please try again later (error: get cds upload folder API");
                }
              }
            });
          }

          function upload_cds_file_to_storage(folder, file){
            var fileName = file.name;
            var uploadFileURL = folder + fileName;
            var isError = false;
            var data = new FormData();
            data.append( 'Filedata', file );

            jQuery.ajax({
              url: uploadFileURL,
              data: data,
              cache: false,
              contentType: false,
              processData: false,
              type: 'POST',
              success: function(data){  },
              beforeSend: function(xhr){
                uploadInProgress = true;
                jQuery("<div class='fileContainer'><div style='width:500px'><p style='float:left'>" + fileName + "</p><p class='editLink" + counter + "' style='float: right'></p></div><div style='clear:both'></div><canvas class='progressBar' width='460' height='20'></canvas><span id='percentage' class='percent"+ counter + "'></span></div>").appendTo('#result');
              },
              xhr: function(){
                  var xhr = new window.XMLHttpRequest();
                  //Upload progress
                  xhr.upload.addEventListener("progress", function(evt){
                  if (evt.lengthComputable) {
                    var percentComplete = evt.loaded / evt.total;
                    //Do something with upload progress
                    handleProgress(evt);
                   }
                 }, false);
                 //Download progress
                 xhr.addEventListener("progress", function(evt){
                   if (evt.lengthComputable) {
                   var percentComplete = evt.loaded / evt.total;
                    //handleProgress(evt);
                   }
                 }, false);
                 return xhr;
              },
              error: function(jqXHR, textStatus, errorThrown){
                isError = true;
                jQuery('.fileContainer').remove();
                alert('Error occurred, please try again' );

                uploadInProgress = false;
                return;
              },
              complete: function(data){
                 if(!isError){
                   getCDSItemID(uploadFileURL, name[0]);
                   drawProgress(0.99);
                   uploadInProgress = false;

                }
              }
            });
          }

          function getCDSItemID(uploadFileURL, fileName){
            if (uploadFileURL){
              var data = new FormData();
              data.append( 'fileURL', uploadFileURL );

              //get user from selected option
              var user = jQuery('select option:selected').length ? jQuery('select option:selected').val() : '';

              jQuery.ajax({
                url: '/wp-admin/admin-ajax.php?action=get_cdsvideo_item_id&cds_user_id=' + user,
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                type: 'POST',
                success: function(response){

                  var cdsItemID = false;
                  if (typeof response.data != 'undefined'){
                    var cdsItemID = response.data.itemID;
                  }
                  
                  if ( !cdsItemID ){
                    setTimeout(function(){ getCDSItemID(uploadFileURL, fileName); }, 2000);
                  } else {

                    //save itemID on wordpress
                    jQuery.ajax({
                      url: '/wp-admin/admin-ajax.php',    //this will call the ajax wp function
                      dataType: "text",
                      data: {
                        itemID : cdsItemID,
                        title  : fileName,
                        action: 'save_cdsvideo_item_id',
                      },
                      cache: false,
                      type: 'POST',
                      success: function(response){
                        if (response){
                          drawProgress(1);
                          var postID = jQuery.parseJSON(response);
                          jQuery('.editLink' + counter).append('<a href="/wp-admin/post.php?post=' + postID.data + '&action=edit">edit</a>');
                          counter++;
                          handleComplete();
                        }
                      },

                    });
                  }
                }
              });
            }
          }

          // upload next file
          function uploadNext() {
            if (list.length) {
              count.textContent = list.length - 1;
              dropArea.className = 'uploading';

              var nextFile = list.shift();
              // if (nextFile.size >= 2621440000) { // 256kb
              //                    result.innerHTML += '<div class="f">Too big file (max filesize exceeded)</div>';
              //                    handleComplete(nextFile.size);
              //                } else {
              //                    uploadFile(nextFile, status, count.textContent);
              //                }

              uploadFile(nextFile, status, count.textContent);
            } else {
              dropArea.className = '';
            }
          }

          initHandlers();
        })();
      </script>
  <?php
  }

  //Update the widget 
   
  function update( $new_instance, $old_instance ) {
    $instance = $old_instance;

    //Strip tags from title and name to remove HTML 
    $instance['title'] = strip_tags( $new_instance['title'] );
    $instance['name'] = strip_tags( $new_instance['name'] );
    $instance['show_info'] = $new_instance['show_info'];

    return $instance;
  }

  
  function form( $instance ) {

    //Set up some default widget settings.
    $defaults = array( 'title' => __('Example', 'example'), 'name' => __('Bilal Shaheen', 'example'), 'show_info' => true );
    $instance = wp_parse_args( (array) $instance, $defaults ); ?>

    //Widget Title: Text Input.
    <p>
      <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'example'); ?></label>
      <input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
    </p>

    //Text Input.
    <p>
      <label for="<?php echo $this->get_field_id( 'name' ); ?>"><?php _e('Your Name:', 'example'); ?></label>
      <input id="<?php echo $this->get_field_id( 'name' ); ?>" name="<?php echo $this->get_field_name( 'name' ); ?>" value="<?php echo $instance['name']; ?>" style="width:100%;" />
    </p>

    
    //Checkbox.
    <p>
      <input class="checkbox" type="checkbox" <?php checked( $instance['show_info'], true ); ?> id="<?php echo $this->get_field_id( 'show_info' ); ?>" name="<?php echo $this->get_field_name( 'show_info' ); ?>" /> 
      <label for="<?php echo $this->get_field_id( 'show_info' ); ?>"><?php _e('Display info publicly?', 'example'); ?></label>
    </p>

  <?php
  }
}

?>