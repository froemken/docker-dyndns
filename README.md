# docker-dyndns

This docker image is based on bind9 DNS server, so you can
host your own DynDns server.

This service is designed to have the Nameserver configured
for a subdomain.

## Usage

### Create subdomains at your hoster

You have to create two subdomains

*dyndns.example.com*

This is the main domain which will keep all subdomains
like homeserver.dyndns.example.com, ...

*Host:* dyndns
*Type:* NS
*Destination:* ns.example.com

*ns.example.com*

*Host:* ns
*Type:* A
*Destination:* 123.123.123.123

### Update envfile

Set `ZONE` to the previous configured subdomain `dyndns.example.com`.
Set `PUBLIC_DNS_SERVER` to `ns.example.com`.
Set `PUBLIC_IP_ADDRESS` to the IP address of your server.

### Start server

`make build`
`make run`

### ToDo

Currently I don't have a Webserver configured, so it is not possible
to update the IP from your Router/FritzBox.

You can use `make console` and start server manually with
`/root/start.sh`
`/usr/sbin/named`
`/root/nsclient_update.sh [newSubDomain] [newIpAddress]`

F.e. `/root/nsclient_update.sh homeserver 123.456.987.654`