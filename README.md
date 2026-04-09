# OMI — AI Messenger

Суверенный мессенджер с AI. Svelte + Laravel GraphQL + DeepSeek + PostgreSQL + pgvector.

## Быстрый старт

```bash
# 1. Клонируй
git clone git@github.com:digital-atelier-zephyr/omi-try1.git
cd omi-try1

# 2. Настрой ключ
cp .env.example .env
# Открой .env и вставь свой DEEPSEEK_API_KEY

# 3. Запусти
docker compose up -d

# 4. Открой
# http://localhost — чат с AI
```

## Архитектура

```
┌─────────────┐    ┌───────────┐    ┌──────────────┐    ┌──────────┐
│  UI (Svelte) │───▶│  Nginx    │───▶│  Laravel API  │───▶│ DeepSeek │
│  port 80     │    │  port 9080│    │  PHP-FPM 9000 │    │  API     │
└─────────────┘    └───────────┘    └──────┬───────┘    └──────────┘
                                           │
                                    ┌──────▼───────┐
                                    │  PostgreSQL   │
                                    │  + pgvector   │
                                    └──────────────┘
```

## Стек

| Слой | Технология |
|------|-----------|
| **UI** | Svelte 5 + TypeScript + TailwindCSS |
| **API** | Laravel 13 + Lighthouse (GraphQL) |
| **AI** | DeepSeek API (с памятью через pgvector) |
| **DB** | PostgreSQL 16 + pgvector |
| **CI/CD** | GitHub Actions → GHCR → Watchtower |
| **Мониторинг** | Dozzle (логи), CloudBeaver (БД) |

## Сервисы

| Сервис | URL |
|--------|-----|
| Чат | http://localhost |
| GraphiQL | http://localhost:9080/graphiql |
| Логи (Dozzle) | http://localhost:9999 |
| БД (CloudBeaver) | http://localhost:8081 |

## CI/CD Pipeline

```
git push → Lint → Tests → Build API + UI → Push to GHCR → Telegram Notify
                                                  ↓
                                            Watchtower → Auto-deploy
```

## Инфраструктура

| Компонент | Статус |
|-----------|--------|
| Laravel 13 + PostgreSQL + pgvector | ✅ |
| Svelte UI (докеризирован) | ✅ |
| GraphQL API (Lighthouse) | ✅ |
| AI с памятью (эпизодическая + семантическая) | ✅ |
| CI/CD (lint → test → build → push → notify) | ✅ |
| Docker registry (ghcr.io) | ✅ |
| Auto-deploy (Watchtower) | ✅ |
| Health checks | ✅ |
| Auto-migration на старте | ✅ |
| Telegram уведомления | ✅ |
