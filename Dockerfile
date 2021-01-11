# NAME:		jbackpcat/tosdr-crisp
# VERSION: DEVELOPMENT
FROM debian:buster-slim

# Accept Environment Variables, set defaults

ENV MYSQL_HOSTNAME localhost
ENV MYSQL_USERNAME tosdr
ENV MYSQL_PASSWORD tosdr
ENV MYSQL_DATABASE tosdr
ENV REDIS_HOST localhost
ENV REDIS_PORT 6379
ENV REDIS_AUTH tosdr
ENV DEBIAN_FRONTEND noninteractive
ENV POSTGRES_URI ""
ENV CDN_URL ""

ARG CLONE_URL "https://github.com/tosdr/CrispCMS.git"
ENV CLONE_URL ${CLONE_URL}

# Expose Ports
EXPOSE 80
EXPOSE 3306
EXPOSE 6379

# Add our Scripts

ADD start.sh start.sh
ADD bootstrap.sh bootstrap.sh
RUN chmod +x /start.sh
RUN chmod +x /bootstrap.sh

# Bootstrap the image
RUN /bootstrap.sh


# Add our custom apache config
ADD configs/http.conf /etc/apache2/sites-enabled/000-default.conf


CMD /start.sh