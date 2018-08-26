#!/bin/sh

[ -z "$ZONE" ] && echo "ZONE not set" && exit 1;

if ! grep 'zone "'$ZONE'"' /etc/bind/named.conf > /dev/null
then
	echo "Creating TSIG Key..."
	tsig-keygen -r /dev/urandom | tee /etc/bind/tsig-key.private >> /etc/bind/named.conf

	echo "Creating zone..."
	cat >> /etc/bind/named.conf <<EOF
zone "$ZONE" {
	type master;
	file "$ZONE.zone";
	allow-update { !{ !localnets; any; }; key tsig-key; };
};
EOF
fi

if [ ! -f /var/bind/$ZONE.zone ]
then
	echo "Creating zone file..."
	cat > /var/bind/$ZONE.zone <<EOF
\$ORIGIN .
\$TTL 180				; 3 minutes
$ZONE	IN	SOA	${PUBLIC_DNS_SERVER}. root.localhost. (
				74	; serial
				3600	; refresh (1 hour)
				900	; retry (15 minutes)
				604800	; expire (1 week)
				86400 )	; minimum (1 day)

		IN	NS	${PUBLIC_DNS_SERVER}.
		IN	A	$PUBLIC_IP_ADDRESS

\$ORIGIN ${ZONE}.
\$TTL 180				; 3 minutes
EOF
fi

if [ ! -f /root/nsclient_update.sh ]
then
	echo "Creating nsclient_update.sh..."
	cat > /root/nsclient_update.sh <<EOF
#!/bin/sh
HOST=\$1.${ZONE}.
ADDR=\$2
echo "server 127.0.0.1" > /root/nsupdate.txt
echo "debug yes" >> /root/nsupdate.txt
echo "zone $ZONE" >> /root/nsupdate.txt
echo "update delete \$HOST A" >> /root/nsupdate.txt
echo "update add \$HOST 180 A \$ADDR" >> /root/nsupdate.txt
echo "show" >> /root/nsupdate.txt
echo "send" >> /root/nsupdate.txt
nsupdate -k /etc/bind/tsig-key.private /root/nsupdate.txt
EOF
fi

chown root:named /var/bind
chown named:named /var/bind/*
chmod 770 /var/bind
chmod 644 /var/bind/*
chmod +x /root/nsclient_update.sh
