// hello-json-c-xijian.c
#include <stdio.h>
#include <stdlib.h>
#include <time.h>

static const char* env_or(const char* key, const char* fallback) {
    const char* v = getenv(key);
    return (v && v[0]) ? v : fallback;
}

// minimal JSON escape (enough for IP/time)
static void json_escape(const char* s) {
    for (; *s; s++) {
        unsigned char c = (unsigned char)*s;
        if (c == '\\') printf("\\\\");
        else if (c == '"') printf("\\\"");
        else if (c == '\n') printf("\\n");
        else if (c == '\r') printf("\\r");
        else if (c == '\t') printf("\\t");
        else if (c < 32) printf("\\u%04x", c);
        else putchar(c);
    }
}

int main(void) {
    time_t now = time(NULL);
    char current[64];
    strftime(current, sizeof(current), "%Y-%m-%d %H:%M:%S", localtime(&now));

    const char* ip_addr = env_or("REMOTE_ADDR", "unknown");

    printf("Content-Type: application/json\r\n\r\n");

    printf("{\n");
    printf("  \"greeting\": \"Hello, World!\",\n");
    printf("  \"from\": \"greeting from Xijian Xiang\",\n");
    printf("  \"Generated at\": \""); json_escape(current); printf("\",\n");
    printf("  \"IP Address\": \""); json_escape(ip_addr); printf("\"\n");
    printf("}\n");

    return 0;
}
