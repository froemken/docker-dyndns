FROM alpine:3.8

MAINTAINER Stefan Froemken <froemken@gmail.com>

RUN apk --update add \
	lighttpd \
	bind \
	bind-tools \
	iputils \
	php7 \
	php7-iconv \
	php7-json \
	php7-cgi \
	php7-fpm \
	fcgi && \
	rm -rf /var/cache/apk/*

EXPOSE 53 80

RUN addgroup lighttpd named
COPY users.json /var/bind/users.json
COPY lighttpd.conf /etc/lighttpd/lighttpd.conf
COPY www.conf /etc/php7/php-fpm.d/www.conf
COPY named.conf /etc/bind/named.conf
COPY setup.sh /root/setup.sh
COPY ./www /var/www
RUN chmod +x /root/setup.sh
RUN rm -rf /var/www/localhost

CMD \
	/bin/sh -c /root/setup.sh && \
	/usr/sbin/named -c /etc/bind/named.conf -u named && \
	/usr/sbin/php-fpm7 && \
	/usr/sbin/lighttpd -D -f /etc/lighttpd/lighttpd.conf
