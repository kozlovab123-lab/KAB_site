# KAB_site

Версия: **1.1.0**

Сайт-визитка проекта KAB с всплывающим ИИ-консультантом на базе GigaChat и RAG (Retrieval-Augmented Generation).

Публичный адрес: [https://kab.solig.ru/](https://kab.solig.ru/)

## Возможности

- Лендинг с приветствием и карточками проектов по prompt engineering
- Раздел «Образцы сайтов» с пятью демо-макетами «КАБ‑студия»
- Возврат на главную с демонстрационных страниц
- Всплывающий чат «ИИ-консультант»
- RAG-пайплайн: семантический поиск по базе знаний + генерация ответа через GigaChat
- Кеширование ответов (SQLite)
- Деплой на shared-хостинг Reg.ru по FTP

Логика RAG адаптирована из репозитория [kozlovab123-lab/RAG](https://github.com/kozlovab123-lab/RAG).

## Структура проекта

```
KAB_site/
├── index.html              # Главная страница и UI чата
├── gigachat_proxy.php      # API-эндпоинт для чата (POST)
├── rag_lib.php             # RAG-пайплайн на PHP
├── gigachat_config.php     # Секреты GigaChat (не в git)
├── gigachat_config.example.php
├── hosting.json            # Параметры FTP-хостинга
├── deploy_ftp.py           # Загрузка файлов на хостинг
├── download_ftp.py         # Скачивание файлов с хостинга
├── rag/
│   ├── data/docs.txt       # База знаний
│   ├── index.json          # Индекс эмбеддингов (генерируется)
│   └── rag_cache.db        # Кеш ответов (создаётся на сервере)
└── scripts/
    └── build_rag_index.py  # Локальная сборка rag/index.json
```

## Требования

- Хостинг с **PHP 8+**, расширениями `curl`, `sqlite3`
- Python 3.11+ (для деплоя и сборки индекса)
- Учётная запись **GigaChat API**
- FTP-доступ к хостингу Reg.ru

## Быстрый старт (локально)

1. Клонируйте репозиторий:

```powershell
git clone https://github.com/kozlovab123-lab/KAB_site.git
cd KAB_site
```

2. Создайте `.env` на основе `.env.example` и заполните ключи GigaChat и FTP.

3. Создайте `gigachat_config.php` на основе `gigachat_config.example.php` (для продакшена на хостинге).

4. Соберите индекс базы знаний:

```powershell
python scripts/build_rag_index.py
```

5. Задеплойте на хостинг:

```powershell
$env:FTP_PASSWORD = "ваш_ftp_пароль"
python deploy_ftp.py
```

## Переменные окружения (.env)

| Переменная | Описание |
|------------|----------|
| `FTP_PASSWORD` | Пароль FTP для деплоя |
| `CLIENT_ID` | UUID клиента GigaChat |
| `CLIENT_SECRET` | Секрет GigaChat (или base64 Basic Auth) |
| `GIGACHAT_BASIC_AUTH` | Base64 для `Authorization: Basic` |
| `GIGACHAT_SCOPE` | Обычно `GIGACHAT_API_PERS` |
| `GIGACHAT_OAUTH_URL` | URL OAuth GigaChat |
| `GIGACHAT_CHAT_URL` | URL chat/completions |
| `GIGACHAT_EMBEDDINGS_URL` | URL embeddings |
| `GIGACHAT_CHAT_MODEL` | Модель чата (`GigaChat`) |
| `GIGACHAT_EMBEDDINGS_MODEL` | Модель эмбеддингов (`Embeddings`) |
| `GIGACHAT_VERIFY` | Проверка SSL (`false` для Reg.ru/Sber) |
| `TOP_K` | Число фрагментов контекста (по умолчанию 3) |

Файлы `.env` и `gigachat_config.php` **не коммитятся** в git.

## Хостинг

Параметры в `hosting.json`:

| Параметр | Значение |
|----------|----------|
| Домен | `kab.solig.ru` |
| Родительский домен | `solig.ru` |
| Провайдер | Reg.ru |
| Удалённая папка | `/www/kab.solig.ru` |

SSL: Let's Encrypt в панели ISPmanager Reg.ru.

## API чата

**Эндпоинт:** `POST /gigachat_proxy.php`

**Тело запроса (JSON):**

```json
{
  "message": "Что такое RAG?",
  "use_cache": true
}
```

**Успешный ответ:**

```json
{
  "ok": true,
  "answer": "…",
  "from_cache": false,
  "context_docs": [
    { "id": "doc_5", "text": "…", "distance": 0.17 }
  ],
  "model": "GigaChat"
}
```

## RAG-пайплайн

Поток обработки запроса (как в [RAG](https://github.com/kozlovab123-lab/RAG)):

1. Проверка кеша (`rag/rag_cache.db`)
2. Семантический поиск по `rag/index.json`
3. Формирование промпта с контекстом
4. Запрос к GigaChat
5. Сохранение ответа в кеш

### Обновление базы знаний

1. Отредактируйте `rag/data/docs.txt`
2. Пересоберите индекс: `python scripts/build_rag_index.py`
3. Задеплойте: `python deploy_ftp.py`

## Деплой

Скрипт `deploy_ftp.py` загружает файлы проекта в `/www/kab.solig.ru`, исключая:

- `.git`, `.env`, служебные скрипты, `hosting.json`, `_rag_src`

Скачивание с хостинга: `python download_ftp.py` (нужен `FTP_PASSWORD`).

## Версии

| Версия | Описание |
|--------|----------|
| **1.1.0** | Раздел «Образцы сайтов», навигация возврата на главную, обновлённый заголовок |
| **1.0.0** | Первый релиз: лендинг, popup-чат, RAG + GigaChat |

Тег: `v1.1.0`

## Лицензия и автор

Проект KAB — Козлов Алексей.
