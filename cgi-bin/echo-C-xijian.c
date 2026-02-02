#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

static const char* env_or(const char* key, const char* fallback) {
    const char* v = getenv(key);
    return (v && v[0]) ? v : fallback;
}

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

static char* read_body(void) {
    const char* len_s = getenv("CONTENT_LENGTH");
    long length = (len_s && *len_s) ? strtol(len_s, NULL, 10) : 0;
    if (length <= 0 || length > 5 * 1024 * 1024) {
        return strdup("");
    }
    char* body = (char*)malloc((size_t)length + 1);
    if (!body) return strdup("");
    size_t n = fread(body, 1, (size_t)length, stdin);
    body[n] = '\0';
    return body;
}

int main(void) {
    const char* protocol = env_or("SERVER_PROTOCOL", "HTTP/1.1");
    const char* method = env_or("REQUEST_METHOD", "UNKNOWN");
    const char* query_string = env_or("QUERY_STRING", "");
    const char* content_type = env_or("CONTENT_TYPE", "");
    const char* ip = env_or("REMOTE_ADDR", "unknown");
    const char* user_agent = env_or("HTTP_USER_AGENT", "unknown");

    time_t now = time(NULL);
    char current[64];
    strftime(current, sizeof(current), "%Y-%m-%d %H:%M:%S", localtime(&now));

    // read and parse data
    char* body = NULL;
    const char* body_mode = NULL;

    // for get: parsed is query_string
    // for others: parsed is body
    const char* parsed = NULL;

    if (strcmp(method, "GET") == 0) {
        body = strdup("");
        parsed = query_string;
        body_mode = "n/a";
    } else if (strcmp(method, "POST") == 0 || strcmp(method, "PUT") == 0 || strcmp(method, "DELETE") == 0) {
        body = read_body();
        parsed = body;

        if (strstr(content_type, "application/json")) body_mode = "json";
        else if (strstr(content_type, "application/x-www-form-urlencoded")) body_mode = "form";
        else if (body[0] == '\0') body_mode = "empty";
        else body_mode = "other";
    } else {
        body = strdup("");
        parsed = "";
        body_mode = "unsupported";
    }

    printf("Content-Type: text/html\r\n\r\n");

    printf("<!doctype html>");
    printf("<html><head><meta charset='utf-8'>");
    printf("<title>General Request Echo (C)</title>");
    printf("</head><body>");

    printf("<h1 style='text-align:center;'>General Request Echo</h1>");
    printf("<hr>");

    printf("<p><strong>HTTP Protocol:</strong> "); html_escape(protocol); printf("</p>");
    printf("<p><strong>HTTP Method:</strong> ");   html_escape(method);   printf("</p>");

    printf("<p><strong>Query String:</strong></p>");
    printf("<pre>");
    html_escape(query_string);
    printf("</pre>");

    printf("<p><strong>Message Body:</strong></p>");
    printf("<pre>");
    html_escape(body);
    printf("</pre>");

    printf("<p><strong>Parsed Data:</strong> (mode: ");
    html_escape(body_mode);
    printf(")</p>");
    printf("<pre>");
    html_escape(parsed);
    printf("</pre>");

    printf("<hr>");
    printf("<p><strong>Time:</strong> "); html_escape(current); printf("</p>");
    printf("<p><strong>IP Address:</strong> "); html_escape(ip); printf("</p>");
    printf("<p><strong>User-Agent:</strong> "); html_escape(user_agent); printf("</p>");

    printf("</body></html>");

    free(body);
    return 0;
}
