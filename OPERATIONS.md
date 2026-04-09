# OMI — Справочник команд

## 🚀 Запуск проекта

### Прод (основной режим)
```bash
# Поднять всё
docker compose -f docker-compose.prod.yml up -d

# Остановить всё
docker compose -f docker-compose.prod.yml down

# Остановить и УДАЛИТЬ данные (базу, кэш)
docker compose -f docker-compose.prod.yml down -v
```

### Dev (для локальной разработки UI)
```bash
# Поднять бэкенд
docker compose up -d

# Запустить UI с hot-reload
cd ui && yarn dev
```

---

## 📦 Деплой

### Стандартный деплой (через CI/CD)
```bash
git add .
git commit -m "описание изменений"
git push
# GitHub Actions: lint → test → build → push в GHCR
# Watchtower: автоматически стянет новый образ через ~60 сек
```

### Ручной деплой (без CI/CD)
```bash
# Пересобрать конкретный сервис локально
docker compose -f docker-compose.prod.yml build api
docker compose -f docker-compose.prod.yml up -d api

# Или стянуть последний образ из registry
docker compose -f docker-compose.prod.yml pull api
docker compose -f docker-compose.prod.yml up -d api
```

---

## 🔍 Диагностика

### Логи
```bash
# Логи конкретного сервиса (последние 50 строк)
docker compose -f docker-compose.prod.yml logs api --tail 50

# Логи в реальном времени (follow)
docker compose -f docker-compose.prod.yml logs api -f

# Найти ошибки в логах API
docker compose -f docker-compose.prod.yml logs api 2>&1 | grep -E "SQLSTATE|Exception|Error:" | tail -5

# Логи Watchtower (проверить обновления)
docker compose -f docker-compose.prod.yml logs watchtower --tail 20
```

### Статус контейнеров
```bash
# Все контейнеры и их состояние
docker compose -f docker-compose.prod.yml ps

# Проверить какой образ использует контейнер
docker inspect $(docker compose -f docker-compose.prod.yml ps -q api) --format '{{.Config.Image}}'

# Проверить маунты (volumes)
docker inspect $(docker compose -f docker-compose.prod.yml ps -q api) --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{println}}{{end}}'
```

### Войти внутрь контейнера
```bash
# Шелл в API
docker compose -f docker-compose.prod.yml exec api sh

# Шелл в БД
docker compose -f docker-compose.prod.yml exec db psql -U omi -d omi
```

---

## 🛠 Laravel (через контейнер)

```bash
# Запустить миграции
docker compose -f docker-compose.prod.yml exec api php artisan migrate --force

# Откатить миграции
docker compose -f docker-compose.prod.yml exec api php artisan migrate:rollback

# Очистить кэш (если GraphQL глючит)
docker compose -f docker-compose.prod.yml exec api php artisan cache:clear

# Очистить кэш Lighthouse (схема GraphQL)
docker compose -f docker-compose.prod.yml exec api php artisan lighthouse:clear-cache

# Очистить кэш конфига
docker compose -f docker-compose.prod.yml exec api php artisan config:clear

# ЯДЕРНЫЙ СБРОС (все кэши разом)
docker compose -f docker-compose.prod.yml exec api sh -c "php artisan cache:clear && php artisan config:clear && php artisan lighthouse:clear-cache"
```

---

## 🔄 Рестарт сервисов

```bash
# Рестарт одного сервиса
docker compose -f docker-compose.prod.yml restart api
docker compose -f docker-compose.prod.yml restart nginx
docker compose -f docker-compose.prod.yml restart ui

# Рестарт всего
docker compose -f docker-compose.prod.yml restart
```

---

## 🧹 Очистка

```bash
# Удалить неиспользуемые образы (освободить диск)
docker image prune -f

# Ядерная очистка (ВСЕ неиспользуемые образы, volumes, networks)
docker system prune -a -f

# Проверить сколько диска жрёт Docker
docker system df
```

---

## 🌐 URL сервисов

| Сервис | URL | Описание |
|--------|-----|----------|
| **Чат UI** | http://localhost:3493 | Основной интерфейс |
| **GraphiQL** | http://localhost:9080/graphiql | Песочница GraphQL |
| **API** | http://localhost:9080/graphql | Эндпоинт GraphQL |
| **Dozzle** | http://localhost:9999 | Логи контейнеров в браузере |
| **CloudBeaver** | http://localhost:8081 | Управление БД в браузере |

---

## 📁 Структура проекта

```
omi-try1/
├── api/                        # Laravel бэкенд
│   ├── Dockerfile              # Сборка PHP-FPM образа
│   ├── docker-entrypoint.sh    # Скрипт старта (миграции, кэш)
│   ├── graphql/schema.graphql  # GraphQL схема
│   ├── app/Models/             # Eloquent модели
│   ├── app/Services/           # Бизнес-логика (LLM, память)
│   └── database/migrations/    # Миграции БД
├── ui/                         # Svelte фронтенд
│   ├── Dockerfile              # Мультистейдж: node build → nginx
│   ├── nginx.conf              # SPA-роутинг + прокси /graphql
│   └── src/
│       ├── App.svelte          # Главный компонент
│       └── lib/
│           ├── api.ts          # GraphQL клиент
│           └── components/     # UI компоненты
├── docker/nginx/default.conf   # Nginx конфиг для API
├── docker-compose.yml          # Dev: локальная сборка
├── docker-compose.prod.yml     # Prod: образы из GHCR + Watchtower
├── .github/workflows/ci.yml    # CI/CD пайплайн
├── .env.example                # Шаблон переменных
└── .env.prod                   # Секреты (НЕ в git!)
```

---

## ⚠️ Частые проблемы

### 502 Bad Gateway
API контейнер упал. Проверь логи:
```bash
docker compose -f docker-compose.prod.yml logs api --tail 30
```
Обычно: ошибка миграции или PHP fatal error.

### __PHP_Incomplete_Class (кэш Lighthouse)
```bash
docker compose -f docker-compose.prod.yml exec api php artisan cache:clear
```

### Watchtower не обновляет
1. Проверь что контейнер запущен из **prod** compose (с `image:`, не `build:`)
2. Проверь лейбл: `com.centurylinklabs.watchtower.enable=true`
3. Проверь логи: `docker compose -f docker-compose.prod.yml logs watchtower --tail 10`

### Порт занят
```bash
sudo lsof -i :ПОРТ | head -5
```
