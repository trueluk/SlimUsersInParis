<?PHP
/*** 
	Example index.php
	
		Remember to create the users table as specified in the readme

***/
require 'Slim/Slim.php';
require 'Paris/idiorm.php';
require 'Paris/paris.php';

/** configure your db **/
ORM::configure('mysql:host=localhost;dbname=slimusersDB');
ORM::configure('username', 'root');
ORM::configure('password', '');

//setup templates
require_once 'Twig/Autoloader.php';
require 'Slim/Views/TwigView.php';
TwigView::$twigDirectory = dirname(__FILE__) . '/templates';

//user plugin requires this global
$baseURL = '/SlimUsersInParis/examples/simple';

//Settings for Slim.php
//Users plugin requires encrypted cookies
$app = new Slim(array(
    'view' => new TwigView(),
    'cookies.secret_key'  => 'n4328i489431niNFN&,;13*',	//Change me
    'cookies.lifetime' => time() + (1 * 24 * 60 * 60), // = 1 day
    'cookies.cipher' => MCRYPT_RIJNDAEL_256,
    'cookies.cipher_mode' => MCRYPT_MODE_CBC
));


require '../../../SlimUsersInParis/app.php';	//change if running example in different folder

$app->hook('slim.before', function () use ($app) {
    global $baseURL;
    //depending on which app this is in, that coudl chagne?
    $app->view()->appendData(array('baseUrl' => $baseURL));
    $_user = User::current();
    if($_user){
        $app->view()->appendData(array('current_user' => $_user ));
    }
});

// require user to be logged in to access this page.
$app->get("/", $SUiP_current_user, function() use($app){
	global $USER,$baseURL;
	if(isset($USER->username)){
		echo "You are logged in as " . $USER->username. ". <a href='".$baseURL."/logout'>Logout</a>";
		echo "<br/><a href='".$baseURL."/private'>View private page</a>";
	}else{
		echo "You are not logged in. <a href='".$baseURL."/login'>Login here</a> or <a href='".$baseURL."/signup'>Signup here</a>";
	}
});
$app->get("/private", $SUiP_requires_login, function() use($app){
	global $USER,$baseURL;
	echo "<h1>Private Page</h1>";
	echo "You are logged in as " . $USER->username. ". <a href='".$baseURL."/logout'>Logout</a>";
});

$app->run();

?>