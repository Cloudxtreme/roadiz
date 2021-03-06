#
# This is an example VCL file for Varnish.
#
# It does not do anything by default, delegating control to the
# builtin VCL. The builtin VCL is called when there is no explicit
# return statement.
#
# See the VCL chapters in the Users Guide at https://www.varnish-cache.org/docs/
# and https://www.varnish-cache.org/trac/wiki/VCLExamples for more examples.

# Marker to tell the VCL compiler that this VCL has been adapted to the
# new 4.0 format.
vcl 4.0;

# Default backend definition. Set this to point to your content server.
backend default {
    .host = "127.0.0.1";
    .port = "8080";
}

acl local {
    "localhost";
}

sub vcl_recv {
    # Happens before we check if we have this in cache already.
    #
    # Typically you clean up the request here, removing cookies you don't need,
    # rewriting the request, etc.
    if (req.url ~ "^/(rz-admin|preview\.php|clear_cache\.php|install\.php|dev\.php)") {
        return(pass);
    } else {
        # Remove the cookie header to enable caching
        unset req.http.cookie;
    }

    #
    # Purge one object by its URL
    #
    if (req.method == "PURGE") {
        if (client.ip ~ local) {
            return(purge);
        } else {
            return(synth(403, "Access denied."));
        }
    }

    #
    # Purge entire domain objects
    #
    if (req.method == "BAN") {
        # Same ACL check as above:
        if (client.ip ~ local) {
            ban("req.http.host ~ " + req.http.host);
            return(synth(200, "Ban domain"));
        } else {
            return(synth(403, "Access denied."));
        }
    }
}

sub vcl_backend_response {
    # Happens after we have read the response headers from the backend.
    #
    # Here you clean the response headers, removing silly Set-Cookie headers
    # and other mistakes your backend does.

    # Clean backend responses only on public pages.
    if (bereq.url !~ "^/(rz-admin|preview\.php|clear_cache\.php|install\.php|dev\.php)") {
        # Remove the cookie header to enable caching
        unset beresp.http.Set-Cookie;
    }
}

sub vcl_deliver {
    # Happens when we have all the pieces we need, and are about to send the
    # response to the client.
    #
    # You can do accounting or modifying the final object here.

    if (obj.hits > 0) {
        set resp.http.X-Cache = "HIT";
    } else {
        set resp.http.X-Cache = "MISS";
    }
}

