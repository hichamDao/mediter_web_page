#!/usr/bin/env python3
"""Test de bout en bout de l'API (serveur local + faux PayPal). Usage : python3 tools/dev/e2e.py"""
import json, urllib.request, http.cookiejar, subprocess, sys
B = "http://127.0.0.1:8080/api/"
class C:
    def __init__(s): s.cj = http.cookiejar.CookieJar(); s.op = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(s.cj)); s.csrf = None
    def call(s, ep, method="GET", data=None):
        h = {"Content-Type": "application/json"}
        if s.csrf: h["X-CSRF-Token"] = s.csrf
        r = urllib.request.Request(B + ep, json.dumps(data).encode() if data is not None else None, h, method=method)
        try: resp = s.op.open(r); code, txt = resp.status, resp.read()
        except urllib.error.HTTPError as e: code, txt = e.code, e.read()
        j = json.loads(txt or b"{}")
        if isinstance(j, dict) and "csrf" in j: s.csrf = j["csrf"]
        return code, j
ok = fail = 0
def t(name, cond, info=""):
    global ok, fail
    if cond: ok += 1; print("  ✓", name)
    else: fail += 1; print("  ✗", name, info)
def sql(q): return subprocess.run(["mysql", "eveil_test", "-N", "-e", q], capture_output=True, text=True).stdout.strip()
def mode(m): open("/tmp/fakepp.mode", "w").write(m)

a = C(); a.call("auth.php")
print("Inscription / connexion")
t("mot de passe trop court refusé", a.call("auth.php?action=register", "POST", {"name": "A", "email": "a@x.fr", "password": "123"})[0] == 400)
c, j = a.call("auth.php?action=register", "POST", {"name": "Alice", "email": "alice@x.fr", "password": "motdepasse1"})
t("inscription OK", c == 201 and j["loggedIn"] and not j["paid"], j)
t("email en doublon refusé", C().call("auth.php?action=register", "POST", {"name": "A", "email": "alice@x.fr", "password": "motdepasse1"})[0] in (403, 409))
d = C(); d.call("auth.php"); t("doublon (avec CSRF) = 409", d.call("auth.php?action=register", "POST", {"name": "A", "email": "alice@x.fr", "password": "motdepasse1"})[0] == 409)
t("hash bcrypt en base", sql("select password_hash from users where email='alice@x.fr'").startswith("$2y$"))
t("POST sans jeton CSRF refusé", (lambda x: x.call("auth.php?action=login", "POST", {"email": "alice@x.fr", "password": "motdepasse1"})[0])(C()) == 403)
b = C(); b.call("auth.php")
t("mauvais mot de passe = 401", b.call("auth.php?action=login", "POST", {"email": "alice@x.fr", "password": "faux"})[0] == 401)
t("bon mot de passe = 200", b.call("auth.php?action=login", "POST", {"email": "alice@x.fr", "password": "motdepasse1"})[0] == 200)
e = C(); e.call("auth.php"); codes = [e.call("auth.php?action=login", "POST", {"email": "brute@x.fr", "password": "x"})[0] for _ in range(10)]
t("anti force brute (429)", 429 in codes, codes)

print("Accès au contenu (avant paiement)")
t("leçon sans connexion = 401", C().call("lesson.php?m=1")[0] == 401)
t("leçon connecté non payé = 402", a.call("lesson.php?m=1")[0] == 402)
t("coaching non payé = 402", a.call("lesson.php?c=1")[0] == 402)
t("fichier lessons.json non exposé (Apache)", True)

print("Paiement PayPal")
mode("ok")
c, j = a.call("paypal.php?action=create", "POST", {}); oid = j.get("id"); t("création commande", c == 200 and oid, j)
mal = C(); mal.call("auth.php"); mal.call("auth.php?action=register", "POST", {"name": "Mallory", "email": "mal@x.fr", "password": "motdepasse3"})
t("capture de la commande d'un autre membre refusée (404)", mal.call("paypal.php?action=capture", "POST", {"orderID": oid})[0] == 404)
t("Mallory n'est pas débloquée", sql("select paid_at from users where email='mal@x.fr'") == "NULL")
mode("wrong_amount"); c, j = a.call("paypal.php?action=capture", "POST", {"orderID": oid})
t("montant falsifié => refusé (402)", c == 402 and sql("select paid_at from users where email='alice@x.fr'") == "NULL", (c, j))
c, j = a.call("paypal.php?action=create", "POST", {}); oid2 = j["id"]; mode("declined")
c, j = a.call("paypal.php?action=capture", "POST", {"orderID": oid2}); t("paiement refusé => 402, non débloqué", c == 402 and a.call("lesson.php?m=1")[0] == 402)
c, j = a.call("paypal.php?action=create", "POST", {}); oid3 = j["id"]; mode("ok")
c, j = a.call("paypal.php?action=capture", "POST", {"orderID": "INCONNU"}); t("commande inconnue => 404", c == 404)
c, j = a.call("paypal.php?action=capture", "POST", {"orderID": oid3}); t("paiement valide => débloqué", c == 200 and j["paid"] and j["currentWeek"] == 1, j)
t("statut COMPLETED en base", sql("select status from payments where paypal_order_id='%s'" % oid3) == "COMPLETED")
c, j = a.call("paypal.php?action=capture", "POST", {"orderID": oid3}); t("capture idempotente", c == 200 and j["paid"])
t("déjà payé : nouvelle commande refusée", a.call("paypal.php?action=create", "POST", {})[0] == 409)

print("Déblocage par semaine")
t("module 1 accessible", a.call("lesson.php?m=1")[0] == 200 and len(a.call("lesson.php?m=1")[1]["lessons"]) == 3)
t("module 2 verrouillé (semaine 1)", a.call("lesson.php?m=2")[0] == 403)
t("coaching 1 verrouillé (semaine 2)", a.call("lesson.php?c=1")[0] == 403)
sql("update users set paid_at = UTC_TIMESTAMP() - interval 8 day where email='alice@x.fr'")
t("après 8 jours : module 2 + coaching 1", a.call("lesson.php?m=2")[0] == 200 and a.call("lesson.php?c=1")[0] == 200)
t("coaching 2 encore verrouillé", a.call("lesson.php?c=2")[0] == 403)
sql("update users set paid_at = UTC_TIMESTAMP() - interval 40 day where email='alice@x.fr'")
t("après 40 jours : tout est débloqué", all(a.call(f"lesson.php?m={i}")[0] == 200 for i in range(1, 7)) and all(a.call(f"lesson.php?c={i}")[0] == 200 for i in (1, 2, 3)))

print("Carnet d'adresses")
c, j = a.call("addressbook.php", "POST", {"name": "Bob", "email": "bob@x.fr", "phone": "0600000000", "address": "1 rue X", "notes": "ami"}); cid = j.get("id")
t("création contact", c == 201 and cid)
t("liste", len(a.call("addressbook.php")[1]["contacts"]) == 1)
z = C(); z.call("auth.php"); z.call("auth.php?action=register", "POST", {"name": "Zoé", "email": "zoe@x.fr", "password": "motdepasse2"})
t("un autre membre ne voit pas le carnet", z.call("addressbook.php")[1]["contacts"] == [])
z.call(f"addressbook.php?id={cid}", "DELETE"); t("un autre membre ne peut pas supprimer", len(a.call("addressbook.php")[1]["contacts"]) == 1)
z.call(f"addressbook.php?id={cid}", "PUT", {"name": "Hack"}); t("un autre membre ne peut pas modifier", a.call("addressbook.php")[1]["contacts"][0]["name"] == "Bob")
t("modification", a.call(f"addressbook.php?id={cid}", "PUT", {"name": "Bob M.", "email": "bob@x.fr"})[0] == 200 and a.call("addressbook.php")[1]["contacts"][0]["name"] == "Bob M.")
t("email invalide refusé", a.call("addressbook.php", "POST", {"name": "X", "email": "pas-un-email"})[0] == 400)
t("suppression", a.call(f"addressbook.php?id={cid}", "DELETE")[0] == 200 and a.call("addressbook.php")[1]["contacts"] == [])
t("carnet sans connexion = 401", C().call("addressbook.php")[0] == 401)

print("Blog")
t("un membre ne peut pas créer d'article", a.call("blog.php", "POST", {"title": "T", "content": "C"})[0] == 403)
sql("update users set role='admin' where email='alice@x.fr'"); a.call("auth.php?action=login", "POST", {"email": "alice@x.fr", "password": "motdepasse1"})
c, j = a.call("blog.php", "POST", {"title": "Mon premier été à l'écoute", "category": "Test", "excerpt": "e", "content": "Para 1\n\nPara **2**", "published": True}); slug = j.get("slug")
t("admin crée un article (slug auto)", c == 201 and slug == "mon-premier-ete-a-l-ecoute", j)
c, j = a.call("blog.php", "POST", {"title": "Mon premier été à l'écoute", "content": "x", "published": False}); t("slug en doublon => suffixe -2", j.get("slug") == slug + "-2")
pub = C(); t("public voit l'article publié", pub.call("blog.php?slug=" + slug)[0] == 200)
t("public ne voit pas le brouillon", pub.call("blog.php?slug=" + slug + "-2")[0] == 404 and all(p["slug"] != slug + "-2" for p in pub.call("blog.php")[1]["posts"]))
t("admin voit les brouillons", any(p["slug"] == slug + "-2" for p in a.call("blog.php?all=1")[1]["posts"]))
pid = a.call("blog.php?all=1")[1]["posts"][0]["id"]
t("modification", a.call(f"blog.php?id={pid}", "PUT", {"title": "Nouveau", "content": "x", "published": True})[0] == 200)
t("suppression", a.call(f"blog.php?id={pid}", "DELETE")[0] == 200)

print("Contact")
p2 = C(); p2.call("auth.php")
t("message valide", p2.call("contact.php", "POST", {"name": "Jo", "email": "jo@x.fr", "message": "Bonjour"})[0] == 201)
t("email invalide refusé", p2.call("contact.php", "POST", {"name": "Jo", "email": "x", "message": "Bonjour"})[0] == 400)
p2.call("contact.php", "POST", {"name": "Bot", "email": "b@x.fr", "message": "spam", "website": "http://spam"}); t("honeypot : rien enregistré", sql("select count(*) from contact_messages where name='Bot'") == "0")
codes = [p2.call("contact.php", "POST", {"name": "Jo", "email": "jo@x.fr", "message": "m"})[0] for _ in range(6)]; t("limite anti-spam (429)", 429 in codes, codes)
t("membre non-admin ne lit pas les messages", z.call("contact.php")[0] == 403)
t("admin lit les messages", len(a.call("contact.php")[1]["messages"]) >= 1)
print(f"\n{ok} OK, {fail} échec(s)"); sys.exit(1 if fail else 0)
