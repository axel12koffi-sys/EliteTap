# EliteTap — plateforme de cartes de visite NFC

Site vitrine, profils NFC publics, espace client et administration.
PHP 8.1+ / MySQL (ou MariaDB), sans framework ni dépendance Composer.

## Ce que fait la plateforme

| Adresse | Rôle |
|---|---|
| `/` | Site vitrine + formulaire de devis (pièce jointe, anti-robot, accusé de réception) |
| `/t/{CODE}` | URL encodée dans la puce NFC **et** le QR de chaque carte. Compte le tap puis ouvre le profil |
| `/u/{adresse}` | Profil public : enregistrer le contact (vCard), appeler, texto, courriel, site, itinéraire, liens |
| `/compte` | Espace client : profil + aperçu en direct, photo, couleurs, liens, statistiques, cartes, mot de passe |
| `/admin` | Tableau de bord (indicateurs du plan d'évolution), commandes, clients, cartes NFC, designs, courriels, réglages |
| `/commande/{réf}/{jeton}` | Lien reçu par courriel après le devis : le client confirme, choisit sa carte et paie en ligne ou avec le service client |
| `/payer/{réf}/{jeton}` | Page de paiement de la commande (Stripe Checkout) |
| `/compte/prospects` | Boîte de réception des prospects (Pro) : coordonnées laissées par les visiteurs |
| `/compte/forfait` | Forfait du client et abonnement Pro (Stripe) |

**Forfaits** (plan d'évolution, section 05) :

| | Essentiel (gratuit avec la carte) | Pro (abonnement) | Entreprise (sur devis) |
|---|---|---|---|
| Profil public, vCard, cartes | ✓ | ✓ | ✓ |
| Choix parmi 6 thèmes | ✓ | ✓ | ✓ |
| Modifications du profil | par EliteTap | par le client | par le client |
| Statistiques, prospects, alertes, export CSV | | ✓ | ✓ |
| Couleurs, logo et police de la marque | | ✓ | ✓ |

L'admin attribue un forfait à la main (*Admin › Clients*) ou le client s'abonne au Pro lui-même (*Mon espace › Forfait*). **« Gérer le profil »** ouvre l'espace d'un client avec toutes les fonctions : c'est ainsi qu'EliteTap fait les modifications des clients Essentiel. Un client **désactivé** n'a plus de profil ni de carte qui s'ouvre, et ne peut plus se connecter ; rien n'est supprimé. Pour ouvrir toutes les fonctions à tous : `'plans_enforced' => false` dans `config.php`.

**Prospects (Loi 25)** : formulaire « Laissez vos coordonnées » en bas du profil (Pro). Case de consentement obligatoire ; le texte accepté et sa date sont conservés, aucune adresse IP. Le client reçoit une alerte courriel, retrouve ses prospects avec la carte d'origine, les exporte en CSV et peut les supprimer. Suppression automatique après `lead_retention_days` (730 jours par défaut).

**Langues** : tout ce que voient les clients et les visiteurs existe en français et en anglais (site, profils, espace client, courriels, pages légales). La langue suit le choix du visiteur (lien FR/EN), sinon celle de son navigateur ; les courriels partent dans la langue du client. L'admin reste en français. Les traductions sont dans `app/lang/en.php`.

**QR codes** : *Admin › Cartes NFC › QR codes* affiche le QR de chaque carte (même URL que la puce), téléchargeable en SVG ou imprimable en PDF. Le client a aussi le QR de son profil dans *Mes cartes*.

**Parcours de commande** : formulaire de l'accueil → courriel « Confirmez votre demande » (renvoi possible depuis la page de remerciement) → page de commande : type de carte, quantité, total avec taxes → **Payer en ligne** (Stripe) ou **Régler avec le service client** (vous êtes avertis par courriel). Les prix se règlent dans *Admin › Réglages* ; un prix vide = « sur devis » (paiement en ligne impossible pour ce type). Un paiement reçu hors ligne (virement, comptant) se note avec « Marquer comme payée » dans la commande : le reçu et l'accès au compte partent au client.

**Tableau de bord** (*Admin*) : taux d'activation, conversion Pro, revenu récurrent (MRR, d'après le prix mensuel saisi dans *Réglages*), rétention à 6 et 12 mois, prospects, chiffre d'affaires sur 12 mois, entonnoir des commandes, touchers, et une liste « À traiter ».

**Designs** (*Admin › Designs*) : téléversez les photos des cartes réalisées (redimensionnées automatiquement), choisissez la matière, l'ordre, masquez-les. Elles alimentent la galerie « Réalisations » et les vignettes de matières de l'accueil.

**Wallet** (Pro) : boutons « Ajouter à Apple Wallet / Google Wallet » sur le profil, cachés tant que les accès ne sont pas dans `config.php` (`wallet`). Apple : compte Apple Developer (99 $ US/an), *Identifiers › Pass Type IDs*, certificat exporté en `.pem` + certificat WWDR. Google : projet Google Cloud, API Google Wallet, compte de service (clé JSON) et identifiant d'émetteur sur pay.google.com/business/console.

**Paiement en ligne** : dans une commande, indiquez le montant total (taxes incluses), puis « Envoyer le lien par courriel » (ou copiez le lien pour WhatsApp). Le client paie par carte sur Stripe ; la commande passe à « Payée » automatiquement et vous recevez un courriel.

**Cycle d'une commande** : devis reçu → l'admin crée le compte client depuis la commande (le client reçoit un lien pour choisir son mot de passe) → l'admin lie un code de carte du stock → la carte ouvre le profil dès son premier tap.

**Cartes** : on génère des codes dans *Admin › Cartes NFC*, on exporte le CSV (code + URL) pour l'encodage des puces et l'impression des QR. Une carte n'est jamais reprogrammée : on change seulement le profil auquel son code est relié. Un client connecté qui tape une carte neuve peut l'activer lui-même ; une carte perdue se désactive depuis son espace.

**Vie privée (Loi 25)** : aucune IP stockée. Les statistiques utilisent un identifiant anonyme qui change chaque jour, et les robots (aperçus WhatsApp, Facebook…) ne sont pas comptés.

## Installation sur cPanel (new.elitetap.ca)

1. **Base de données** — *cPanel › Bases de données MySQL* : créer une base et un utilisateur, lui donner tous les privilèges. Dans *phpMyAdmin*, importer `database/schema.mysql.sql`.
2. **Code** — *cPanel › Git Version Control › Créer* : cloner `https://github.com/axel12koffi-sys/EliteTap.git` dans par exemple `/home/UTILISATEUR/repositories/EliteTap`.
3. **Sous-domaine** — *cPanel › Domaines* : faire pointer la racine de `new.elitetap.ca` sur `/home/UTILISATEUR/repositories/EliteTap/public`.
   (Si la racine ne peut pas être changée, le `.htaccess` à la racine du dépôt redirige vers `public/` et bloque `app/`, `storage/`, `database/`.)
4. **Configuration** — dans le *Gestionnaire de fichiers*, copier `app/config.sample.php` en `app/config.php` et remplir :
   - `app_url` : `https://new.elitetap.ca` (puis `https://elitetap.ca` au lancement)
   - `db` : nom de la base, utilisateur, mot de passe
   - `secret` : une longue chaîne aléatoire
5. **Droits d'écriture** — `storage/uploads/`, `public/uploads/avatars/` et `public/uploads/designs/` en 755 (750 pour `storage`).
6. **Compte admin** — *cPanel › Terminal* :
   ```
   cd ~/repositories/EliteTap && php bin/create-admin.php info@elitetap.ca "Votre nom"
   ```
7. **Paiement (facultatif)** — sur dashboard.stripe.com : *Développeurs › Clés API* (clé secrète) et *Développeurs › Webhooks › Ajouter un point de terminaison* : URL `https://new.elitetap.ca/stripe/webhook`, événements `checkout.session.completed`, `checkout.session.async_payment_succeeded`, `customer.subscription.updated` et `customer.subscription.deleted`. Pour l'abonnement Pro : *Catalogue de produits › Ajouter* « EliteTap Pro » avec un prix mensuel et/ou annuel, puis coller leurs identifiants `price_…` dans `config.php` (`price_pro_monthly`, `price_pro_yearly`). Activer aussi le *Portail client* (*Paramètres › Facturation › Portail client*) pour que les abonnés gèrent leur carte et leurs factures. Copier la clé secrète et le secret du webhook (`whsec_…`) dans `app/config.php`. Tester d'abord avec les clés *test* et la carte 4242 4242 4242 4242.
8. **Courriels** — créer la boîte `info@elitetap.ca` (*cPanel › Comptes de messagerie*), puis renseigner `smtp` dans `config.php` (hôte `mail.elitetap.ca`, port 465, `ssl`, la boîte et son mot de passe) : les courriels partent alors par SMTP authentifié, beaucoup plus fiable que `mail()`. Vérifier SPF et DKIM dans *cPanel › Délivrabilité des courriels*. *Admin › Courriels* montre chaque envoi (envoyé / échec) et permet un envoi test. Tant que la boîte n'existe pas, mettre votre adresse personnelle dans *Admin › Réglages › Notifications*.
9. **PHP** — *Sélecteur de version PHP* : 8.1 ou plus, extensions `pdo_mysql`, `gd`, `fileinfo`, `mbstring`, `curl` (et `intl` si disponible).

**Base créée avec une version précédente ?** Dans phpMyAdmin, onglet SQL, exécuter dans l'ordre les fichiers de `database/migrations/` qui n'ont pas encore été appliqués (`002-…`, `003-forfaits-prospects-themes.mysql.sql`, puis `004-courriels-commande-designs.mysql.sql`).

**Mises à jour** : *Git Version Control › Gérer › Pull or Deploy › Update from Remote*. `app/config.php` et les fichiers envoyés ne sont jamais écrasés (ignorés par Git).

## Reprendre les clients de l'ancien site

Les anciennes URL des cartes ne sont pas connues : chaque client est recréé et sa carte reçoit un nouveau code.

1. *Admin › Cartes NFC* : générer autant de codes que de clients à reprendre.
2. *Admin › Clients › Nouveau client* : nom, courriel, et choisir un code du stock. La carte est activée sur son profil et le client reçoit son lien d'accès.
3. Réencoder la puce de la carte existante avec l'URL `https://elitetap.ca/t/CODE` (application gratuite **NFC Tools** sur iPhone ou Android : *Écrire › Ajouter un enregistrement › URL*). Ça ne fonctionne que si la puce n'a pas été verrouillée ; sinon, il faut une nouvelle carte.
4. Le QR imprimé sur l'ancienne carte ne peut pas être changé : il garde l'ancienne URL. Pour retrouver cette URL, lisez une ancienne carte avec NFC Tools (*Lire*) : si elle pointe vers elitetap.ca, une redirection pourra être ajoutée pour que les anciens QR continuent de fonctionner.

Astuce : encodez avec l'URL définitive `https://elitetap.ca/t/CODE` pour ne pas devoir réencoder au lancement. Ces cartes ne fonctionneront qu'une fois elitetap.ca basculé sur la nouvelle plateforme : faites la reprise juste avant le lancement, ou utilisez `new.elitetap.ca` pour quelques cartes de test seulement.

## Avant le passage sur elitetap.ca

- [ ] Changer `app_url` pour `https://elitetap.ca`
- [ ] Stripe : passer aux clés *live* et recréer le webhook sur `https://elitetap.ca/stripe/webhook`
- [ ] SEO : l'indexation s'active automatiquement sur elitetap.ca (new.elitetap.ca reste en noindex)
- [ ] Google Search Console : ajouter elitetap.ca et soumettre `https://elitetap.ca/sitemap.xml`
- [ ] Google Business Profile : créer ou mettre à jour la fiche « EliteTap, Laval » et demander des avis clients
- [ ] Les cartes déjà distribuées pointent-elles vers d'anciennes URL ? Si oui, ajouter les redirections correspondantes
- [ ] Remplir les champs entre crochets de `app/views/site/privacy.php` et `refund.php`
- [ ] Téléverser les photos des réalisations dans *Admin › Designs* (une par matière au minimum)
- [ ] `'debug' => false` dans `config.php`

## Développement local

```
cp app/config.sample.php app/config.php   # dsn sqlite:/chemin/test.sqlite, 'log_only' => true dans 'mail'
php -r '$p=new PDO("sqlite:test.sqlite");$p->exec(file_get_contents("database/schema.sqlite.sql"));'
php bin/create-admin.php admin@exemple.ca "Admin"
php -S localhost:8000 -t public public/index.php
```
Avec `'log_only' => true`, les courriels sont écrits dans `storage/mail.log` au lieu d'être envoyés.

## Structure

```
app/            code (inaccessible depuis le web)
  controllers/  site, profils, connexion, espace client, admin
  lib/          base de données, sécurité, courriels, statistiques
  views/        gabarits HTML
database/       schémas MySQL et SQLite
public/         racine web : index.php, assets/, uploads/avatars/
storage/        pièces jointes des devis (privées)
bin/            create-admin.php
```

Sécurité : webhook Stripe vérifié par signature (rejeu et montant contrôlés), requêtes préparées partout, jeton CSRF sur chaque formulaire, mots de passe `password_hash`, limitation des tentatives (connexion, devis, mot de passe oublié), photos réencodées en JPEG, pièces jointes hors du dossier public, liens limités à http/https.
