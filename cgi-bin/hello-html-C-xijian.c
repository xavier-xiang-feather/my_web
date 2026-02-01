// hello-html-c-xijian.c
#include <stdio.h>
#include <stdlib.h>
#include <time.h>

static const char* env_or(const char* key, const char* fallback) {
    const char* v = getenv(key);
    return (v && v[0]) ? v : fallback;
}

int main(void) {
    printf("Content-Type: text/html\r\n\r\n");

    time_t now = time(NULL);
    char current[64];
    strftime(current, sizeof(current), "%Y-%m-%d %H:%M:%S", localtime(&now));

    const char* ip_addr = env_or("REMOTE_ADDR", "unknown");

    printf(
        "<!DOCTYPE html>\n"
        "<html>\n"
        "<head>\n"
        "    <title>hello html c</title>\n"
        "</head>\n"
        "<body>\n"
        "    <h1>Hello! Welcome to my web!</h1>\n"
        "    <p>Greeting from Xijian Xiang<p>\n"
        "    <p>Language: C </p>\n"
        "    <p>Generated at %s</p>\n"
        "    <p>IP address: %s</p>\n"
        "</body>\n"
        "</html>\n",
        current, ip_addr
    );

    return 0;
}
