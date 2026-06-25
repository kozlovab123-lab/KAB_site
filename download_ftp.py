"""Download kab_site files from Reg.ru hosting via FTP."""
from __future__ import annotations

import ftplib
import json
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent
CONFIG = json.loads((ROOT / "hosting.json").read_text(encoding="utf-8"))
PASSWORD = os.environ.get("FTP_PASSWORD", "")


def connect() -> ftplib.FTP:
    if not PASSWORD:
        print("Set FTP_PASSWORD environment variable.", file=sys.stderr)
        sys.exit(1)
    ftp = ftplib.FTP(timeout=120)
    ftp.connect(CONFIG["ftp"]["host"], CONFIG["ftp"]["port"])
    ftp.login(CONFIG["ftp"]["user"], PASSWORD)
    ftp.set_pasv(True)
    return ftp


def download_tree(ftp: ftplib.FTP, remote: str, local: Path) -> int:
    count = 0
    ftp.cwd(remote)
    items: list[tuple[str, str]] = []
    ftp.retrlines("LIST", lambda line: items.append((line,)))

    for line, in items:
        parts = line.split(maxsplit=8)
        if len(parts) < 9:
            continue
        name = parts[8]
        if name in (".", ".."):
            continue
        is_dir = line.startswith("d")
        if is_dir:
            sub = local / name
            sub.mkdir(parents=True, exist_ok=True)
            cwd = ftp.pwd()
            ftp.cwd(name)
            count += download_tree(ftp, ".", sub)
            ftp.cwd(cwd)
        else:
            dest = local / name
            with dest.open("wb") as handle:
                ftp.retrbinary(f"RETR {name}", handle.write)
            print(f"  {dest.relative_to(ROOT)}")
            count += 1
    return count


def main() -> None:
    remote = CONFIG["remote_dir"]
    print(f"Downloading from {remote} ...")
    ftp = connect()
    try:
        n = download_tree(ftp, remote, ROOT)
        print(f"Done. Downloaded {n} files.")
    finally:
        ftp.quit()


if __name__ == "__main__":
    main()
