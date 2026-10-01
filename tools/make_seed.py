#!/usr/bin/env python3
"""Génère database/seed.sql : les 3 articles de départ du blog (INSERT IGNORE, réexécutable)."""
import importlib.util, os, re
R = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
spec = importlib.util.spec_from_file_location("bp", os.path.join(R, "tools", "build_pages.py")); bp = importlib.util.module_from_spec(spec); spec.loader.exec_module(bp)
q = lambda s: "'" + s.replace("\\", "\\\\").replace("'", "''") + "'"
rows = []
for f, cat, title, img, exc, paras in bp.A:
    body = "\n\n".join(re.sub(r"</?b>", "**", p) for p in paras)
    slug = f.replace("article-", "").replace(".html", "")
    rows.append(f"({q(slug)},{q(title)},{q(cat)},{q(exc)},{q(body)},{q(img)},1,NOW(),NOW())")
open(os.path.join(R, "database", "seed.sql"), "w", encoding="utf-8").write(
    "SET NAMES utf8mb4;\nINSERT IGNORE INTO blog_posts (slug,title,category,excerpt,content,image,published,created_at,updated_at) VALUES\n" + ",\n".join(rows) + ";\n")
print("database/seed.sql écrit,", len(rows), "articles")
