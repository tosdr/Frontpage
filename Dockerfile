FROM ruby:bookworm as sassbuild

COPY . /tmp/crisp

RUN apt-get update && \
    apt-get install -y ca-certificates curl gnupg sudo && \
    mkdir -p /etc/apt/keyrings && \
    curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | sudo gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg && \
    echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_20.x nodistro main" | sudo tee /etc/apt/sources.list.d/nodesource.list && \
    apt-get update && \
    apt-get install -y nodejs && \
    cd /tmp/crisp && \
    npm install -g sass && \
    npm install && \
    npm run compile 

FROM registry.jrbit.de/crispcms/core:17



ARG THEME_GIT_COMMIT=NF_HASH
ARG THEME_GIT_TAG=NF_HASH


ENV THEME_GIT_COMMIT "$THEME_GIT_COMMIT"
ENV THEME_GIT_TAG "$THEME_GIT_TAG"

COPY --chown=33:33 public /var/www/crisp/cms/themes/crisptheme

COPY --chown=33:33 --from=sassbuild /tmp/crisp/public/assets/css/dist /var/www/crisp/cms/themes/crisptheme/assets/css/dist

RUN cd /var/www/crisp/cms/themes/crisptheme/includes && composer install
