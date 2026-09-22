COMPOSE := docker compose
HTTP_TESTS := \
	bin/test-http.sh \
	bin/test-users-http.sh \
	bin/test-products-http.sh \
	bin/test-campaigns-http.sh \
	bin/test-sales-http.sh \
	bin/test-sales-history-http.sh \
	bin/test-cancellations-http.sh \
	bin/test-wallet-http.sh \
	bin/test-concurrency-http.sh

.PHONY: up up-d build down logs test-unit test-http test

up:
	$(COMPOSE) up --build

up-d:
	$(COMPOSE) up --build -d

build:
	$(COMPOSE) build

down:
	$(COMPOSE) down

logs:
	$(COMPOSE) logs -f backend

test-unit:
	$(COMPOSE) run --rm --no-deps backend vendor/bin/phpunit

test-http:
	@for script in $(HTTP_TESTS); do \
		echo "==> $$script"; \
		$(COMPOSE) exec -T backend sh -c "BASE_URL=http://127.0.0.1:8080 sh $$script" || exit 1; \
	done

test: test-unit test-http
