"""Deploy kab_site files to Reg.ru hosting via FTP."""
from __future__ import annotations

import ftplib
import json
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
CONFIG = json.loads((ROOT / "hosting.json").read_text(encoding="utf-8"))
PASSWORD = os.environ.get("FTP_PASSWORD", "")

SKIP = {
    ".git",
    ".env",
    "__pycache__",
    ".vscode",
    "hosting.json",
    "deploy_ftp.py",
    "download_ftp.py",
    ".gitignore",
    ".code-workspace",
    "_rag_src",
}


def connect() -> ftplib.FTP:
    if not PASSWORD:
        print("Set FTP_PASSWORD environment variable.", file=sys.stderr)
        sys.exit(1)
    ftp = ftplib.FTP(timeout=120)
    ftp.connect(CONFIG["ftp"]["host"], CONFIG["ftp"]["port"])
    ftp.login(CONFIG["ftp"]["user"], PASSWORD)
    ftp.set_pasv(True)
    return ftp


def ensure_remote_dir(ftp: ftplib.FTP, path: str) -> None:
    parts = [p for p in path.strip("/").split("/") if p]
    ftp.cwd("/")
    for part in parts:
        try:
            ftp.cwd(part)
        except ftplib.error_perm:
            ftp.mkd(part)
            ftp.cwd(part)


def upload_tree(ftp: ftplib.FTP, local: Path, remote_base: str) -> int:
    count = 0
    for item in sorted(local.rglob("*")):
        if not item.is_file():
            continue
        if any(part in SKIP for part in item.parts):
            continue
        if item.suffix == ".code-workspace":
            continue
        rel = item.relative_to(local).as_posix()
        remote_dir = f"{remote_base}/{item.parent.relative_to(local).as_posix()}".replace("/.", "")
        if remote_dir.endswith("/."):
            remote_dir = remote_dir[:-2]
        ensure_remote_dir(ftp, remote_dir)
        with item.open("rb") as handle:
            ftp.storbinary(f"STOR {item.name}", handle)
        print(f"  {rel}")
        count += 1
    return count


def main() -> None:
    remote = CONFIG["remote_dir"]
    print(f"Deploying to {remote} ...")
    ftp = connect()
    try:
        ensure_remote_dir(ftp, remote)
        n = upload_tree(ftp, ROOT, remote)
        print(f"Done. Uploaded {n} files.")
    finally:
        ftp.quit()


if __name__ == "__main__":
    main()
