WvWGuilds
=========

The files for wvwguilds.com

-index.php is the main homepage that lists the guilds currently claiming an objective in WvW
-allguilds.php is the list of all guilds in the database
-emblemforge2.php is the script that does the heavy lifting of creating the emblems and displaying them. This page is called by index.php and allguilds.php for each guild in the listing.
-wvwdaemon.php is the script to pull all the claiming guilds from all North American matches and add them to the database. It's run via a Cron Job every 5 minutes.
