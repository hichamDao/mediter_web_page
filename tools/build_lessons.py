#!/usr/bin/env python3
"""Génère lessons-data.js à partir des dossiers « Module N … » et « Vos 3 séances de coaching ».
Usage : python3 tools/build_lessons.py   (à relancer après chaque modification d'un fichier de leçon)"""
import glob, html, json, os, re
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
KINDS = [("Enseignement", "enseignement"), ("Pratique", "pratique"), ("Méditation", "meditation"), ("Séance", "coaching")]

def kind_of(name):
    for k, v in KINDS:
        if re.match(r"^\d+_" + k, name):
            return v
    return "enseignement"

def module_of(n):  # numéro de fichier -> module (le 12 « poser le poids » appartient au module 4)
    return {1:1,2:1,3:1,4:2,5:2,6:2,7:3,8:3,9:3,10:4,11:4,12:4,13:5,14:5,15:5,16:6,17:6,18:6}.get(n)

def inline(t):
    t = html.escape(t.strip(), quote=False)
    return re.sub(r"\[(Pause[^\]]*)\]", r'<span class="pause">⏸ \1</span>', t)

def parse(path):
    lines = open(path, encoding="utf-8").read().split("\n")
    blocks, gap = [], 0
    for l in lines:
        if not l.strip(): gap += 1; continue
        t = re.sub(r"</?(html|body)>", "", l).strip()
        if t and t != "/": blocks.append((gap, t))
        gap = 0
    title, out, in_list, ordered, prev_h = blocks[0][1], [], False, False, ""
    def close():
        nonlocal in_list
        if in_list: out.append("</ol>" if ordered else "</ul>"); in_list = False
    for gap, t in blocks[1:]:
        if gap >= 5 or (gap >= 3 and in_list):
            if not in_list:
                ordered = "pas à pas" in prev_h.lower() or "étape" in prev_h.lower() and False
                out.append("<ol>" if ordered else "<ul>"); in_list = True
            out.append(f"<li>{inline(t)}</li>")
            continue
        close()
        if len(t) <= 90 and not t.endswith((".", "!", "]", ")")) and not t.startswith("["):
            prev_h = t; out.append(f"<h3>{inline(t)}</h3>")
        else:
            out.append(f"<p>{inline(t)}</p>")
    close()
    return title, "\n".join(out)

modules, coaching = {i: [] for i in range(1, 7)}, []
files = [f for d in sorted(glob.glob(os.path.join(ROOT, "Module *"))) + glob.glob(os.path.join(ROOT, "Vos 3*")) for f in glob.glob(os.path.join(d, "*.html"))]
for f in sorted(files, key=lambda p: int(re.match(r"(\d+)_", os.path.basename(p)).group(1))):
    n = int(re.match(r"(\d+)_", os.path.basename(f)).group(1))
    title, body = parse(f)
    item = {"n": n, "kind": kind_of(os.path.basename(f)), "title": title, "html": body}
    if n >= 19: coaching.append(item)
    else: modules[module_of(n)].append(item)
open(os.path.join(ROOT, "lessons-data.js"), "w", encoding="utf-8").write(
    "/* Fichier généré par tools/build_lessons.py — ne pas modifier à la main */\n"
    "const LESSONS = " + json.dumps(modules, ensure_ascii=False) + ";\n"
    "const COACHING_SESSIONS = " + json.dumps(coaching, ensure_ascii=False) + ";\n")
print({k: [(x['n'], x['kind']) for x in v] for k, v in modules.items()}, [c['n'] for c in coaching])
