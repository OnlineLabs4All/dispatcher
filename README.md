Experiment Dispatcher
=====================

The experiment dispatcher is a service that offers some functionalities to lab owners and simplify the deployment of online laboratories. It specifies a simple interface for lab owners.

## Installation

1) Download or clone the source code with:
```
$ git clone https://github.com/OnlineLabs4All/dispatcher.git
```
2) *cd* into the downloaded project folder and run
```
$ composer install
```
During composer installation you have to set some parameters. Press Enter to take the (*default*) value.
Values, which are usually not default:
- database_name: Use name of database created in step 2)
- database_password: Your SQL password.
- secret: PHP session token - write something...

3) Create a database
```
$ php app/console doctrine:database:create
```

4) generate the database tables
```
$ php app/console doctrine:schema:update --force
```

5) Create an admin account in the *site_user* table:
- id (AUTO_INCREMENT --> leave empty)
- username
- password (Has to be encrypted as blowfish hash)
- firstName
- lastName
- email
- role (**ROLE_ADMIN**, or **ROLE_USER** for normal users)
- is_active (set to **1**)

6) **OPTIONAL** - Check if system is ready to run Symfony:
```
$ php app/check.php
```


## Run the dispatcher in debug mode

1) *cd* into the project folder and run
```
$ php app/console server:run
```
or (if an alternative port is needed)
```
$ php app/console server:run 127.0.0.1:8000
```

2) Open your browser and go to
```
http://localhost:8000
```
