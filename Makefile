.PHONY: install test style

install: ; docker compose run --rm app composer install
test: ; docker compose run --rm app composer test
style: ; docker compose run --rm app composer style
