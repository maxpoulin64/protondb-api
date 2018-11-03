# Community ProtonDB API

This simple application takes the [ProtonDB database dumps](https://github.com/bdefore/protondb-data) from the [ProtonDB project](https://www.protondb.com/), stores it in a real database and serves a simple API to query it.


# Installing
These instructions haven't really been tested and only serve as a general guidance on how to set up the PHP application. This assumes you already have an nginx, PHP and MySQL setup going.

1. Configure the MySQL database
	1. Import the contents of `/contrib/initdb.sql` to your database
	1. Copy `/contrib/config.php` to `/config.php`
	1. Edit `/config.php` and input your MySQL credentials
1. Configure nginx to add the server defined in `/contrib/nginx.conf`.
1. (optional) Set up the protobdb-data repository
	1. Clone the [protondb-data](https://github.com/bdefore/protondb-data) repository to `/data.git`
	1. Copy `/contrib/post-merge.sh` to `/data.git/.git/hooks/` to automatically import new data whenever the git repository is updated
 	1. Add a cronjob or systemd timer to `git pull` the repository
