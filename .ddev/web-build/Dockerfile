# wkhtmltopdf is gone from Debian trixie's repos, so install the upstream
# wkhtmltox package. The .deb ships the binary at /usr/local/bin/wkhtmltopdf,
# matching config/snappy.php. Using the bookworm build; apt resolves its
# runtime dependencies against trixie.
RUN set -eux; \
    arch="$(dpkg --print-architecture)"; \
    url="https://github.com/wkhtmltopdf/packaging/releases/download/0.12.6.1-3/wkhtmltox_0.12.6.1-3.bookworm_${arch}.deb"; \
    (timeout 120 apt-get update || true); \
    curl -fsSL -o /tmp/wkhtmltox.deb "$url"; \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends /tmp/wkhtmltox.deb; \
    rm -f /tmp/wkhtmltox.deb; \
    wkhtmltopdf --version
