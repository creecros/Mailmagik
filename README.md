## Checkout our latest project
[![](https://raw.githubusercontent.com/docpht/docpht/master/public/assets/img/logo.png)](https://github.com/docpht/docpht)

- With [DocPHT](https://github.com/docpht/docpht) you can take notes and quickly document anything and without the use of any database.
-----------
[![Latest release](https://img.shields.io/github/release/creecros/kbphpimap.svg)](https://github.com/creecros/MarkdownPlus/releases)
![GitHub license](https://img.shields.io/github/license/Naereen/StrapDown.js.svg)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/creecros/kbphpimap/graphs/contributors)
![Open Source Love](https://badges.frapsoft.com/os/v1/open-source.svg?v=103)
![Downloads](https://img.shields.io/github/downloads/creecros/kbphpimap/total.svg)

**:star: If you use it, you should star it on Github!**
*It's the least you can do for all the work put into it!*

# :magic_wand: Mailmagik for Kanboard

Have you ever asked yourself, how do I send an email to Kanboard to create a task? 
Did someone suggest the Mailgun Plugin, and you got stumped, annoyed, and finally figured it out to realize that it cost your hard earned money to keep using it?
Did someone then suggest something like Zapier and the API to try to accomplish this, and you found yourself asking WTF are they talking about? 

*WELL THIS IS THE PLUGIN FOR YOU!*

Install this plugin today, connect to an IMAP server of your choosing, and boom! You will be making Mailmagik in no time!

So come on, give it a go, and let's all make Mailmagik happen together!

This plugin allows you to connect Kanboard directly to an IMAP server. Once connected, you can:

1. Send emails directly to a task
2. Send emails to a project to automatically be converted into a task within the project
3. Send emails to a task to automatically convert into a task comment

Once installed, setup in config:
![image](https://user-images.githubusercontent.com/26339368/216668816-a7a00c09-7594-4fda-8d7a-2f59dc6c0028.png)

- Example to send an email directly to a task: `Task#1<myemail@email.com>`
- Example to send an email to a project for task conversion: `Project#1<myemail@email.com>`
- Example to send an email to a task for comment conversion: `CommentOnTask#1<myemail@email.com>`

Emails sent to a task can be found in the Task uner the "Task Email" icon on the sidebar:
![image](https://user-images.githubusercontent.com/26339368/216670260-ddffad7f-62ee-4297-ad73-3ec03d9fb04e.png)


In order to setup automatic conversions of email, you will need to add the Actions to the project.

When sending emails for automatic Task Conversion, MailMagik can parse the subject line to add attributes to the Task:

|in Subject|Effect
|-|-
|d:YYYY-MM-DD| Set due date
|s:YYYY-MM-DD |Set start date
|p:n |Set priority n
|c:name| Set category 'name'
|t:name| Add tag 'name', may be repeated. Non-existing tags will be created, beware of typos.

A subject line containing

> Test c:SQL p:3 d:2023-02-28 s:2023-02-20

will create a priority 3 task named "Test", category "SQL", start on Feb 20 and due on Feb 28.

![image](https://user-images.githubusercontent.com/2079289/217609719-e1fecea5-0616-4cb7-be31-7db6f2c418c1.png)

**Plugin Author:** _[creecros](https://github.com/creecros)_

**Collaborators and Contributors:** 
- _[alfredbuehler](https://github.com/alfredbuehler)_
- _[aljawaid](https://github.com/aljawaid)_

# Requirements

https://github.com/barbushin/php-imap#requirements

If you are using the docker containter of Kanboard, simply remote into it and:
```
apk update
apk add php-imap
apk add php81-fileinfo
```

**PHP 7.4**
Check:
```
php -m | grep -i imap
```

```
$ sudo apt-get install php-imap
$ sudo apt-get install php7.4-imap
$ sudo apt-get install -y php-fileinfo
```
Run both commands


# Install

## Automatically

1. If your Kanboard installation is configured to install from the app, simply find it in the plugins directory and choose install.
2. Restart your server 


## Manually

1. Download the latest versions supplied zip file, it should be named `Mailmagik-x.xx.x.zip`
  - I advise not to install from source or master

2. Unzip to the plugins folder.
  - your folder structure should look like the following:
```
plugins
└── Mailmagik            <= Plugin name
    ├── Action  
    ├── Assets  
    ├── Controller  
    ├── Template
    ├── vendor
    ├── LICENSE
    ├── CHANGELOG.MD 
    ├── Makefile 
    ├── Plugin.php   
    ├── README.md
    ├── composer.json
    └── composer.lock
```

3.) Restart your server
