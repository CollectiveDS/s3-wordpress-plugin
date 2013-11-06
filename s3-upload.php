<?php
/**
 * Plugin Name: s3-upload-plugin
 * Description: A widget that lets user upload images, videos to an S3 bucket
 * Version: 0.1
 * Author: Hanzen Lim
 * 
 */

require_once dirname( __FILE__ ) . '/aws/aws-sdk-php/vendor/autoload.php';
require_once dirname( __FILE__ ) . '/recaptcha-php-1.11/recaptchalib.php';

use Aws\Common\Aws;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\S3\Model\AcpBuilder;

add_action( 'widgets_init', 's3_upload_widget' );


function s3_upload_widget() {
  register_widget( 's3_widget' );
}

class s3_widget extends WP_Widget {
  static function cds_log($text)
     {
      $when = date('Y-m-d H:i:s',time());
      $data = "$when:$text\n";
      $fyle = fopen('/tmp/cds-upload.log','a');
      fwrite($fyle,$data);
      fclose($fyle);
    }

  private $recaptcha_public_key = "google-recaptcha-public-key";
  private $recapthca_private_key = "google-recaptcha-private-key";

  function s3_widget() 
  {
    $widget_ops = array( 'classname' => 's3widget', 'description' => __('Uploade media files to an s3 bucket', 'example') );
    
    $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 's3-widget' );
    
    $this->WP_Widget( 's3-widget', __('s3 upload widget', 's3widget'), $widget_ops, $control_ops );

    add_action('init', array($this, 'widget_init'));
    add_action( 'wp_ajax_nopriv_upload_to_s3', array($this, 'upload_to_s3'), 1 );
    add_action( 'wp_ajax_upload_to_s3', array($this, 'upload_to_s3'), 1 );


    //add meta box in S3 custom post
    add_action( 'add_meta_boxes', array($this, 's3_meta_box_init') );
  }

  function widget_init()
  {
    wp_register_style( 'mediaStylesheet', plugins_url('css/cds-media.css', __FILE__) );
    wp_enqueue_style( 'mediaStylesheet' );

    wp_register_style( 'uploadStylesheet', plugins_url('css/upload.css', __FILE__) );
    wp_enqueue_style( 'uploadStylesheet' );

    wp_enqueue_script( 'formUpload', plugins_url('js/form.js', __FILE__), array(), '1.0.0', true );
    wp_enqueue_script( 'uploadjs', plugins_url('js/upload.js', __FILE__), array(), '1.0.0', true );

    register_post_type( 's3videos',
    array(
        'labels' => array(
          'name' => __( 'S3 Videos Uploaded' ),
          'singular_name' => __( 'S3 Videos' )
        ),
      'public' => true,
      'has_archive' => true,
      )
    );

    $args = array('name'=>'S3 Upload',
            'id'=>'s3-upload',
            'description' => 'Upload any media to s3 bucket',
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '',
            'after_title' => '');
    register_sidebar($args);

    //remove editor for s3videos post
    remove_post_type_support('s3videos', 'editor');
  }

  function s3_meta_box_init()
  {
    $screens = array( 's3videos');

    foreach ( $screens as $screen ) {

        add_meta_box(
            's3_meta_box',
            __( 'User Information', 'User Information' ),
            array($this, 's3_inner_custom_box'),
            $screen
        );
    }
  }

  function s3_inner_custom_box($post)
  { 
    //nonce for security
    wp_nonce_field( 's3_inner_custom_box', 's3_inner_custom_box_nonce' );

    $email = get_post_meta( $post->ID, '_s3_email', true );
    $fullname = get_post_meta( $post->ID, '_s3_fullname', true );
    $ytusername = get_post_meta( $post->ID, '_s3_ytusername', true );
    $petname = get_post_meta( $post->ID, '_s3_petname', true );
    $filename = get_post_meta( $post->ID, '_s3_filename', true );
    $fileurl = get_post_meta( $post->ID, '_s3_fileurl', true );
    $comment = get_post_meta( $post->ID, '_s3_comment', true );

    ?>
    <div>
        <h4>Full name: <?php echo $fullname; ?></h4>
        <h4>Email: <?php echo $email; ?></h4>
        <h4>Youtube username: <?php echo $ytusername; ?></h4>
        <h4>Petname: <?php echo $petname; ?></h4>
        <h4>Comments: <?php echo $comment; ?></h4>
        <h4>File name: <?php echo $filename; ?></h4>
        <h4>File URL: <a href="<?php echo $fileurl ?>" target="_blank"><?php echo $fileurl; ?></a></h4>
    </div>
    <?php
  }

  function upload_to_s3()
  {
    //check recaptcha
    $resp = recaptcha_check_answer ($this->recapthca_private_key,
                                $_SERVER["REMOTE_ADDR"],
                                $_POST["recaptcha_challenge_field"],
                                $_POST["recaptcha_response_field"]);

    if (!$resp->is_valid) {
      // What happens when the CAPTCHA was entered incorrectly
      $msg["msg"] = "fail-recaptcha";
      wp_send_json_success( $msg );
      die;
    } 

    $aws = Aws::factory(dirname( __FILE__ ) . '/aws/aws-config.php');
    $client = $aws->get('S3');
    $msg = array();

      // Upload a publicly accessible file. The file size, file type, and MD5 hash are automatically calculated by the SDK     
      try {
          $result = $client->putObject(array(
              'Bucket' => $_GET["bname"],
              'Key'    => $_FILES["Filedata"]["name"] . '-' . time(),
              'Body'   => fopen($_FILES["Filedata"]["tmp_name"], 'r'),
              'ACL'    => 'private',

          ));
       self::cds_log('result::' . $result["ObjectURL"]);

       //create s3 custom post
       $postarr = array(
          'post_type' => 's3videos',
          'post_status' => 'private',
          'post_title' => $_POST["email"],
          'post_name' => $_POST["email"],
          'post_content' => $user->first_name,
        );

        $post_id = wp_insert_post( $postarr );
        update_post_meta( $post_id, '_s3_email', $_POST["email"]);
        update_post_meta( $post_id, '_s3_fullname', $_POST["fullname"]);
        update_post_meta( $post_id, '_s3_ytusername', $_POST["ytusername"]);
        update_post_meta( $post_id, '_s3_petname', $_POST["petname"]);
        update_post_meta( $post_id, '_s3_filename', $_FILES["Filedata"]["name"]);
        update_post_meta( $post_id, '_s3_fileurl', $result["ObjectURL"]);
        update_post_meta( $post_id, '_s3_comment', $_POST["comment"]);


      } catch (S3Exception $e) 
      {
        self::cds_log($e);
        self::cds_log('error s3 putobject');
        $msg["msg"] = "S3 Exception";
      } catch(Exception $e)
      {
        $msg["msg"] = "exception";
      }

      $msg["msg"] = "success";
      wp_send_json_success( $msg );
      unlink($_FILES["Filedata"]["name"]);   
  }

  
  function widget( $args, $instance ) {
    extract( $args );

    //Our variables from the widget settings.
    $s3bucket = apply_filters('widget_title', $instance['title'] );

    ?>
<div class="uploadvideo"> 
  <form id="uploadForm" action="wp-admin/admin-ajax.php?action=upload_to_s3&bname=<?php echo $s3bucket; ?>"  method="post" enctype="multipart/form-data">
    <ul>
      <li>Full Name</li>
      <li> <input type="text" size="30" name="fullname"></li>
      <li>Email</li>
      <li><input type="text" size="30" name="email"></li>
      <li>Youtube Username:</li>
      <li><input type="text" size="30" name="ytusername"></li>
      <li>Animal pet name:</li>
      <li><input type="text" size="30" name="petname"></li>
      <li>I am 18 or older : <input type="checkbox" name="age"></li>
      <li>Comments:</li>
      <li><textarea name="comment" id="Message" cols="30" wrap="virtual" rows="3"></textarea></li>
      <li><input type="file" size="60" name="Filedata" id="inputVideo"></li>
      <li><div class="recaptcha"><?php echo recaptcha_get_html($this->recaptcha_public_key); ?></div></li>
      <li><input type="submit" value="Submit" id="uploadBtn"></li>
    </ul>
    
  </form>
   
  <div id="progress">
        <div id="bar"></div>
        <div id="percent">0%</div >
  </div>
  <div id="message"></div>
</div>

  <?php
  }

  //Update the widget 
  function update( $new_instance, $old_instance ) 
  {
    $instance = $old_instance;
    
    //Strip tags from title and name to remove HTML 
    $instance['title'] = strip_tags( $new_instance['title'] );
  return $instance;
  }
  
  function form( $instance ) 
  {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      $title = __( 'New title', 'text_domain' );
    }
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'S3 bucket name' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php 
  }
}

?>