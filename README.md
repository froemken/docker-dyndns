# docker-dyndns

This docker image is based on Alpine 3.8 with configured bind9 DNS server, PHP7
and lighttpd server to update your DNS entries. With this image you can
host your own DynDns server. Have fun.

This service is designed to have a Nameserver configured
at a subdomain. See below.

## Usage

### Create subdomains at your hoster

You have to create two subdomains

**dyndns.example.com**

This is the main domain which will keep all subdomains
like homeserver.dyndns.example.com, ...

* Host: dyndns
* Type: NS
* Destination: ns.example.com

**ns.example.com**

* Host: ns
* Type: A
* Destination: IP-Address of your server

### Update envfile

Set `ZONE` to the configured subdomain `dyndns.example.com`.
Set `PUBLIC_DNS_SERVER` to `ns.example.com`.
Set `PUBLIC_IP_ADDRESS` to the IP address of your server.

### Configure users

The users are configured in JSON-Format. See example:

```
{
  "test1": {
    "password": "098f6bcd4621d373cade4e832627b4f6",
    "subdomains": [
      "test",
      "homeserver"
    ]
  },
  "test2": {
    "password": "098f6bcd4621d373cade4e832627b4f6",
    "subdomains": [
      "example"
    ]
  }
}
```

Where `test1` and `test2` are the usernames. You can configure multiple subdomains
for each user. Please add ONLY the first part of hostname as subdomain. The passwords are 
MD5 hashed. You have to hash them curently on your own. Sorry. In this example the hashed
password is `test`.

### Start server

```
make build
make run
```

### Configure Router or whatever

The URI has to look like that, if you use FritzBox:

`http://sfroemken.de:5380/update.php?subdomain=<domain>&username=<username>&password=<pass>&ip=<ipaddr>`

else you have to replace the placeholders with the original values

### ToDo

Currently I don't have support for IPv6.

There is no check to prevent a mass of update requests within seconds, which may crash your server.

There is no check, if someone tries tp bruteforce the usernames and passwords.

### DEV

You can add `&debug=1` to URI. In that case display_errors will be activated
and you will see all mit die('ErrorMessage') calls.

You can use `make console` and start server manually with
```
/root/start.sh
named
php-fpm7
lighttpd -f /etc/lighttpd/lighttpd.conf
```

Use this to update your Zone entry:
```
/root/nsclient_update.sh [newSubDomain] [newIpAddress]
```

F.e. `/root/nsclient_update.sh homeserver 123.456.987.654`
