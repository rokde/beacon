# Beacon Monorepo — Developer Shortcuts
# Usage: make <target>
#
# PHP tools run from the root (single vendor/).
# Frontend tools run from packages/dashboard/.

.PHONY: help install test test-core test-recorder test-dashboard \
        analyse format format-check refactor refactor-check \
        ci frontend-ci frontend-install frontend-build frontend-lint \
        frontend-format frontend-typecheck clean

# ── Meta ──────────────────────────────────────────────────────────────────────

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-25s\033[0m %s\n", $$1, $$2}'

# ── Dependencies ──────────────────────────────────────────────────────────────

install: ## Install all PHP and JS dependencies
	composer install
	$(MAKE) frontend-install

frontend-install: ## Install JS dependencies for dashboard
	cd packages/dashboard && npm ci

# ── PHP Tests ─────────────────────────────────────────────────────────────────

test: ## Run all tests with coverage (≥80%) and type coverage (100%)
	./vendor/bin/pest --coverage --min=80

test-types: ## Run type coverage check (100% required)
	./vendor/bin/pest --type-coverage --min=100

test-core: ## Run only core tests
	./vendor/bin/pest --testsuite=Core --coverage --min=80

test-recorder: ## Run only recorder tests
	./vendor/bin/pest --testsuite="Recorder Unit","Recorder Integration" --coverage --min=80

test-dashboard: ## Run only dashboard tests
	./vendor/bin/pest --testsuite="Dashboard Unit","Dashboard Feature" --coverage --min=80

test-watch: ## Run tests in watch mode (requires pest-watch)
	./vendor/bin/pest --watch

# ── PHP Quality ───────────────────────────────────────────────────────────────

analyse: ## Run PHPStan / Larastan analysis (level 10)
	./vendor/bin/phpstan analyse --memory-limit=512M

analyse-baseline: ## Generate a new PHPStan baseline
	./vendor/bin/phpstan analyse --generate-baseline --memory-limit=512M

format: ## Auto-fix formatting (Pint)
	./vendor/bin/pint

format-check: ## Check formatting without fixing (Pint --test)
	./vendor/bin/pint --test

refactor: ## Apply Rector refactorings
	./vendor/bin/rector process

refactor-check: ## Dry-run Rector (show changes without applying)
	./vendor/bin/rector process --dry-run

# ── Frontend Quality ──────────────────────────────────────────────────────────

frontend-build: ## Build dashboard assets (production)
	cd packages/dashboard && NODE_ENV=production npm run build

frontend-lint: ## Run oxlint on TypeScript files
	cd packages/dashboard && npm run lint

frontend-lint-fix: ## Run oxlint with --fix
	cd packages/dashboard && npm run lint:fix

frontend-format: ## Auto-format TS/CSS with Prettier
	cd packages/dashboard && npm run format

frontend-format-check: ## Check TS/CSS formatting without fixing
	cd packages/dashboard && npm run format:check

frontend-typecheck: ## Run tsc --noEmit
	cd packages/dashboard && npm run typecheck

# ── Full CI pipelines ─────────────────────────────────────────────────────────

ci: ## Run full PHP CI pipeline (format-check + rector + analyse + test + type-coverage)
	$(MAKE) format-check
	$(MAKE) refactor-check
	$(MAKE) analyse
	$(MAKE) test
	$(MAKE) test-types

frontend-ci: ## Run full frontend CI pipeline
	$(MAKE) frontend-format-check
	$(MAKE) frontend-lint
	$(MAKE) frontend-typecheck
	$(MAKE) frontend-build

all-ci: ## Run everything — PHP + Frontend
	$(MAKE) ci
	$(MAKE) frontend-ci

# ── Utilities ─────────────────────────────────────────────────────────────────

clean: ## Remove all build artefacts
	rm -rf build/
	rm -rf packages/core/build/
	rm -rf packages/recorder/build/
	rm -rf packages/dashboard/build/
	rm -rf packages/dashboard/dist/
	@echo "✓ Build artefacts removed."
