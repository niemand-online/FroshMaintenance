# Webdav

This project is aimed towards developers that need access to the file system of a shopware system but do not have ftp
or similar access. It enables the shopware installation to act as a webdav server and be accessed with a backend user
and a corresponding api key. All that is needed to access the file system is a backend user that is permitted to use the
api and the webdav interface.

Api permission can be granted in the user settings (where you can also find the api key) and webdav permission can be
granted in the acl settings where you have to add the privilege to a role of backend users.

After this initial setup you can use your webdav client of choice to access the file system under this URL:
`http(s)://<your-shop-domain.tld>/webdav/index/index/`  
