"""Build rag/index.json for kab_site using GigaChat embeddings."""
from __future__ import annotations

import base64
import json
import re
import sys
import uuid
from pathlib import Path

import requests

ROOT = Path(__file__).resolve().parent.parent
ENV = ROOT / ".env"
DOCS = ROOT / "rag" / "data" / "docs.txt"
INDEX = ROOT / "rag" / "index.json"


def load_env() -> dict[str, str]:
    env: dict[str, str] = {}
    for line in ENV.read_text(encoding="utf-8-sig").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        k, v = line.split("=", 1)
        env[k.strip()] = v.strip().strip('"').strip("'")
    return env


def chunk_text(text: str, chunk_size: int = 500, overlap: int = 100) -> list[str]:
    paragraphs = [p.strip() for p in re.split(r"\n\s*\n", text.strip()) if p.strip()]
    chunks: list[str] = []
    current = ""
    for paragraph in paragraphs:
        if current and len(current) + len(paragraph) + 2 <= chunk_size:
            current += "\n\n" + paragraph
            continue
        if current:
            chunks.append(current)
            tail = current[-overlap:] if overlap else ""
            current = (tail + "\n\n" if tail else "") + paragraph
            continue
        current = paragraph
    if current:
        chunks.append(current)
    return [c for c in chunks if len(c) >= 50]


def get_token(env: dict[str, str]) -> str:
    basic = env.get("GIGACHAT_BASIC_AUTH") or env.get("GIGACHAT_AUTH_KEY", "")
    if not basic:
        basic = base64.b64encode(
            f"{env['CLIENT_ID']}:{env['CLIENT_SECRET']}".encode()
        ).decode()
    r = requests.post(
        env.get("GIGACHAT_OAUTH_URL", "https://ngw.devices.sberbank.ru:9443/api/v2/oauth"),
        headers={
            "Authorization": f"Basic {basic}",
            "RqUID": env.get("GIGACHAT_RQUID") or env.get("CLIENT_ID") or str(uuid.uuid4()),
            "Content-Type": "application/x-www-form-urlencoded",
            "Accept": "application/json",
        },
        data={"scope": env.get("GIGACHAT_SCOPE", "GIGACHAT_API_PERS")},
        verify=False,
        timeout=60,
    )
    r.raise_for_status()
    return r.json()["access_token"]


def embed(token: str, env: dict[str, str], text: str) -> list[float]:
    url = env.get(
        "GIGACHAT_EMBEDDINGS_URL",
        "https://gigachat.devices.sberbank.ru/api/v1/embeddings",
    )
    r = requests.post(
        url,
        headers={
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
        json={
            "model": env.get("GIGACHAT_EMBEDDINGS_MODEL", "Embeddings"),
            "input": [text],
        },
        verify=False,
        timeout=60,
    )
    r.raise_for_status()
    return r.json()["data"][0]["embedding"]


def main() -> None:
    env = load_env()
    text = DOCS.read_text(encoding="utf-8")
    chunks = chunk_text(text)
    token = get_token(env)
    index = {"chunks": []}
    for i, chunk in enumerate(chunks):
        print(f"Embedding {i + 1}/{len(chunks)}")
        index["chunks"].append(
            {"id": f"doc_{i}", "text": chunk, "embedding": embed(token, env, chunk)}
        )
    INDEX.parent.mkdir(parents=True, exist_ok=True)
    INDEX.write_text(json.dumps(index, ensure_ascii=False), encoding="utf-8")
    print(f"Saved {INDEX} ({len(chunks)} chunks)")


if __name__ == "__main__":
    main()
