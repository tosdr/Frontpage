FROM registry.jrbit.de/crispcms/core:nightly



ARG THEME_GIT_COMMIT=NF_HASH
ARG THEME_GIT_TAG=NF_HASH


ENV THEME_GIT_COMMIT "$THEME_GIT_COMMIT"
ENV THEME_GIT_TAG "$THEME_GIT_TAG"

COPY --chown=33:33 public /var/www/crisp/cms/themes/crisptheme

#RUN cd /var/www/crisp/cms/themes/crisptheme/includes/class && composer install
