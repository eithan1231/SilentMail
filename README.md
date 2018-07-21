# Silent mail
is a free and open source mail program. It currently only has the support of receiving mail, but plans to send mail are being worked on. If you are looking for a live demo, head over to https://www.silentmail.us. The UI part of things are a we-bit bad, but it functions as needed.

# Known Bugs
As some of you might notice, there are many bugs. Here are some that are known.
1) URL Working Path failing to work correctly in router.php

# Installation

## Prerequisites
You will need atleast 2 servers. One SMTP, and one WEB. Ideally you should have a caching layer (Varnish, NGINX), but this is not required.

## 1) Configuring web server.
The web server will need to be apache2, with mod-rewirte plugin, mysqli plugin, and the gd plugin.

## 2) Configuring configuration file.
The configuration file for this project can be found under /WEB/lib/config.php . This file has all essential configuration settings. Read through this file, read comments, do as they say. You mainly just need to change the MySQL credentials, mail-domain, trusted hosts, seed (towards bottom), and project name. You may also need to make sure it's not in development mode.

## 3) Setting up nodes.
Due to the administration panel not being complete, you will need to manually configure the nodes. This will involve executing SQL queries.

### 3.1) Put node onto a server.
To firstly configure a node, you need to have the node-js application running on a server with the port 25 forwarded to it. To put the node onto a server, you want to put the files in to a folder of your choise.

### 3.2) Insert node interface (node api) key.
You want to insert a row to the table "out_node". The ID parameter can be left blank. IS loopback is whether the out-node is a local connection. creator_user_id is who created the node. date parameter is the date when this was made. And finally, token, is the authentication key for the node. Note this down for the next step.

### 3.3) Node configuration.
The node configuration is straight forward. Firstly you want to get config open in a text editor. Then you should set the config.smtp.hostname to the hostname of this node. Next you want to get the node key (from the sql query you exexcuted). Then you want to get the local ip of the web server, and the node api path on your web server. This may change depending if the mail php files are stored at your web servers root. After you get the local ip, and the node api path, update the config.node.template. In the default value, "localhost" is where you want to put the local ip of the web server, and the /node/ is the path to the node api.

### 3.4) Running the node
To start the node, you need to run it within a screen. If you dont know what a screen is, google "Linux Screens." They basically allow you to run things in the background, that are not services. Very helpful tool. So to start a screen, simply type "screen." Then run the main script.

### 3.5) MX record to this node.
For the love of god, don't forget to point our domains mx record to this node.
http://lmgtfy.com/?q=forwarding+mx+record+to+an+ip+address
