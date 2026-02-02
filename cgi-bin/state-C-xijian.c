#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <unistd.h>

#define BUF 1024

/* -------- helpers -------- */

void get_session_id(char *sid, size_t n) {
    char *cookie = getenv("HTTP_COOKIE");
    if (!cookie) { sid[0] = '\0'; return; }

    char *p = strstr(cookie, "session_id=");
    if (!p) { sid[0] = '\0'; return; }

    p += strlen("session_id=");
    size_t i = 0;
    while (*p && *p != ';' && i < n - 1) {
        sid[i++] = *p++;
    }
    sid[i] = '\0';
}

void gen_session_id(char *sid, size_t n) {
    srand(time(NULL));
    snprintf(sid, n, "%ld%u", time(NULL), rand());
}

char *get_query_param(const char *qs, const char *key) {
    static char val[BUF];
    val[0] = '\0';

    if (!qs) return val;

    char pattern[128];
    snprintf(pattern, sizeof(pattern), "%s=", key);

    char *p = strstr(qs, pattern);
    if (!p) return val;

    p += strlen(pattern);
    size_t i = 0;
    while (*p && *p != '&' && i < BUF - 1) {
        val[i++] = *p++;
    }
    val[i] = '\0';
    return val;
}

/* -------- main -------- */

int main(void) {
    char session_id[128];
    get_session_id(session_id, sizeof(session_id));

    int new_session = 0;
    if (session_id[0] == '\0') {
        gen_session_id(session_id, sizeof(session_id));
        new_session = 1;
    }

    printf("Content-Type: text/html\r\n");
    if (new_session) {
        printf("Set-Cookie: session_id=%s; Path=/\r\n", session_id);
    }
    printf("\r\n");

    char *qs = getenv("QUERY_STRING");
    char *action = get_query_param(qs, "action");

    char path[256];
    snprintf(path, sizeof(path), "/tmp/state_c_%s.txt", session_id);

    /* -------- SET -------- */
    if (strcmp(action, "set") == 0) {
        char *method = getenv("REQUEST_METHOD");

        if (method && strcmp(method, "POST") == 0) {
            int len = atoi(getenv("CONTENT_LENGTH") ?: "0");
            if (len > 0 && len < BUF) {
                char body[BUF];
                fread(body, 1, len, stdin);
                body[len] = '\0';

                char *p = strstr(body, "name=");
                if (p) {
                    FILE *f = fopen(path, "w");
                    if (f) {
                        fprintf(f, "%s", p + 5);
                        fclose(f);
                    }
                }
            }
        }

        printf(
            "<h1>Set State (C)</h1>"
            "<form method='POST' action='?action=set'>"
            "Name: <input type='text' name='name'>"
            "<button type='submit'>Save</button>"
            "</form>"
            "<p><a href='?action=view'>View State</a></p>"
            "<p><a href='?action=clear'>Clear State</a></p>"
            "<p><strong>Session ID:</strong> %s</p>",
            session_id
        );
    }

    /* -------- VIEW -------- */
    else if (strcmp(action, "view") == 0) {
        char value[BUF] = "(no state saved)";
        FILE *f = fopen(path, "r");
        if (f) {
            fgets(value, sizeof(value), f);
            fclose(f);
        }

        printf(
            "<h1>View State (C)</h1>"
            "<pre>%s</pre>"
            "<p><a href='?action=set'>Set State</a></p>"
            "<p><a href='?action=clear'>Clear State</a></p>",
            value
        );
    }

    /* -------- CLEAR -------- */
    else if (strcmp(action, "clear") == 0) {
        unlink(path);

        printf(
            "<h1>State Cleared (C)</h1>"
            "<p>Server-side state removed.</p>"
            "<p><a href='?action=set'>Set State</a></p>"
            "<p><a href='?action=view'>View State</a></p>"
        );
    }

    /* -------- DEFAULT -------- */
    else {
        printf(
            "<h1>C State Demo</h1>"
            "<ul>"
            "<li><a href='?action=set'>Set State</a></li>"
            "<li><a href='?action=view'>View State</a></li>"
            "<li><a href='?action=clear'>Clear State</a></li>"
            "</ul>"
        );
    }

    return 0;
}
