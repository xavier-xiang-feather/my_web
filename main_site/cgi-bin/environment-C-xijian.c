#include <stdio.h>
#include <stdlib.h>

extern char **environ;

static void html_escape(const char* s) {
    for (; *s; s++) {
        switch (*s) {
            case '&':  fputs("&amp;", stdout); break;
            case '<':  fputs("&lt;", stdout);  break;
            case '>':  fputs("&gt;", stdout);  break;
            case '"':  fputs("&quot;", stdout);break;
            case '\'': fputs("&#39;", stdout); break;
            default:   fputc(*s, stdout);
        }
    }
}

int main(void) {
    printf("Content-Type: text/html\r\n\r\n");

    printf("<html><head><title>Environment from C</title></head><body>");
    printf("<h1>Environment Variables</h1>");
    printf("<ul>");

    for (char **e = environ; *e; e++) {
        // key value
        const char* kv = *e;

        // split at first =
        const char* eq = kv;
        while (*eq && *eq != '=') eq++;

        printf("<li><strong>");
        for (const char* p = kv; p < eq; p++) {
            char tmp[2] = {*p, '\0'};
            html_escape(tmp);
        }
        printf("</strong>: ");

        if (*eq == '=') {
            html_escape(eq + 1);
        }
        printf("</li>");
    }

    printf("</ul>");
    printf("</body></html>");

    return 0;
}
