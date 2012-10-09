<?PHP
/*** 
	Example index.php
	
		Remember to create the users table as specified in the readme
		This example requries a things table:

			CREATE TABLE IF NOT EXISTS `things` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `title` varchar(255) NOT NULL,
			  `content` text NULL,
			  `parent_id` int(10) unsigned NULL,
			  `user_id` int(10) unsigned NOT NULL,  
			  PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=utf8;
			 
	 	User's own lists
			

***/
require 'Slim/Slim.php';
require 'Paris/idiorm.php';
require 'Paris/paris.php';

/** configure your db **/
ORM::configure('mysql:host=localhost;dbname=slimusers_lists_DB');
ORM::configure('username', 'root');
ORM::configure('password', '');

//setup templates
require_once 'Twig/Autoloader.php';
require 'Slim/Views/TwigView.php';
TwigView::$twigDirectory = dirname(__FILE__) . '/templates';

//user plugin requires this global
$baseURL = '/SlimUsersInParis/examples/lists';

//Settings for Slim.php
//Users plugin requires encrypted cookies
$app = new Slim(array(
    'view' => new TwigView(),
    'cookies.secret_key'  => 'n6289()nfd328*^&432n&,;13*',	//Change me
    'cookies.lifetime' => time() + (1 * 24 * 60 * 60), // = 1 day
    'cookies.cipher' => MCRYPT_RIJNDAEL_256,
    'cookies.cipher_mode' => MCRYPT_MODE_CBC
));


require '../../../SlimUsersInParis/app.php';	//change if running example in different folder

$app->hook('slim.before', function () use ($app) {
    global $baseURL;
    //depending on which app this is in, that coudl chagne?
    $app->view()->appendData(array('baseURL' => $baseURL));
    $_user = User::current();
    if($_user){
        $app->view()->appendData(array('current_user' => $_user ));
    }
});

// require user to be logged in to access this page.
$app->get("/", $SUiP_current_user, function() use($app){
	global $USER,$baseURL;
	if(isset($USER->username)){
		//if logged in, redirect to things
		$app->redirect($baseURL."/things");
	}else{
		echo "You are not logged in. <a href='".$baseURL."/login'>Login here</a> or <a href='".$baseURL."/signup'>Signup here</a>";
	}
});

/*** Lists/Things sample app code 

		- Create lists
			- create lists in a list
				- it's all lists
		- Lists belong to a user

***/
	


class Thing extends Model {
    public static $_table = 'things';
    public function children(){
    	return $this->has_many("Thing","parent_id");
    }
    public function parent(){
    	return $this->belongs_to("Thing","parent_id");
    }
    public function full_delete(){
    	//delete all children, then delete me
    	$context = $this;
    	$items = self::recursive_find($context);
    	//delete all items
    	foreach($items as $item){
			$i = ORM::for_table('things')->find_one($item);
			$i->delete();
    	}
    	$this->delete();
    }
    private function recursive_find($thing){
    	$items_to_delete = array();
    	$children = $thing->children()->find_many();
		foreach($children as $child){
    		$items_to_delete[] = $child->id;
    		$items_to_delete = array_merge($items_to_delete, self::recursive_find($child));
    	}
    	return $items_to_delete;
    }
}

$app->get("/things", $SUiP_requires_login, function() use($app){
	global $USER;
	//I'm only allowed to see my own
    $things = Model::factory('Thing')
    	->where("user_id", $USER->id)
    	->where_null("parent_id")		//parent is null when viewing top level listing
    	->find_many();

    $app->render("thing/list.html", array("things"=>$things));
});

$app->get("/things/new",  $SUiP_requires_login, function() use($app){
	$app->render("thing/new.html");
});

$app->post('/things',  $SUiP_requires_login, function() use($app){
	global $USER, $baseURL;
	$thing = $app->request()->params('thing');
	$newThing = null;

	if(!$newThing){
		$newThing = Model::factory('Thing')->create();
	}
	$newThing->user_id = $USER->id;	//this user
	$newThing->title = $thing['title'];
	$newThing->content = $thing['content'];
	if(isset($thing["parent_id"])){
		$newThing->parent_id = $thing['parent_id'];
	}
	$newThing->save();
	if(isset($thing["parent_id"])){
		$app->redirect($baseURL."/things/".$newThing->parent_id);
	}else{
		$app->redirect($baseURL."/things");
	}
});
$app->get("/things/:thing_id/delete",  $SUiP_requires_login, function($thing_id) use($app){
	global $USER, $baseURL;
	//show this course
	$thing = Model::factory('Thing')->find_one($thing_id);
	if(!$thing || $thing->user_id != $USER->id ){
		return $app->render("@user/error", array("message"=>"You do not have permission"));
	}
	//get all of this things children
	$thing->full_delete();

	if($app->request()->get('r'))
		$app->redirect($baseURL."/things/".$app->request()->get('r'));
	elseif($thing->parent_id)
		$app->redirect($baseURL."/things/".$thing->parent_id);
	else
		$app->redirect($baseURL."/things");
});
$app->get("/things/:thing_id",  $SUiP_requires_login, function($thing_id) use($app){
	global $USER;
	//show this course
	$thing = Model::factory('Thing')->find_one($thing_id);
	if(!$thing || $thing->user_id != $USER->id){
		return $app->render("@user/error", array("message"=>"You do not have permission"));
	}

	$children = $thing->children()->find_many();
	$breadcrumb_items = array();
	$parent = $thing;
	$i=0;
	while($parent->parent_id!=null && $i<5){
		$parent = $parent->parent()->find_one();
		array_unshift($breadcrumb_items,$parent);
		$i++;
	}
	if($i == 5){
		array_unshift($breadcrumb_items, (object) array("title"=>"...", "id"=>$parent->id));
	}
	array_push($breadcrumb_items,$thing);
	$app->render("thing/show.html",
		array(
			"thing"=>$thing
			, "breadcrumb" => $breadcrumb_items
			, "children" => $children
		));
});

$app->run();

?>