# OMI — Plan

AI мессенджер с биологической моделью памяти.

## Архитектура

```
User → Svelte UI → GraphQL (Lighthouse) → Memory Engine → DeepSeek API
                                               ↓
                                          PostgreSQL + pgvector
```

**Поток сообщения:**
```
User input
    ↓
1. Retriever.recall(input)          → достаёт релевантные memories
    ↓
2. PromptBuilder.build(memories)    → собирает system prompt + контекст
    ↓
3. LLM.complete(prompt)             → DeepSeek API
    ↓
4. EpisodicStore.save(...)          → пишет в episodes
    ↓
Response to user
```

---

## Phase 1: Фундамент памяти ✅

- [x] pgvector extension в Docker (`pgvector/pgvector:pg16`)
- [x] Миграции: `episodes`, `memories`, `sessions`
- [x] `EpisodicStore.php` — запись сообщений
- [x] Модели: `Episode`, `Memory`, `Session`

---

## Phase 2: Chat API ✅

- [x] Lighthouse (GraphQL) установка и настройка
- [x] `DeepSeekAdapter.php` (вместо Ollama — облачный API)
- [x] GraphQL schema: `sendMessage`, `episodes`, `sessions`, `memories`, `recall`
- [x] `Retriever.php` — подтягивание релевантного контекста (v1: ILIKE)
- [x] `SendMessage` resolver — полный цикл запрос → LLM → сохранение
- [x] `embed()` в DeepSeekAdapter — реализован (но не подключён к поиску)

---

## Phase 3: Chat UI ✅

- [x] Svelte 5 + TypeScript + TailwindCSS
- [x] Докеризация UI (multistage: node build → nginx)
- [x] `App.svelte` — главный компонент
- [x] `Sidebar.svelte` — список сессий
- [x] `MessageBubble.svelte` — рендеринг сообщений
- [x] `InputSocket.svelte` — поле ввода с авторесайзом
- [x] `api.ts` — GraphQL клиент
- [x] Nginx SPA-роутинг + прокси `/graphql`
- [x] CI/CD: GitHub Actions → GHCR → Watchtower
- [x] Telegram уведомления о деплое

---

## Phase 4: Consolidation + Decay ❌

> Текущий `Retriever` использует текстовый поиск (ILIKE). Эмбеддинги генерируются, но не используются для поиска.

- [ ] `EmbeddingService.php` — подключить `embed()` к сохранению episodes и memories
- [ ] `Retriever.php` — переключить с ILIKE на vector similarity (`<=>` pgvector)
- [ ] `Consolidator.php` — эпизоды → знания (LLM суммаризирует после сессии)
- [ ] `DecayScheduler.php` — ежедневное затухание `relevance_score`
  - Формула: `score = base × (0.95 ^ days_since_access) × log(access_count + 1)`
  - Если `score < threshold` и не `pinned` → пометить `faded = true`
- [ ] GraphQL: мутации `pinMemory`, `deleteMemory`, `consolidate` (схема уже есть, resolver'ы нужны)
- [ ] UI: панель памяти (что агент знает о тебе)
- [ ] UI: кнопка ручной консолидации

---

## Phase 5: Multi-LLM ❌

- [ ] `LLMAdapter` interface (уже есть) → `OllamaAdapter` (Ollama на хосте)
- [ ] `ClaudeAdapter` — Anthropic API
- [ ] Переключатель модели в UI (DeepSeek / Ollama / Claude)
- [ ] Выбор модели сохраняется в `Session.model_used`

---

## Долги

| Что | Где | Приоритет |
|-----|-----|-----------|
| Vector search не подключён | `Retriever.php` | Phase 4 |
| `embed()` не вызывается при сохранении | `EpisodicStore.php` | Phase 4 |
| `PinMemory`, `DeleteMemory`, `Consolidate` resolvers — заглушки | `app/GraphQL/Mutations/` | Phase 4 |
| `consolidate` мутация в schema есть, логики нет | `Consolidator.php` (отсутствует) | Phase 4 |
