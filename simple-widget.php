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
use Aws\S3\Model\AcpBuilder;

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

  function MY_Widget() 
  {
    $widget_ops = array( 'classname' => 'example', 'description' => __('A widget that displays the authors name ', 'example') );
    
    $control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'example-widget' );
    
    $this->WP_Widget( 'example-widget', __('Example Widget', 'example'), $widget_ops, $control_ops );

    add_action('init', array($this, 'widget_init') );
    add_action( 'wp_ajax_nopriv_upload_to_s3', array($this, 'upload_to_s3'), 1 );

    //add meta box in S3 post
    add_action( 'add_meta_boxes', array($this, 's3_meta_box_init') );
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

  function widget_init(){
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

    $args = array('name'=>'CDS Upload',
            'id'=>'cds-upload',
            'description' => 'Upload CDS Video to MC Backend',
            'before_widget' => '<div class="widget">',
            'after_widget' => '</div>',
            'before_title' => '',
            'after_title' => '');
    register_sidebar($args);

    //remove editor for s3videos post
    remove_post_type_support('s3videos', 'editor');

  }

  function upload_to_s3()
  {
    self::cds_log($_POST["fullname"]);
    self::cds_log($_POST["ytusername"]);
    self::cds_log($_POST["email"]);

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
          $result = $client->putObject(array(
              'Bucket' => 'cds-campaign',
              'Key'    => $_FILES["Filedata"]["name"],
              // 'Body'   => fopen('/tmp/sample_mpeg4.mp4', 'r'),
              'Body'   => fopen($_FILES["Filedata"]["tmp_name"], 'r'),
              // 'SourceFile' => $_FILES["Filedata"]["tmp_name"],
              'ACL'    => 'public-read',
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


      } catch (S3Exception $e) {
        self::cds_log($e);
        self::cds_log('error s3 putobject');
        $msg["error"] = "S3 Exception";
        // echo "There was an error uploading the file.\n";
      } catch(Exception $e){
        $msg["error"] = "exception";
      }

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

    ?>
<div class="uploadvideo"> 
  <form id="uploadForm" action="wp-admin/admin-ajax.php?action=upload_to_s3" method="post" enctype="multipart/form-data">
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
      <li><input type="submit" value="Ajax File Upload" id="uploadBtn"></li>
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