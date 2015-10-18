#Git Auto Deployment 
Git duto deployment using POST deploy hooks that are offered by GitHub and BitBucket.

***
## Install
* create a directory for deployment control site
```bash
mkdir /var/www/deploy
cd /var/www/deploy
```
* clone this repo
```bash
git clone https://github.com/lkwdwrd/git-deploy.git .
```
* setup apache/nginx/other web-server site (ex. deploy.some.site) to /var/www/deploy 

## Setup
* fill deploy config with your repos
```php
$repos = array(
    'prism-code-highlighting' => array(
        'branch' => 'master',
        'path' => '/home/usr/example/wpcopilot.net/wp-content/plugins/prism-code-highlighting/'
    ),
    'another-plugin' => array(
        'branch' => 'deploy',
        'remote' => 'bbremote',
        'path' => '/home/usr/example/wpcopilot.net/wp-content/plugins/another-plugin/'
    )
);
```
* setup GitHub/Bitbucket POST hooks
 * GitHub (https://help.github.com/articles/post-receive-hooks) to http://deploy.some.site/github.php
 * Bitbucket (https://confluence.atlassian.com/display/BITBUCKET/POST+Service+Management) to http://deploy.some.site/bitbucket.php

### Private repos
* create local ssh key
```bash
ssh-keygen -t rsa -f ~/.ssh/id_rsa -C 'Bitbucket deploy'
```
* add public key as Deploy Key to your repo 

More: https://confluence.atlassian.com/pages/viewpage.action?pageId=271943168

## Usage
* commit and push 
## Common Issues

### Not able to pull from a private github repository
In order to be able to pull from a github repository, this script has to connect using a public key, you have to add the key to the .ssh folder of the user who is running the git-deploy script. And server has to trust on github authenticity without interactive prompt, you can fix this creating a .php file with this content and running only once, you should see a succesfully authentication message with some debug information.

```php
error_reporting(E_ALL);
header('Content-type: text/plain');
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
echo system( 'ssh -v -o "StrictHostKeyChecking no" git@github.com 2>&1' );
// Uncomment this lines to also check that pull is working
//chdir('/path/to/repository/');
//echo system('git pull origin master 2>&1'); // change master for your branch if needed
```

# More information
http://lkwdwrd.com/git-auto-deployment/
