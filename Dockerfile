FROM alpine:latest

MAINTAINER Stefan Froemken <froemken@gmail.com>

RUN apk --update add bind
RUN apk --update add bind-tools
RUN apk --update add iputils

EXPOSE 53

COPY named.conf /etc/bind/named.conf
COPY setup.sh /root/setup.sh
RUN chmod +x /root/setup.sh

CMD ["sh", "-c", "/root/setup.sh"]
CMD ["named", "-c", "/etc/bind/named.conf", "-g", "-u", "named"]