# OMI — AI Messenger

Мобильный мессенджер с нейросетью. Expo + Laravel GraphQL + PostgreSQL + Ollama.

## Инфраструктура

| Компонент | Статус |
|-----------|--------|
| Laravel 13 + Postgres | ✅ |
| CI/CD (lint → test → build → push → notify) | ✅ |
| Docker registry (ghcr.io) | ✅ |
| Production deploy (pull from registry) | ✅ |
| Dozzle (логи в браузере) | ✅ |
| CloudBeaver (БД в браузере) | ✅ |
| Health checks | ✅ |
| Auto-migration на старте | ✅ |
| Telegram уведомления | ✅ |
| GitHub организация + глобальные секреты | ✅ |

## Быстрый старт

```bash
# 1. Клонируем
git clone git@github.com:digital-atelier-zephyr/omi-try1.git
cd omi-try1

# 2. Настраиваем env
cp .env.prod.example .env

# 3. Домен
echo "127.0.0.1 omi.local" | sudo tee -a /etc/hosts

# 4. Логинимся в registry
echo "TOKEN" | docker login ghcr.io -u USER --password-stdin

# 5. Поднимаем
docker compose -f docker-compose.prod.yml --env-file .env up -d
```

## Сервисы

| Сервис | URL |
|--------|-----|
| API | http://omi.local |
| Dozzle (логи) | http://localhost:9999 |
| CloudBeaver (БД) | http://localhost:8081 |

## CI/CD Pipeline

```
git push → Lint (Pint) → Tests (PHPUnit + Postgres) → Docker Build & Push → Telegram Notify
```

## Стек

- **Mobile**: Expo Go (React Native)
- **API**: Laravel 13 + Lighthouse (GraphQL)
- **DB**: PostgreSQL 16
- **LLM**: Ollama (local)
- **CI/CD**: GitHub Actions
- **Registry**: GitHub Container Registry (ghcr.io)
- **Monitoring**: Dozzle
- **DB Client**: CloudBeaver
