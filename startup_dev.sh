export HOST="$(gp url 80 | sed -E 's_^https?://__')"
export REDIRECT_URI="$(gp url 80)"

docker compose -f docker-compose.dev.yml up