#!/usr/bin/env python3
"""Génère les pages secondaires du site (parcours, approche, services, histoire, blog, articles)
avec le même en-tête / pied de page que index.html.  Usage : python3 tools/build_pages.py"""
import os, re
R = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
idx = open(os.path.join(R, "index.html"), encoding="utf-8").read()
HEAD = idx[:idx.index("<body>")]
NAV = re.search(r"<header class=\"nav\">.*?</header>", idx, re.S).group(0)
FOOT = re.search(r"<footer>.*?</footer>", idx, re.S).group(0)
def fix(h): return re.sub(r'href="#(\w+)"', r'href="index.html#\1"', h)
NAV = re.sub(r'<a class="on" href="index.html">', '<a href="index.html">', NAV)
NAV, FOOT = fix(NAV), fix(FOOT)
def page(fn, title, desc, main, active=""):
    h = re.sub(r"<title>.*?</title>", f"<title>{title} — Éveil Intérieur</title>", HEAD)
    h = re.sub(r'(<meta name="description" content=").*?(">)', lambda m: m.group(1) + desc + m.group(2), h)
    nav = NAV
    if active: nav = nav.replace(f'href="{active}"', f'class="on" href="{active}"', 1)
    open(os.path.join(R, fn), "w", encoding="utf-8").write(f"{h}<body>\n{nav}\n<main>\n{main}\n</main>\n{FOOT}\n<script src=\"js/newsletter.js\" defer></script>\n</body></html>")
BTN = lambda t, h="parcours.html": f'<a class="btn" href="{h}">{t} →</a>'
def hero(t, lead, crumb, btn=""):
    return f'<section class="page-hero"><p class="crumbs"><a href="index.html">Accueil</a> · {crumb}</p><h1>{t}</h1><p class="lead">{lead}</p>{btn}</section>'
def lst(items): return "<ul>" + "".join(f"<li>{i}</li>" for i in items) + "</ul>"
CTA = '<section class="cta"><h2>Prêt à commencer votre voyage intérieur ?</h2><p>Faites le premier pas dès aujourd’hui, à votre rythme.</p>' + BTN("Commencer mon parcours") + "</section>"

# ---------- Commencer mon parcours ----------
page("parcours.html", "Commencer mon parcours", "Trois étapes simples pour démarrer votre parcours de méditation et de bien-être.",
 hero("Commencer mon parcours", "Un chemin simple, progressif et bienveillant pour retrouver votre équilibre, à votre rythme.", "Commencer mon parcours") +
 '<section class="block"><div class="wrap"><h2>Votre parcours en 3 étapes</h2><div class="steps">'
 '<div class="step"><span class="n">1</span><h3>Créez votre espace</h3><p>Accédez à votre espace membre avec votre email. La formation démarre automatiquement.</p></div>'
 '<div class="step"><span class="n">2</span><h3>Pratiquez chaque semaine</h3><p>Un module par semaine : un enseignement, une pratique concrète et une méditation guidée.</p></div>'
 '<div class="step"><span class="n">3</span><h3>Faites-vous accompagner</h3><p>Trois séances de coaching guidées aux semaines 2, 4 et 6 pour lever les blocages et ancrer votre rituel.</p></div>'
 '</div></div></section>'
 '<section class="block alt" id="reserver"><div class="wrap"><h2>Deux façons de démarrer</h2><div class="tiles">'
 '<div class="tile p1"><h3>La formation en 6 semaines</h3><p>Autonome et progressive. Un module débloqué chaque semaine.</p><p style="margin-top:1rem"><a class="btn" href="login.html">Accéder à mon espace →</a></p></div>'
 '<div class="tile p2"><h3>Découvrir le programme</h3><p>Le détail des modules, du coaching et des tarifs.</p><p style="margin-top:1rem"><a class="btn" href="formation.html">Voir la formation →</a></p></div>'
 '<div class="tile p3"><h3>Réserver une séance</h3><p>Pour un accompagnement individuel, écrivez-nous et nous vous répondons sous 48 h.</p><p style="margin-top:1rem"><a class="btn" href="mailto:contact@eveil-interieur.fr?subject=Réservation%20d%27une%20séance">Nous écrire →</a></p></div>'
 '</div></div></section>' + CTA)

# ---------- Notre approche ----------
page("approche.html", "Notre approche", "Une approche globale qui relie le corps, l’esprit et les émotions pour un bien-être durable.",
 hero("Une approche globale pour un bien-être durable", "Le bien-être naît de l’harmonie entre le corps, l’esprit et les émotions.", "Notre approche") +
 '<section class="block"><div class="wrap"><div class="tiles">'
 '<div class="tile p1"><h3>Corps</h3><p>Respiration, détente, relaxation et énergie. Le corps parle avant l’esprit : apprendre à l’écouter est le premier pas.</p></div>'
 '<div class="tile p2"><h3>Esprit</h3><p>Méditation, concentration et clarté mentale. Nous apprenons à observer les pensées plutôt qu’à les subir.</p></div>'
 '<div class="tile p3"><h3>Émotions</h3><p>Gestion du stress, confiance et équilibre. Aucune émotion n’est mauvaise : chacune porte un message.</p></div></div></div></section>'
 '<div class="prose"><h2>Nos principes</h2>' + lst(["<b>Simplicité</b> : cinq minutes par jour valent mieux qu’une longue séance occasionnelle.", "<b>Progressivité</b> : un pas à la fois, sans performance ni jugement.", "<b>Bienveillance</b> : chaque séance faite est une séance réussie.", "<b>Concret</b> : chaque enseignement est suivi d’une pratique et d’une méditation guidée."]) +
 '<p>Nos accompagnements sont un complément au bien-être, et ne remplacent pas un suivi médical ou thérapeutique.</p></div>' +
 '<div class="centered"><p style="margin-bottom:1rem">Découvrez nos accompagnements</p>' + BTN("Nos accompagnements", "index.html#services") + "</div>" + CTA)

# ---------- Services ----------
S = {
 "meditation-respiration.html": ("Méditation & Respiration", "Apprenez à calmer votre mental et à retrouver votre paix intérieure.",
   ["Respiration consciente et cohérence cardiaque", "Méditations guidées de 5 à 15 minutes", "Balayage corporel et ancrage", "Un rituel quotidien simple à tenir"], "Débutants et personnes stressées qui cherchent un premier pas accessible vers le calme."),
 "developpement-personnel.html": ("Développement Personnel", "Renforcez votre confiance, vos ressources et votre potentiel.",
   ["Clarifier vos valeurs et vos priorités", "Reconnaître sa voix intérieure et son intuition", "Lâcher ce qui pèse : pardon et allègement", "Carnet de pratique et objectifs concrets"], "Personnes en transition ou en quête de sens qui veulent avancer avec plus d’alignement."),
 "coaching-individuel.html": ("Coaching Individuel", "Un accompagnement sur mesure pour atteindre vos objectifs.",
   ["Séances individuelles d’environ 30 à 60 minutes", "Questions puissantes et exercices sur mesure", "Plan d’action pour les 7 jours suivants", "Suivi entre les séances"], "Toute personne qui veut être accompagnée pas à pas, avec un regard extérieur bienveillant."),
 "retraites-ateliers.html": ("Retraites & Ateliers", "Des expériences immersives pour se reconnecter à l’essentiel.",
   ["Ateliers de méditation en petit groupe", "Retraites de un à plusieurs jours en pleine nature", "Marches conscientes, silence et partage", "Programme adapté à tous les niveaux"], "Celles et ceux qui souhaitent faire une vraie pause et vivre l’expérience en groupe."),
}
for fn, (t, d, items, who) in S.items():
    page(fn, t, d, hero(t, d, f'<a href="index.html#services">Accompagnements</a> · {t}', BTN("Commencer mon parcours")) +
     '<div class="prose"><h2>Ce que vous y trouverez</h2>' + lst(items) + f"<h2>Pour qui ?</h2><p>{who}</p></div>" +
     '<section class="block alt"><div class="wrap"><h2>Découvrez aussi</h2><div class="tiles">' +
     "".join(f'<div class="tile"><h3>{v[0]}</h3><p style="margin:.6rem 0 1rem">{v[1]}</p><a class="link" href="{k}">Découvrir →</a></div>' for k, v in S.items() if k != fn)[:100000] +
     "</div></div></section>" + CTA, "index.html#services")

# ---------- Notre histoire ----------
page("histoire.html", "Notre histoire", "Découvrez la mission et l’histoire d’Éveil Intérieur.",
 hero("Notre histoire", "Une conviction : chacun porte en lui les ressources pour vivre une vie plus alignée.", "Notre histoire") +
 '<div class="split"><img src="images/home-mission.jpg" alt="Femme méditant face aux montagnes"><div><p class="eyebrow">Notre mission</p><h2 style="margin:.5rem 0 1rem">Révéler votre plein potentiel</h2><p>Éveil Intérieur est né d’un constat simple : beaucoup de personnes veulent se sentir mieux, mais ne savent pas par où commencer. Trop de méthodes sont compliquées, exigeantes ou culpabilisantes.</p></div></div>'
 '<div class="prose" style="padding-top:0"><h2>Comment tout a commencé</h2><p>Tout est parti d’une pratique quotidienne de cinq minutes, faite simplement, chaque matin. Les effets ont été discrets, puis évidents : plus de calme, plus de clarté, plus de patience.</p>'
 '<p>Nous avons voulu partager cette approche en la structurant : des enseignements courts, des pratiques concrètes et des méditations guidées, accompagnés de séances de coaching.</p><h2>Nos valeurs</h2>' + lst(["<b>Bienveillance</b> : sans jugement, sans performance.", "<b>Simplicité</b> : des outils accessibles à tous.", "<b>Régularité</b> : de petits gestes, chaque jour.", "<b>Respect</b> : votre rythme, votre chemin."]) + "</div>" + CTA, "histoire.html")

# ---------- Blog + articles ----------
A = [
 ("article-bienfaits-meditation.html", "Méditation", "5 bienfaits de la méditation sur votre quotidien", "images/blog-meditation.jpg", "Des effets discrets au début, puis évidents, avec seulement quelques minutes par jour.",
  ["Pratiquée régulièrement, même cinq minutes par jour, la méditation transforme peu à peu le quotidien. Voici cinq bienfaits que l’on observe le plus souvent.", "<b>1. Moins de stress.</b> S’arrêter et observer son souffle apaise le système nerveux et crée de la distance avec les tensions de la journée.", "<b>2. Plus de concentration.</b> Chaque fois que vous revenez au souffle après une distraction, vous entraînez votre attention comme un muscle.", "<b>3. Un meilleur sommeil.</b> Une courte pratique le soir aide le corps et l’esprit à ralentir.", "<b>4. Une meilleure relation à vos émotions.</b> Nommer ce que l’on ressent, même en un mot, permet de mieux le traverser.", "<b>5. Plus de clarté.</b> Avec le calme, la voix intérieure se distingue plus facilement du bruit mental.", "Il n’y a rien à réussir : une séance faite est une séance réussie."]),
 ("article-routine-bien-etre.html", "Bien-être", "Comment créer une routine bien-être simple ?", "images/blog-routine.jpg", "Une pratique minimale, accrochée à un geste existant, se tient bien plus longtemps.",
  ["Le plus grand piège d’une routine est de viser trop grand. Une routine simple est une routine qui dure.", "<b>Choisissez un moment fixe</b>, idéalement le matin avant d’ouvrir votre téléphone.", "<b>Accrochez-la à un geste existant</b> : le café du matin, le brossage de dents, le retour à la maison.", "<b>Définissez une version minimale</b> pour les jours difficiles : trois respirations conscientes suffisent.", "<b>Notez deux lignes le soir</b> dans un carnet : avez-vous pratiqué, et qu’avez-vous remarqué ? Sans jugement.", "Commencez avec cinq minutes, puis ajustez au bout de deux semaines selon ce qui fonctionne pour vous."]),
 ("article-confiance.html", "Développement personnel", "3 habitudes pour renforcer votre confiance", "images/blog-confiance.jpg", "Trois gestes simples pour vous reconnecter à vos ressources.",
  ["La confiance en soi ne se décrète pas, elle se construit par de petites preuves répétées. Voici trois habitudes simples.", "<b>1. La question du matin.</b> Après quelques respirations, demandez-vous : « Qu’est-ce qui compte le plus pour moi aujourd’hui ? » Laissez venir la réponse sans la forcer.", "<b>2. Le carnet de gratitude.</b> Chaque soir, notez trois choses qui se sont bien passées. Vous entraînez votre regard à repérer vos ressources.", "<b>3. Remplacer « j’ai mal fait » par « j’ai pratiqué ».</b> Cesser de se juger est le premier pas vers la confiance.", "Ces gestes prennent moins de dix minutes par jour, mais leur effet cumulé est considérable."]),
]
page("blog.html", "Blog", "Conseils et inspirations pour méditer, respirer et retrouver votre équilibre.",
 hero("Le blog", "Conseils simples et inspirations pour votre pratique au quotidien.", "Blog") +
 '<section class="block"><div class="wrap"><div class="posts">' + "".join(
  f'<article class="post"><img src="{im}" alt="{t}" style="border-radius:8px;aspect-ratio:16/9;object-fit:cover;width:100%"><span class="tag">{c}</span><h3>{t}</h3><p>{e}</p><a class="link" href="{f}">Lire l’article →</a></article>' for f, c, t, im, e, _ in A) + "</div></div></section>" + CTA, "blog.html")
for i, (f, c, t, im, e, ps) in enumerate(A):
    nxt = A[(i + 1) % len(A)]
    page(f, t, e, hero(t, e, f'<a href="blog.html">Blog</a> · {c}') +
     '<div class="prose">' + "".join(f"<p>{p}</p>" for p in ps) +
     f'<div class="postnav"><a class="btn" href="blog.html">← Tous les articles</a><a class="btn" href="{nxt[0]}">Article suivant →</a></div></div>' + CTA, "blog.html")


# ================= Pages connectées à l'API MySQL =================
API = '<script src="js/api.js"></script>\n'
# --- Blog dynamique (les cartes statiques restent en secours si l'API est indisponible) ---
b = open(os.path.join(R, "blog.html"), encoding="utf-8").read()
b = b.replace('<div class="posts">', '<div class="posts" id="posts">', 1).replace("</main>", API + """<script>
(async()=>{try{const {posts}=await Api.get('blog.php');if(!posts.length)return;
document.getElementById('posts').innerHTML=posts.map(p=>`<article class="post">${p.image?`<img src="${Api.esc(p.image)}" alt="" onerror="this.remove()" style="border-radius:8px;aspect-ratio:16/9;object-fit:cover;width:100%">`:''}<span class="tag">${Api.esc(p.category)}</span><h3>${Api.esc(p.title)}</h3><p>${Api.esc(p.excerpt)}</p><a class="link" href="article.html?slug=${encodeURIComponent(p.slug)}">Lire l’article →</a></article>`).join('');}catch(e){}})();
</script></main>""", 1)
open(os.path.join(R, "blog.html"), "w", encoding="utf-8").write(b)

# --- Article (lu depuis MySQL) ---
page("article.html", "Article", "Article du blog Éveil Intérieur.",
 '<section class="page-hero"><p class="crumbs"><a href="index.html">Accueil</a> · <a href="blog.html">Blog</a> · <span id="a-cat"></span></p><h1 id="a-title">Chargement…</h1><p class="lead" id="a-excerpt"></p></section>'
 '<div class="prose"><p class="meta" id="a-date"></p><div id="a-body"></div><div class="postnav"><a class="btn" href="blog.html">← Tous les articles</a></div></div>' + API + """<script>
(async()=>{const $=i=>document.getElementById(i),slug=new URLSearchParams(location.search).get('slug');try{const p=await Api.get('blog.php?slug='+encodeURIComponent(slug));
document.title=p.title+' — Éveil Intérieur';$('a-title').textContent=p.title;$('a-cat').textContent=p.category;$('a-excerpt').textContent=p.excerpt;
$('a-date').textContent=new Date(p.created_at.replace(' ','T')+'Z').toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'});Api.renderText($('a-body'),p.content);
}catch(e){$('a-title').textContent='Article introuvable';}})();
</script>""" + CTA, "blog.html")

# --- Contact ---
page("contact.html", "Contact", "Écrivez-nous : nous répondons sous 48 h.",
 hero("Contactez-nous", "Une question, une envie de réserver une séance ? Écrivez-nous, nous répondons sous 48 h.", "Contact") +
 '<div class="prose" style="max-width:560px"><form id="cf" novalidate class="cform"><label>Votre nom<input name="name" required maxlength="120" autocomplete="name"></label>'
 '<label>Votre email<input type="email" name="email" required maxlength="190" autocomplete="email"></label><label>Sujet<input name="subject" maxlength="200"></label>'
 '<label>Message<textarea name="message" rows="6" required maxlength="5000"></textarea></label><input class="hp" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">'
 '<p id="cmsg" role="status"></p><button class="btn" type="submit">Envoyer le message →</button></form></div>'
 '<style>.cform{display:grid;gap:1rem}.cform label{display:grid;gap:.3rem;font-size:.9rem;font-weight:500;color:var(--dark)}.cform input,.cform textarea{font:inherit;padding:.7rem 1rem;border:1px solid #d8dccf;border-radius:12px;background:#fff}.cform button{justify-self:start;border:0;cursor:pointer}.hp{position:absolute;left:-9999px}#cmsg{min-height:1.4rem;font-size:.9rem}</style>' + API + """<script>
cf.addEventListener('submit',async e=>{e.preventDefault();cmsg.style.color='';const f=Object.fromEntries(new FormData(cf));
if(!f.name||!f.email||!f.message){cmsg.textContent='Merci de remplir les champs obligatoires.';return}
try{await Api.post('contact.php',f);cf.reset();cmsg.style.color='#3b5b45';cmsg.textContent='Merci ! Votre message a bien été envoyé.'}catch(er){cmsg.style.color='#b3261e';cmsg.textContent=er.message}});
</script>""", "contact.html")

# --- Carnet d'adresses (par membre) ---
page("carnet.html", "Mon carnet d'adresses", "Votre carnet d'adresses personnel.",
 hero("Mon carnet d'adresses", "Vos contacts, visibles uniquement par vous.", "Mon carnet") +
 '<section class="block"><div class="wrap" style="max-width:900px"><form id="af" class="cform" novalidate style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 6px 20px rgba(36,58,45,.08);margin-bottom:2rem">'
 '<h2 id="ftitle" style="font-size:1.3rem;text-align:left;margin:0">Ajouter un contact</h2><input type="hidden" name="id">'
 '<label>Nom *<input name="name" required maxlength="120"></label><label>Email<input type="email" name="email" maxlength="190"></label>'
 '<label>Téléphone<input name="phone" maxlength="40"></label><label>Adresse<input name="address" maxlength="300"></label><label>Notes<textarea name="notes" rows="2" maxlength="3000"></textarea></label>'
 '<p id="amsg" role="status"></p><div style="display:flex;gap:.6rem"><button class="btn" type="submit">Enregistrer →</button><button class="btn" type="button" id="acancel" hidden style="background:#8a938c">Annuler</button></div></form>'
 '<div id="alist" class="posts" style="grid-template-columns:repeat(2,1fr)"></div></div></section>'
 '<style>.cform{display:grid;gap:1rem}.cform label{display:grid;gap:.3rem;font-size:.9rem;font-weight:500;color:var(--dark)}.cform input,.cform textarea{font:inherit;padding:.7rem 1rem;border:1px solid #d8dccf;border-radius:12px}.cform button{border:0;cursor:pointer}#amsg{min-height:1.2rem;font-size:.9rem;color:#b3261e}@media(max-width:700px){#alist{grid-template-columns:1fr!important}}</style>' + API + """<script>
let contacts=[];(async()=>{const me=await Api.me(true);if(!me.loggedIn){location.href='login.html';return}load()})();
async function load(){contacts=(await Api.get('addressbook.php')).contacts;alist.innerHTML=contacts.length?contacts.map(c=>`<article class="post"><h3>${Api.esc(c.name)}</h3><p>${[c.email,c.phone,c.address].filter(Boolean).map(Api.esc).join('<br>')}${c.notes?'<br><em>'+Api.esc(c.notes)+'</em>':''}</p><div><a class="link" href="#" onclick="edit(${c.id});return false">Modifier</a> · <a class="link" href="#" onclick="rm(${c.id});return false">Supprimer</a></div></article>`).join(''):'<p>Aucun contact pour le moment.</p>'}
function edit(id){const c=contacts.find(x=>x.id==id);for(const k of ['id','name','email','phone','address','notes'])af.elements[k].value=c[k]??'';ftitle.textContent='Modifier le contact';acancel.hidden=false;scrollTo({top:0,behavior:'smooth'})}
function reset(){af.reset();af.elements.id.value='';ftitle.textContent='Ajouter un contact';acancel.hidden=true}acancel.onclick=reset;
async function rm(id){if(confirm('Supprimer ce contact ?')){await Api.del('addressbook.php?id='+id);load()}}
af.addEventListener('submit',async e=>{e.preventDefault();amsg.textContent='';const f=Object.fromEntries(new FormData(af));const id=f.id;delete f.id;
try{id?await Api.put('addressbook.php?id='+id,f):await Api.post('addressbook.php',f);reset();load()}catch(er){amsg.textContent=er.message}});
</script>""", "carnet.html")

# --- Administration (articles + messages), réservée au rôle admin ---
page("admin.html", "Administration", "Gestion du blog et des messages.",
 hero("Administration", "Gérez les articles du blog et lisez les messages reçus.", "Administration") +
 '<section class="block"><div class="wrap" style="max-width:900px"><h2>Articles</h2><form id="pf" class="cform" novalidate style="background:#fff;padding:24px;border-radius:12px;box-shadow:0 6px 20px rgba(36,58,45,.08);margin-bottom:1.5rem">'
 '<input type="hidden" name="id"><label>Titre *<input name="title" required maxlength="200"></label><label>Catégorie<input name="category" maxlength="80"></label>'
 '<label>Résumé<input name="excerpt" maxlength="400"></label><label>Image (URL, ex. images/blog-x.jpg)<input name="image" maxlength="255"></label>'
 '<label>Contenu * <small>(ligne vide = nouveau paragraphe, **gras**)</small><textarea name="content" rows="10" required></textarea></label>'
 '<label style="display:flex;gap:.5rem;align-items:center"><input type="checkbox" name="published" checked style="width:auto"> Publié</label><p id="pmsg" role="status" style="color:#b3261e"></p>'
 '<div style="display:flex;gap:.6rem"><button class="btn" type="submit">Enregistrer →</button><button class="btn" type="button" id="pcancel" hidden style="background:#8a938c">Annuler</button></div></form><div id="plist"></div>'
 '<h2 style="margin-top:3rem">Messages reçus</h2><div id="mlist"></div></div></section>'
 '<style>.cform{display:grid;gap:1rem}.cform label{display:grid;gap:.3rem;font-size:.9rem;font-weight:500;color:var(--dark)}.cform input,.cform textarea{font:inherit;padding:.7rem 1rem;border:1px solid #d8dccf;border-radius:12px}.cform button{border:0;cursor:pointer}.row{display:flex;justify-content:space-between;gap:1rem;background:#fff;padding:14px 18px;border-radius:12px;margin-bottom:.6rem;align-items:center}.msg{background:#fff;padding:16px 18px;border-radius:12px;margin-bottom:.6rem}</style>' + API + """<script>
let posts=[];(async()=>{const me=await Api.me(true);if(me.role!=='admin'){location.href='login.html';return}load()})();
async function load(){posts=(await Api.get('blog.php?all=1')).posts;plist.innerHTML=posts.map(p=>`<div class="row"><span>${p.published?'':'🔒 brouillon · '}<b>${Api.esc(p.title)}</b> <small>${Api.esc(p.category)}</small></span><span><a class="link" href="#" onclick="edit(${p.id});return false">Modifier</a> · <a class="link" href="#" onclick="rm(${p.id});return false">Supprimer</a></span></div>`).join('')||'<p>Aucun article.</p>';
const ms=(await Api.get('contact.php')).messages;mlist.innerHTML=ms.map(m=>`<div class="msg"><b>${Api.esc(m.name)}</b> &lt;${Api.esc(m.email)}&gt; <small>${Api.esc(m.created_at)} UTC</small>${m.subject?'<br><i>'+Api.esc(m.subject)+'</i>':''}<p style="margin-top:.4rem;white-space:pre-wrap">${Api.esc(m.message)}</p><a class="link" href="#" onclick="delMsg(${m.id});return false">Supprimer</a></div>`).join('')||'<p>Aucun message.</p>'}
async function edit(id){const p=await Api.get('blog.php?all=1&slug='+encodeURIComponent(posts.find(x=>x.id==id).slug));for(const k of ['id','title','category','excerpt','image','content'])pf.elements[k].value=p[k]??'';pf.elements.published.checked=!!+p.published;pcancel.hidden=false;pf.scrollIntoView({behavior:'smooth'})}
function reset(){pf.reset();pf.elements.id.value='';pcancel.hidden=true}pcancel.onclick=reset;
async function rm(id){if(confirm('Supprimer cet article ?')){await Api.del('blog.php?id='+id);load()}}
async function delMsg(id){if(confirm('Supprimer ce message ?')){await Api.del('contact.php?id='+id);load()}}
pf.addEventListener('submit',async e=>{e.preventDefault();pmsg.textContent='';const f=Object.fromEntries(new FormData(pf));f.published=pf.elements.published.checked;const id=f.id;delete f.id;
try{id?await Api.put('blog.php?id='+id,f):await Api.post('blog.php',f);reset();load()}catch(er){pmsg.textContent=er.message}});
</script>""", "")

# ---------- Pages légales : le texte vient de content/*.html (à modifier là), habillé avec l'en-tête/pied de page du site ----------
LEGAL = {"conditions-d-utilisation.html": ("Conditions d’utilisation", "Conditions d’utilisation du site et de la formation Éveil Intérieur."),
         "politique-de-confidentialite.html": ("Politique de confidentialité", "Comment Éveil Intérieur collecte, utilise et protège vos données.")}
for fn, (title, desc) in LEGAL.items():
    raw = open(os.path.join(R, "content", fn), encoding="utf-8").read()
    body = re.sub(r"</?section[^>]*>", "", re.sub(r"<h1>.*?</h1>", "", raw, count=1, flags=re.S)).strip()
    page(fn, title, desc, hero(title, "", title) + '<div class="prose">' + body + "</div>" + CTA)

# ---------- Désinscription newsletter (le lien de l'email contient ?token=…) ----------
page("newsletter-desinscription.html", "Désinscription newsletter", "Se désinscrire de la newsletter Éveil Intérieur.",
 hero("Se désinscrire de la newsletter", "Confirmez pour ne plus recevoir nos emails.", "Newsletter") +
 '<div class="prose" style="text-align:center"><p id="nlu-msg">Cliquez sur le bouton pour confirmer votre désinscription.</p><p><button class="btn" id="nlu-btn" type="button">Confirmer la désinscription</button></p></div>'
 '<script>const tk=new URLSearchParams(location.search).get("token")||"";const b=document.getElementById("nlu-btn"),m=document.getElementById("nlu-msg");'
 'if(!tk){m.textContent="Lien invalide : ouvrez le lien reçu dans votre email.";b.hidden=true}'
 'b.onclick=async()=>{b.disabled=true;try{await Newsletter.post("newsletter.php?action=unsubscribe",{token:tk});m.textContent="Vous êtes bien désinscrit(e). Vous ne recevrez plus nos emails.";b.hidden=true}catch(e){m.textContent=e.message;b.disabled=false}};</script>')
print("pages générées")
