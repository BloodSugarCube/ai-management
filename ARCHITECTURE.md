# Архитектура AI-management

Ниже — обзор потоков данных и компонентов. Диаграммы в формате [Mermaid](https://mermaid.js.org/) (рендерятся на GitHub/GitLab и во многих IDE).

## Общая схема

```mermaid
flowchart LR
  subgraph Admin["Админка (Blade + session)"]
    UI_E["Сотрудники"]
    UI_T["Задачи"]
  end

  subgraph App["Laravel приложение"]
    AC["Контроллеры Admin\\*"]
    S_REC["TaskAssigneeRecommendationService"]
    S_ASN["RedmineIssueAssignmentService"]
    CMD_E["redmine:sync-employees"]
    CMD_I["redmine:sync-tasks"]
  end

  subgraph DB["MySQL"]
    T_E[(employees)]
    T_I[(redmine_issues)]
  end

  subgraph RC["app/Components/RocketChat"]
    RM["RedmineClient (REST)"]
  end

  subgraph GA["app/Components/GenApi"]
    GC["GenApiClient (POST + poll)"]
  end

  UI_E --> AC
  UI_T --> AC
  AC --> T_E
  AC --> T_I
  AC --> S_REC
  AC --> S_ASN
  S_REC --> GC
  S_ASN --> RM
  CMD_E --> RM
  CMD_E --> T_E
  CMD_I --> RM
  CMD_I --> T_I
  S_REC --> RM
```

## Подбор исполнителя (UI + GenAPI + Redmine)

```mermaid
sequenceDiagram
  participant U as Пользователь
  participant B as Браузер
  participant L as Laravel
  participant G as GenAPI
  participant R as Redmine

  U->>B: Нажать «Подобрать исполнителя»
  B->>L: POST /tasks/{id}/recommendations (+ CSRF)
  L->>L: TaskAssigneeRecommendationService

  L->>G: Шаг 1: задача + сотрудники + нагрузка
  G-->>L: JSON с кандидатами (до 5 логинов)

  loop Для каждого кандидата
    L->>R: GET issues (closed, titles)
    R-->>L: до 100 названий
  end

  L->>G: Шаг 2: + последние задачи
  G-->>L: JSON с процентами и причинами

  L-->>B: { recommendations: [...] }

  alt Автоназначение включено
    B->>L: POST /tasks/{id}/assign {login: top1}
    L->>R: PUT issue assigned_to_id
    R-->>L: OK
    L->>R: GET issue (обновление полей)
    L->>L: UPDATE redmine_issues
    L-->>B: OK + reload
  else Автоназначение выключено
    B->>B: Модальное окно + выбор
    U->>B: Сохранить
    B->>L: POST /tasks/{id}/assign {login}
    L->>R: PUT issue assigned_to_id
    L-->>B: OK + reload
  end
```

## Синхронизация

```mermaid
flowchart TD
  A["php artisan redmine:sync-employees"] --> B["RedmineClient.fetchAllUsers"]
  B --> C["upsert employees"]

  D["php artisan redmine:sync-tasks"] --> E["RedmineClient.iterateIssues(status filter)"]
  E --> F["normalizeIssueFromApi"]
  F --> G["upsert redmine_issues"]
```

## Конфигурация

| Область        | Файл `config/*.php` | Основные переменные `.env`        |
|----------------|---------------------|-----------------------------------|
| Админ-вход     | `admin.php`         | `ADMIN_USERNAME`, `ADMIN_PASSWORD_HASH`, `ADMIN_PASSWORD` |
| Redmine        | `redmine.php`       | `REDMINE_BASE_URL`, `REDMINE_API_KEY`, `REDMINE_ISSUE_STATUS_FILTER`, `REDMINE_IN_PROGRESS_STATUS_NAMES` |
| GenAPI         | `genapi.php`        | `GENAPI_BASE_URL`, `GENAPI_API_KEY`, `GENAPI_STEP1_NETWORK_ID`, `GENAPI_STEP2_NETWORK_ID`, `GENAPI_EXTRA_JSON_BODY` |

## Примечание по каталогу `RocketChat`

По техническому заданию весь код интеграции с **Redmine** размещён в `app/Components/RocketChat` (имя каталога зафиксировано в ТЗ). Функционально это клиент Redmine REST API (`RedmineClient`).
