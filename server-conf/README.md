LIGHTTPD INSTALL

- Configure your Lighttpd web server with PHP FastCGI and OpenSSL support - 6paster requires HTTPS to work.

- Add the server configuration example that you find at 'lighttpd/lighttpd-conf.txt' to your Lighttpd configuration file.

- Copy the 'lighttpd/index.php' to the 'webroot/' directory of your 6paster installation.

- Reload the Lighttpd process.

NGINX INSTALL

- Configure your NGINX web server with PHP FastCGI and OpenSSL support - 6paster requires HTTPS to work.

- Add the server configuration example that you find at 'nginx/nginx-conf.txt' to your NGINX configuration file.

- Copy the 'nginx/index.php' to the 'webroot/' directory of your 6paster installation.

- Reload the NGINX process.

NOTES

- The content of 'webroot/.htaccess' is ignored by the web server.



Information: sam@arahant.net
