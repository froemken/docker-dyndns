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

If you get error `bind: address already in use` you should try deactivating local DNS server:

```
sudo nano /etc/systemd/resolved.conf
```

Change line `#DNSStubListener=yes` to `DNSStubListener=no` and save file. This will deactivate local DNS Server
which listen at 127.0.0.52:52.

Create new file: `/usr/lib/systemd/resolv.conf` with following content:

```
nameserver 8.8.8.8
nameserver 8.8.4.4
```

I'm using the public DNS Servers of Google here.
Remove resolv.conf from /etc

```
sudo rm /etc/resolv.conf
```

And link our new created file to /etc:

```
sudo ln -sf /usr/lib/systemd/resolv.conf /etc/resolv.conf
```

Now restart resolv service:

```
sudo systemctl restart systemd-resolved
```

Now try to start container again:

`docker start [container-id]`

### Configure Router or whatever

The URI has to look like that, if you use FritzBox:

`http://your-domain.de:5380/update.php?subdomain=<domain>&username=<username>&password=<pass>&ip=<ipaddr>`

else you have to replace the placeholders with the original values

### ToDo

Currently I don't have implemented support for IPv6.

There is no check to prevent a mass of update requests within seconds, which may crash your server.

There is no check, if someone tries to bruteforce the usernames and passwords.

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

### BUG: Update does not work

Login into my docker image

```
make exec
```

Check owner and group for directory `/var/bind/`. I should look like:

```
drw-r--r--    2 named    named         4096 Aug 31 20:35 dyn
-rw-r--r--    1 named    named         2878 May 21 22:30 named.ca
-rwxr-xr-x    1 named    named          452 Nov  9 23:32 nsclient_update.sh
-rw-r--r--    1 lighttpd lighttpd       171 Nov  9 23:32 nsupdate.txt
drw-r--r--    2 named    named         4096 Aug 31 20:35 pri
lrwxrwxrwx    1 root     root             8 Aug 31 20:35 root.cache -> named.ca
drw-r--r--    2 named    named         4096 Aug 31 20:35 sec
-rw-r--r--    1 named    named          242 Aug 28 17:54 users.json
```
