<?php
session_start();
$config['root_dir'] = (dirname(__FILE__) . '/');
require_once $config['root_dir'] . 'includes/bootstrap.inc';
require_once $config['root_dir'] . 'includes/email.inc';
require_once $config['root_dir'] . 'theme/admin_header2.php';

function feedbackForm($name = '', $email = '', $feedback = '')
{
    if (!$email) {
        $email = $_SESSION['username'];
        if (!$name) {
            $name = $_SESSION['name'];
        }
    }
    $html_name=htmlspecialchars($name, ENT_QUOTES);
    $html_email=htmlspecialchars($email, ENT_QUOTES);
    $html_feedback = $feedback;

    $rv = <<< HTML
<form action="" method="post">
<label for="email">Your email (required) </label>
<input type="email" name="email" value="$html_email" autocomplete="email"/><br>
<label for="name">Your name (optional) </label>
<input name="name" value="$html_name" autocomplete="name"/><br>
<p>Message:
<p><textarea name="feedback" cols="80" rows="20">$html_feedback</textarea>
<div class="g-recaptcha" data-sitekey="6LcP1WMUAAAAAO86jz4thPRLPpYL8H9waxjWA-G3"></div><br>
<input type="submit" value="Send feedback" /><br><br>
</form>
HTML;
    return $rv;
}

// us_ prefix means "unsafe", i.e. the raw input from user
$us_name=isset($_POST['name']) ? $_POST['name']:'';
$us_email=isset($_POST['email']) ? $_POST['email']:'';
$us_feedback=isset($_POST['feedback']) ? $_POST['feedback']:'';
// Note: Doublequotes (") required; singlequotes fail:
$us_feedback = str_replace('\r\n', "\n", $us_feedback);
// Try to remove the \ being inserted before all ' characters.
$us_feedback = stripslashes($us_feedback);
$baseurl = $config['base_url'];

$footer_div = 1;
$capcha_pass = false;
if (isset($_POST["g-recaptcha-response"])) {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = array(
        'secret' => '6LcP1WMUAAAAAJbn85XZb_UIzpKCP4Fu8ePwjmUM',
        'response' => $_POST["g-recaptcha-response"]
    );
    $options = array(
        'http' => array (
            'method' => 'POST',
            'content' => http_build_query($data)
         )
    );
    $context  = stream_context_create($options);
    $verify = file_get_contents($url, false, $context);
    $verify = json_decode($verify);
    if ($verify->success==true) {
        $capcha_pass = true;
    }
}
if ($us_feedback && $us_email && $capcha_pass) {
    send_email_from(setting('feedbackmail'), 'T3 Feedback', $us_email,
    "Name: $us_name
    Email: $us_email
    Database: $baseurl

Feedback:
$us_feedback");

    echo "<h3>Thank you for your feedback. It has been sent to the T3 curators.</h3>";
} elseif ($_POST) {
    ?>
    <h1>Feedback</h1>
    Please send your questions, suggestions, or complaints to the
    T3 curators.
    <p>
    <?php
    if (!$capcha_pass) {
        echo "<h3 style='color: red'>Please check the reCAPTCHA</h3>";
    }
    if (!$us_email) {
        echo "<h3 style='color: red'>Your email address is required.</h3>";
    }
    if (!$us_feedback) {
        echo "<h3 style='color: red'>No message entered.</h3>";
    }
    echo feedbackForm($us_name, $us_email, $us_feedback);
} else {
    ?>
    <h1>Feedback</h1>
    Please send your questions, suggestions, or complaints to the
    T3 curators.
    <p>
    <?php
    echo feedbackForm($us_name, $us_email, $us_feedback);
}

require_once $config['root_dir'] . 'theme/footer.php';
