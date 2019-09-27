# PHP-CLI TP-Link WR1043ND
PHP-CLI Script for controlling TP-LINK WR1043ND routers on linux based systems. Tested and developed on a TP-LINK WR1043ND V5 router.

## Configuring:
Open file `tp_link_wr1043nd.php` and edit the following variables with your data:

    $router_ip = '192.168.0.1';
    $username = 'USERNAME_GOES_HERE';
    $password = 'PASSWORD_GOES_HERE';
    $cookie = '/tmp/cookie_router.txt';

## Usage: 
Call PHP script `tp_link_wr1043nd.php` from command line with one of the following arguments: 
- **restart** - reboot the router
- **summary** - get router configuration parameters
- **clients** - get router connected clients

## Example:
`php tp_link_wr1043nd.php restart` - will restart the router.
