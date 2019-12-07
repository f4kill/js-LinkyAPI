

<img align="right" src="/assets/linky.png" width="250">

# php-LinkyAPI

---

*This API being essentially to collect our french electricity consumption, this page is in ... French!*

---

## API php pour récupérer vos données de consommations Linky

Voici une API simple d'utilisation pour récupérer vos données de consommations du compteur Linky, sous forme json, lisible !

J'utilise personnellement cette API avec une tâche planifiée (cron) toutes les 8h pour enregistrer l'ensemble des données dans un fichier json. Ce qui me permet de conserver mes données, et de les afficher avec Plotly par exemple, pour faire des corrélations avec le chauffage ([Qivivo](https://github.com/KiboOst/php-simpleQivivoAPI/tree/master/DailyOverview)), les relevés Netatmo, etc.

Voici le script php qui log les données Linky dans un fichier json : [linkyCron.php](linkyCron.php)

## Pré-requis
- Un compteur Linky !
- Un compte Enedis. Vous pouvez le créer [ici](https://espace-client-particuliers.enedis.fr/web/espace-particuliers/accueil). Vous devez attendre quelques semaines après l'installation du Linky pour voir vos données sur le site Enedis. Une fois ces données disponible, vous pouvez utiliser cette API.
- Un serveur php avec accès à internet (mutualisé sur hébergement, NAS Synology, etc.)

## Utilisation
- Téléchargez le fichier phpLinkyAPI.php sur votre serveur..
- Créez un script php avec vos identifiants/mot de passe Enedis et un include de l'API.

#### Connection

```php
require("phpLinkyAPI.php"); //Linky custom API

$_Linky = new Linky($enedis_user, $enedis_pass, false);
if (isset($_Linky->error)) echo '__ERROR__: ', $_Linky->error, "<br>";
```
---

*La connexion au site Enedis est assez lente, de l'ordre de 5sec...<br />
Les conditions générales d'Enedis peuvent changer, dans ce cas il faut se connecter à son compte pour les accepter, sinon le script tombe dessus et ne fonctionne pas*

---
Une fois connecté, ajoutez les fonctions désirées dans votre script.
A noter que les données du Linky ne sont disponibles que le lendemain. La date d'hier servira donc de date de fin dans la plupart des cas.

#### OPERATIONS<br />

```php
//Si nous sommes le 25 Février 2018:

//Consommation par demi-heure:
$data = $_Linky->getData_perhour('24/02/2018');
echo "<pre>getData_perhour:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Vous pouvez aussi le faire automatiquement:
$today = new DateTime('NOW', new DateTimeZone('Europe/Paris'));
$yesterday = $today->sub(new DateInterval('P1D'));
$data = $_Linky->getData_perhour($yesterday->format('d/m/Y'));
echo "<pre>getData_perhour:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par jour:
//Utilisez toujours des dates d'un mois glissant, sinon les données renvoyées peuvent être décalées, surtout pour le mois courant.
$data = $_Linky->getData_perday('01/01/2018', '31/01/2018');
echo "<pre>getData_perday:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par mois:
//Même si les données n'existent pas, il faut donner une année glissante:
$data = $_Linky->getData_permonth('01/02/2017', '24/02/2018');
echo "<pre>getData_permonth:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par année:
$data = $_Linky->getData_peryear();
echo "<pre>getData_peryear:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Vous pouvez aussi directement appeller cette fonction pour récupérer l'ensemble des données jusqu'à hier:
$_Linky->getAll();
echo "<pre>getAll:<br>".json_encode($_Linky->_data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";
```
---

#### VISUALISATION<br />

<p align="center">
  <img src="/assets/linkyVisualJson.jpg" width="400">
</p>

Pour visualiser les données récoltées dans le fichier json (voir script au dessus en intro), voici un fichier html/javascript.
Dans le screen ci-dessus, je n'avais évidemment pas le Linky en 2015 / 2016 / 2017. J'ai simplement récupéré les infos annuelles depuis mon compte EDF, pour les ajouter à la main dans le json :smirk:

Téléchargez le fichier LinkyLog.html, mettez le sur votre serveur, changez éventuellement la ligne var linkyLogPath = "cache/linkyLog.json" pour pointer sur votre fichier json.

Ensuite, ouvrez le simplement dans un navigateur !

Vous pouvez bien sûr personnaliser le fichier, pour lire un autre json avec d'autres données et dessiner des corrélations.

## Version history

#### v 0.12 (2019-12-07)
- Modified: getData_perhour(), getData_perday(), getData_permonth(), getData_peryear() now return false if data from Enedis are not correct (server down, etc).

#### v0.1 (2018-02-25)
- Première version !

## License

The MIT License (MIT)

Copyright (c) 2018 KiboOst

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
