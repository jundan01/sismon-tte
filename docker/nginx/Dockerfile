FROM nginx:1.27-alpine

# Hapus konfigurasi default dan entrypoint scripts yang memodifikasi filesystem
RUN rm -rf /etc/nginx/conf.d/* /docker-entrypoint.d/*

# Salin sertifikat SSL localhost
COPY certs/ /etc/nginx/certs/

# Salin konfigurasi hardened
COPY nginx.conf /etc/nginx/nginx.conf

# Konfigurasi perizinan direktori cache, logs, & certs
RUN chown -R nginx:nginx /var/cache/nginx /var/log/nginx /etc/nginx \
    && chmod -R 755 /var/cache/nginx /var/log/nginx \
    && chmod 600 /etc/nginx/certs/*.key 2>/dev/null || true

EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]