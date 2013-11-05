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
use Aws\S3\S3Client;

add_action( 'widgets_init', 'my_widget' );



function my_widget() {
  register_widget( 'MY_Widget' );
}

class MY_Widget extends WP_Widget {
  static function cds_log($text)
     {
      $when = date('Y-m-d H:i:s',time());
      $data = "$when:$text\n";
      $fyle = fopen('/tmp/cds-upload.log','a');
      fwrite($fyle,$data);
      fclose($fyle);
    }

  function MY_Widget() {
    $widget_ops = array( 'classname' => 'example', 'description' => __('A widget that displays the authors name ', 'example') );
    
    $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'example-widget' );
    
    $this->WP_Widget( 'example-widget', __('Example Widget', 'example'), $widget_ops, $control_ops );

    add_action('init', array($this, 'widget_init') );
    add_action( 'wp_ajax_nopriv_upload_to_s3', array($this, 'upload_to_s3'), 1 );


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

  function upload_to_s3()
  {
    self::cds_log('uppppppload to s3');
    self::cds_log('file-size' . $_GET["filesize"]);
    self::cds_log('OfileSize' . $_FILES["Filedata"]["size"]);
    self::cds_log('name' . $_FILES["Filedata"]["name"]);
    
    self::cds_log('api call to S3');

    $aws = Aws::factory(dirname( __FILE__ ) . '/aws/aws-config.php');
    $client = $aws->get('S3');
    $msg = array();

      // $client = S3Client::factory(array(
      //   'key' => 'AKIAIFCPGC4N3CVFU2KA',
      //   'secret' => 'pUFnEXFPX5gLDeNKOQ/lLZMNg1sDSIdXxMJccR3Q',
      // ));

      // $handle = fopen('/tmp/sample_mpeg4.mp4', 'r');
      // var_dump($handle);die;


      // Upload a publicly accessible file. The file size, file type, and MD5 hash are automatically calculated by the SDK
      
      try {
          $client->putObject(array(
              'Bucket' => 'cds-campaign',
              'Key'    => $_FILES["Filedata"]["name"],
              // 'Body'   => fopen('/tmp/sample_mpeg4.mp4', 'r'),
              'Body'   => fopen($_FILES["Filedata"]["tmp_name"], 'r'),
              // 'SourceFile' => $_FILES["Filedata"]["tmp_name"],
              'ACL'    => 'public-read',
          ));
       self::cds_log('api call to S3 - 2');

      } catch (S3Exception $e) {
        self::cds_log($e);
        self::cds_log('error s3 putobject');
        $msg["error"] = "S3 Exception";
        // echo "There was an error uploading the file.\n";
      } catch(Exception $e){
        $msg["error"] = "exception";
      }

      self::cds_log('finish api call s3');

      // $msg = 'success';
      // $msg = $_GET['filename'];
      // $msg["filename"] = $_GET['filename']["size"];
      $msg["msg"] = "success";
      // $msg["handle"] = fopen($_FILES["Filedata"]["tmp_name"], r);
      // $msg = json_encode($_FILES["Filedata"]["tmp_name"]);
      wp_send_json_success( $msg );
      unlink($_FILES["Filedata"]["name"]);   
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
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.10.1/jquery.min.js"></script>
<script src="http://malsup.github.com/jquery.form.js"></script>
    <form id="myForm" action="wp-admin/admin-ajax.php?action=upload_to_s3" method="post" enctype="multipart/form-data">
     <input type="file" size="60" name="Filedata">
     <input type="submit" value="Ajax File Upload">
 </form>
 
 <div id="progress">
        <div id="bar"></div>
        <div id="percent">0%</div >
</div>
<br/>
 
<div id="message"></div>
<script>
$(document).ready(function()
{
 
    var options = { 
    beforeSend: function() 
    {
        $("#progress").show();
        //clear everything
        $("#bar").width('0%');
        $("#message").html("");
        $("#percent").html("0%");
    },
    uploadProgress: function(event, position, total, percentComplete) 
    {
        $("#bar").width(percentComplete+'%');
        
        console.log(percentComplete);
        if(percentComplete > 98)
        {
          $("#percent").html('Finishing upload');
          $("#bar").width('99%');
        }
        else 
        {
          $("#percent").html(percentComplete+'%');
          $("#bar").width(percentComplete+'%');
        }
    },
    success: function(response) 
    {
        $("#bar").width('100%');
        $("#percent").html('Upload Complete');
 
    },
    complete: function(response) 
    {
     console.log(response.responseText);
     var r = $.parseJSON(response.responseText);
        if(r.data.msg == "success")
          $("#message").html("<font color='green'>"+ r.data.msg +"</font>");
        else
          $("#message").html("<font color='green'>Upload fail. Please try again</font>");
    },
    error: function()
    {
        $("#message").html("<font color='red'> ERROR: unable to upload files</font>");
    }
 
    }; 
 
     $("#myForm").ajaxForm(options);
 
});
</script>

<style>
form { display: block; margin: 20px auto; background: #eee; border-radius: 10px; padding: 15px }
#progress { position:relative; width:400px; border: 1px solid #ddd; padding: 1px; border-radius: 3px; }
#bar { background-color: #B4F5B4; width:0%; height:20px; border-radius: 3px; }
#percent { position:absolute; display:inline-block; top: 0; left: 48%; }
</style>
   
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