SlimUsersInParis
=====

- Simple user model for Slim.php + Paris + Twig apps
- Includes route middleware for quick setup of Slim apps

## Dependencies:
* Slim.php (http://www.slimframework.com/)
* Idiorm & Paris (http://j4mie.github.com/idiormandparis/)
* Twig (http://twig.sensiolabs.org/)

## Purpose:

Slim.php is great for quickly prototying web apps in PHP, however, there is no User model. SlimUsersInParis adds a user class with routes for logging in/out, signing up, creating users, editing users and deleting users. A user's session is stored via encrypted cookies. Each password is randomly salted before being encrypted. There is crude support of user roles via the role column; however, ideally roles would be handled in a separate table.

## Example Usage:

1. Create a database and the users table

        CREATE TABLE IF NOT EXISTS `users` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `username` varchar(60) NULL,
          `email` text NULL,
          `password` varchar(255) NOT NULL,
          `salt` varchar(255) NOT NULL,
          `session_key` varchar(255) NOT NULL,
          `time_created` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `last_login` TIMESTAMP DEFAULT 0,
          `role` varchar(60) default 'user',
          PRIMARY KEY (`id`),
          UNIQUE (`username`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

2. Create an admin user with password = 'admin'

        insert into users (username, password, salt, role) values ('admin', '$2a$07$8m5iSbzdgfFGO7AhlRQJNOBAqKyjTczED.P03pBw1wbiP22XTNXee', '8m5iSbzdgfFGO7AhlRQJNWkUpX64DH21', 'admin');

3. Make sure path to SlimUsersInParis is accurate. The default works if accessing within the examples folder

        require '../../../SlimUsersInParis/app.php';

4. Check your $baseURL global. It should be set to the path of the app. The default assumes SlimUsersInParis is at the document root of the web server.

5. Navigate your browser to the examples/simple folder

6. Login with admin/admin

