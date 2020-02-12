<?php

/*
For demo, place this file in a folder, traverse to that folder and run the following commands.
You can then visit localhost:8000 in the browser to see the form in action
  
composer require symfony/security-csrf
php -S localhost:8000
*/
require 'vendor/autoload.php';

use \Symfony\Component\Security\Csrf\CsrfToken ;
use \Symfony\Component\Security\Csrf\CsrfTokenManager;

$csrf  = new CsrfTokenManager();
$token = new CsrfToken("_csrf", $_POST["_csrf"] ?? '');

?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Example</title>
</head>
<body>
    <section style="max-width:600px;margin:0 auto;">
        <?php if (isset($_POST)): ?>
            <pre><?php print_r($_POST); ?></pre>
            <?php if ($csrf->isTokenValid($token)): ?>
                <strong>CSRF_PASS</strong>
            <?php else: ?>
                <strong>CSRF_FAIL</strong>
            <?php endif; ?>
        <?php endif; ?>
        <hr />
        <form method="POST">
            <input type="text" name="name" value="" placeholder="Your name" />
            <input type="hidden" name="_csrf" value="<?php echo $csrf->getToken("_csrf"); ?>" />
            <button type="submit">Submit</button>
        </form>
    </section>
</body>
</html>