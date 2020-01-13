

<img align="right" src="/assets/linky.png" width="250">

# php-LinkyAPI

---

*This API being essentially to collect our french electricity consumption, this page is in ... French!*

---

## API php pour récupérer vos données de consommations Linky

Voici une API simple d'utilisation pour récupérer vos données de consommations du compteur Linky, sous forme json, lisible !

J'utilise personnellement cette API avec une tâche planifiée (cron) toutes les 8h pour enregistrer l'ensemble des données dans un fichier json. Ce qui me permet de conserver mes données, et de les afficher avec Plotly par exemple, pour faire des corrélations avec le chauffage ([Qivivo](https://github.com/KiboOst/php-simpleQivivoAPI/tree/master/DailyOverview)), les relevés Netatmo, etc.

Un script php permet de récupérer toutes les données depuis le début : [all.php](all.php).

Un script php permet de mettre à jour les données : [cron.php](cron.php), à lancer quotidiennement.

Les donnés sont stockées dans un fichier linky-data.json.

## Pré-requis
- Un compteur Linky !
- Un compte Enedis. Vous pouvez le créer [ici](https://espace-client-particuliers.enedis.fr/web/espace-particuliers/accueil). Vous devez attendre quelques semaines après l'installation du Linky pour voir vos données sur le site Enedis. Une fois ces données disponible, vous pouvez utiliser cette API.
- Un serveur php avec accès à internet (mutualisé sur hébergement, NAS Synology, etc.)

## Utilisation
- Téléchargez le fichier Linky.php sur votre serveur..
- Créez un script php avec vos identifiants/mot de passe Enedis et un include de l'API.

#### Connection

```php
require_once './Linky.php';
require_once './EnedisCredentials.php';
require_once './LinkyException.php';

use Linky\Linky;
use Linky\EnedisCredentials;
use Linky\LinkyException;

$enedisCredentials = new EnedisCredentials('mylogin', 'mypass');

try {
    $linky = new Linky($enedisCredentials);

    //$data = $linky->getAll();
    //$data = $linky->getHourlyData(new DateTime('2020-01-05'));
    $data = $linky->getDailyData(new DateTime('2020-01-01'), new DateTime('2020-01-11'));

    var_dump($data);
} catch (LinkyException $e) {
    echo $e->getMessage().PHP_EOL;

    exit;
}
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
$data = $linky->getHourlyData(new DateTime('2018-02-24'));
echo "<pre>getData_perhour:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Vous pouvez aussi le faire automatiquement:
$today = new DateTime('NOW', new DateTimeZone('Europe/Paris'));
$yesterday = $today->sub(new DateInterval('P1D'));
$data = $linky->getHourlyData($yesterday->format('d/m/Y'));
echo "<pre>getData_perhour:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par jour:
//Utilisez toujours des dates d'un mois glissant, sinon les données renvoyées peuvent être décalées, surtout pour le mois courant.
$data = $linky->getDailyData(new DateTime('2018-01-01'), new DateTime('2018-01-31'));
echo "<pre>getData_perday:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par mois:
//Même si les données n'existent pas, il faut donner une année glissante:
$data = $linky->getMonthlyData(new DateTime('2017-02-01'), new DateTime('2018-02-24'));
echo "<pre>getData_permonth:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Consommation par année:
$data = $linky->getYearlyData();
echo "<pre>getData_peryear:<br>".json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";

//Vous pouvez aussi directement appeller cette fonction pour récupérer l'ensemble des données jusqu'à hier:
$linky->getAll();
echo "<pre>getAll:<br>".json_encode($linky->data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."</pre><br>";
```
---

#### VISUALISATION<br />

Le fichier display.html ouvert dans un navigateur permet de charger un fichier linky-data.json et affiche les graphs.
Un clic sur chaque barre affiche le détail.

Un ```npm install``` (ou ```yarn install```) est nécessaire pour récupérer les bibliothèques javascript nécessaires.

## Version history

#### forked master (2020-01-11)
- Cleanup of php code: upgraded to php7.3, PSR code style, exceptions for errors, ...
- Rewrote the visualization to enable comparison of data

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
