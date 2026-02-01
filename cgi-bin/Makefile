CC=gcc
CFLAGS=-O2 -Wall -Wextra

all: hello-html-c-xijian hello-json-c-xijian environment-c-xijian echo-c-xijian

hello-html-c-xijian: hello-html-C-xijian.c
	$(CC) $(CFLAGS) -o $@ $<

hello-json-c-xijian: hello-json-C-xijian.c
	$(CC) $(CFLAGS) -o $@ $<

environment-c-xijian: environment-C-xijian.c
	$(CC) $(CFLAGS) -o $@ $<

echo-c-xijian: echo-C-xijian.c
	$(CC) $(CFLAGS) -o $@ $<

clean:
	rm -f hello-html-c-xijian hello-json-c-xijian environment-c-xijian echo-c-xijian
