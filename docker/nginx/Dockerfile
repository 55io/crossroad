FROM nginx:stable-alpine

COPY nginx.conf /etc/nginx/nginx.conf
COPY vhost.conf /etc/nginx/conf.d/default.conf

WORKDIR /etc/nginx

CMD ["nginx"]