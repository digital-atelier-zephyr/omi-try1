---
description: Основной workflow разработки omi-try1 (prod-first)
---
// turbo-all

# Prod-First Workflow

Весь код проходит через CI/CD. Никаких локальных хаков.

## Шаги

1. Пишем/правим код
2. Проверяем lint локально (опционально):
   ```bash
   docker compose exec api vendor/bin/pint --test
   ```
3. Коммит + пуш:
   ```bash
   cd ~/dev/omi-try1
   git add . && git commit -m "описание" && git push
   ```
4. Ждём CI green на GitHub Actions
5. Стягиваем и поднимаем:
   ```bash
   cd ~/dev/omi-try1
   docker compose -f docker-compose.prod.yml --env-file .env down -v
   docker compose -f docker-compose.prod.yml --env-file .env pull
   docker compose -f docker-compose.prod.yml --env-file .env up -d
   ```
6. Проверяем в Dozzle: http://localhost:9999
7. Проверяем API: http://omi.local

## Установка composer пакетов

Через одноразовый контейнер:
```bash
cd ~/dev/omi-try1
docker run --rm -v $(pwd)/api:/app -w /app composer require ПАКЕТ
```
Потом push → CI → pull → up.
