<!-- À faire valider : modèle conforme à l'esprit de la Loi 25 (Québec), à adapter par EliteTap. -->
<section class="page container narrow">
  <h1>Politique de confidentialité</h1>
  <p>Dernière mise à jour : <?= date('d/m/Y', filemtime(__FILE__)) ?></p>

  <h2>Responsable</h2>
  <p>EliteTap, Laval (Québec). Responsable de la protection des renseignements personnels : [NOM DU RESPONSABLE], joignable à <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Renseignements recueillis</h2>
  <p><strong>Demande de devis :</strong> nom, entreprise, courriel, téléphone, type de carte, message et fichier joint, pour préparer votre devis et votre commande.</p>
  <p><strong>Profil EliteTap :</strong> les informations que vous choisissez d'afficher sur votre profil public (nom, titre, coordonnées, photo, liens). Elles sont visibles par toute personne qui tape votre carte ou ouvre votre lien.</p>
  <p><strong>Statistiques :</strong> lors de l'ouverture d'un profil, nous enregistrons la date, le type d'action (ouverture, enregistrement du contact, clic sur un lien) et un identifiant anonyme qui change chaque jour. Nous ne conservons pas l'adresse IP des visiteurs.</p>

  <p><strong>Paiement en ligne :</strong> les paiements sont traités par Stripe. EliteTap ne voit ni ne conserve jamais votre numéro de carte.</p>

  <p><strong>Prospects :</strong> lorsqu'un visiteur remplit le formulaire « Laissez vos coordonnées » d'un profil et coche la case de consentement, son nom, ses coordonnées et son message sont transmis au titulaire du profil. EliteTap les conserve pour le compte de ce titulaire, au maximum <?= (int) config('lead_retention_days', 730) ?> jours. Le texte du consentement et sa date sont enregistrés. Pour faire supprimer ces renseignements, écrivez au titulaire du profil ou à <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Utilisation</h2>
  <p>Ces renseignements servent uniquement à produire vos cartes, afficher votre profil, vous fournir vos statistiques et communiquer avec vous au sujet de votre commande. Ils ne sont ni vendus ni loués.</p>

  <h2>Conservation et hébergement</h2>
  <p>Les données sont hébergées au Canada [À CONFIRMER SELON L'HÉBERGEUR]. Les demandes de devis sans suite sont supprimées après [DURÉE]. Votre profil est conservé tant que votre compte est actif.</p>

  <h2>Vos droits</h2>
  <p>Vous pouvez consulter, corriger ou supprimer vos renseignements, rendre votre profil privé depuis votre espace client, ou demander la fermeture de votre compte en écrivant à <a href="mailto:info@elitetap.ca">info@elitetap.ca</a>.</p>

  <h2>Témoins (cookies)</h2>
  <p>Le site utilise uniquement des témoins techniques : l'un pour la connexion à l'espace client, l'autre pour retenir votre langue. Aucun témoin publicitaire ni de suivi tiers.</p>
</section>
