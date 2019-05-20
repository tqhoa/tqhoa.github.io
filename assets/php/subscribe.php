<?php
  require_once('vendor/MCAPI.class.php');

  // version, 1 = save to txt file, 2 = mailchimp
  $version = 1;

  // http://admin.mailchimp.com/account/api/
  define('MC_API_KEY', 'YOUR_MAILCHIMP_APIKEP_HERE');

  // http://admin.mailchimp.com/lists/
  define('MC_LIST_ID', 'YOUR_MAILCHIMP_ID_HERE');

  // email txt file path
  // strong recommend edit to a hard to guess file name, e.g. email_hard_to_guess.txt and rename the txt file
  // if this function not work, you may need to edit the txt file permission to 666 or above
  define('FILE_PATH', 'email.txt');

  /* PHP headers with AJAX */
  header('Expires: 0');
  header('Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0');
  header('Pragma: no-cache');
  /* Json */
  header('Content-type: application/json');

  /* AJAX check  */
  if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    /* AJAX function */
    extract($_POST, EXTR_PREFIX_ALL, 'form');

    try {
      if ($version === 1) {
        fn_saveToFile($form_email);
      } elseif ($version === 2) {
        fn_mailChimp($form_email);
      }
    } catch(Exception $e) {
      $message = $e->getMessage();
			$code = $e->getCode();
		}

    echo json_encode(array(message => $message, code => $code));

  } else {
    exit('Only allow access via AJAX');
  }

  // save to file
  function fn_saveToFile($form_email) {
    if (defined('FILE_PATH')) {
      if (strpos(file_get_contents(FILE_PATH), $form_email) == true) {
        throw new Exception('Subscription already exists', 1);
      } else {
        file_put_contents(FILE_PATH, date("Y-m-d H:i:s").' - '.$form_email."\n", FILE_APPEND | LOCK_EX); // LOCK_EX available since PHP 5.1.0+
        throw new Exception('Thank you! You have been successfully subscribed', 0);
      }
    } else {
      throw new Exception('An error occurred. Please try again later', 1);
    }
  }

  // mailchimp
  function fn_mailChimp($form_email) {
    if(defined('MC_API_KEY') && defined('MC_LIST_ID')) {
      $api = new MCAPI(MC_API_KEY);
      if ($api->listSubscribe(MC_LIST_ID, $form_email) !== true) {
        if ($api->errorCode == 214) {
          throw new Exception('Subscription already exists', 1);
        } elseif ($api->errorCode == 104) {
          throw new Exception('Invalid API KEY', 1);
        } elseif ($api->errorCode == 200) {
          throw new Exception('Invalid LIST ID', 1);
        } else {
          throw new Exception($api->errorMessage, 1);
        }
      } else {
        throw new Exception('Thank you! We just sent you a confirmation email', 0);
      }
    } else {
      throw new Exception('API KEY / LIST ID not defined', 1);
    }
  }
?>