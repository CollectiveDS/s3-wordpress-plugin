wordpress-plugin-s3
===================
Description: Wordpress plugin that lets user uploads videos to a s3 bucket

Requires: php 5.3 or later

Prerequisite: 
1:  Needs api key, secret key in aws/aws-config.php

2: Needs google recaptcha public and private keys in recaptcha-config.php

Installation/Use:

1: Upload wordpress-plugin-s3 folder to plugins folder

2: Activate plugin

3: Go to Appearance->Widgets

4: Drag "s3 upload widget" to any widget area

Adding the plugin programmatically to wordpress

1: Insert this code: dynamic_sidebar('S3 Upload');

2: Go to Appearance->Widgets

3: Drag "s3 upload widget" to S3 Upload Widget Area

CHANGELOG

1.0.1
Minor Updates

  -Clean up some unused code
  
  -Create google recaptcha config file
  

1.0
Initial Release

  Features:
  
  -Upload videos to a s3 bucket
  
  -s3 bucket name configurable 
  
  -Google recaptcha



