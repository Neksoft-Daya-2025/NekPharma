"""
Mirror project root into hostingercode/, excluding vendor, node_modules, .git, .cursor, .env.
Run: python scripts/sync_to_hostingercode.py [ROOT]
"""
from __future__ import annotations

import os
import shutil
import sys

SKIP_DIRS = {"vendor", "node_modules", ".git", "hostingercode", ".cursor"}


def prune_walk(dirpath: str, dirnames: list[str]) -> None:
    dirnames[:] = [d for d in dirnames if d not in SKIP_DIRS]
    base = os.path.basename(dirpath)
    if base == "public" and "user-uploads" in dirnames:
        # Large runtime uploads; recreate empty dir on server if needed
        dirnames.remove("user-uploads")


def main() -> int:
    if len(sys.argv) > 1:
        root = os.path.abspath(sys.argv[1])
    else:
        root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    dest = os.path.join(root, "hostingercode")

    if not os.path.isdir(root):
        print(f"ERROR: root not found: {root}", file=sys.stderr)
        return 1

    os.makedirs(dest, exist_ok=True)

    for dirpath, dirnames, filenames in os.walk(root):
        prune_walk(dirpath, dirnames)

        rel = os.path.relpath(dirpath, root)
        if rel == ".":
            rel = ""

        target_dir = os.path.join(dest, rel) if rel else dest
        os.makedirs(target_dir, exist_ok=True)

        for name in filenames:
            if name == ".env" and os.path.normpath(dirpath) == os.path.normpath(root):
                continue
            src_file = os.path.join(dirpath, name)
            dst_file = os.path.join(target_dir, name)
            if os.path.isfile(src_file):
                shutil.copy2(src_file, dst_file)

    print(f"Synced into: {dest}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
