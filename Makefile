run:
	docker run -d --name bind9 -p 53:53 -p 53:53/udp --env-file envfile sfroemken/bind

build:
	docker build -t sfroemken/bind .

console:
	docker run -it -p 53:53 -p 53:53/udp --env-file envfile --rm sfroemken/bind sh
