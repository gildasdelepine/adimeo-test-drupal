# Test Drupal

## Informations sur les sources rendues

### 1. Block custom
Un module custom a été créé pour ajouter le block contenant les évènements à insérer dans les pages **Event**.
La configuration du site est à ré-importer pour avoir le module activé et le bloc ajouté sur les contenus.

Un fichier de traduction a été ajouté pour avoir les textes du bloc dans la langue FR.
Pour l'import, lancer la commande suivante:
```shell
drush locale:import fr /project/web/translations/custom/test_drupal.fr.po
```
_Si besoin, un dump de la bsa de données actualisée est disponible dans les sources (test_drupal_dump.zip)._

Le bloc ne contient pas de design spécifique, seule la partie back et l'initialisation du template ont été faits.

Les évènements listés sont affichés dans le view mode _teaser_ (en se basant sur l'existant), il n'y a pas eu de modification sur la structure du type de contenu ou dans ses modes d'affichage.

Le bloc a été ajouté dans le block layout (par simplicité). Il peut également être inséré depuis un template twig, un controller ou un preprocess.

### 2. Tâche cron

La tâche cron a été faite dans hook handler afin de limiter le code dans le fichier _.module_ et pour utiliser l'injection de dépendances.

Le **HookHandler** contient donc le test sur la dernière execution du cron (pour ne pas le relancer à chaque tâche cron), la récupération des évènements passés, et l'ajout de ces évènements dans le QueueWorker.

Le **QueueWorker** de son côté va se charger de la dépublication des évènements qui lui sont donnés.

Pour ce cron, l'intervalle a été défini à 10 min. Cela permet de ne pas être executé tout le temps (pour les sites ayant des crons toutes les minutes), mais assez régulièrement pour qu'un évènement passé ne reste pas disponible trop longtemps en ligne (max 10 min).

### Temps passé
Environ 8h au total.

## Description de l'existant
Le site est déjà installé (profile standard), la db est à la racine du projet.
Un **type de contenu** `Événement` a été créé et des contenus générés avec Devel. Il y a également une **taxonomie** `Type d'événement` avec des termes.

La version du core est la 10.0.9 et le composer lock a été généré sur PHP 8.1.

Les files sont versionnées sous forme d'une archive compressée. Vous êtes invité à créer un fichier `settings.local.php` pour renseigner vos accès à la DB. Le fichier `settings.php` est lui versionné.

## Consignes

### 1. Faire un bloc custom (plugin annoté)
* s'affichant sur la page de détail d'un événement ;
* et affichant 3 autres événements du même type (taxonomie) que l'événement courant, ordonnés par date de début (asc), et dont la date de fin n'est pas dépassée ;
* S'il y a moins de 3 événements du même type, compléter avec un ou plusieurs événements d'autres types, ordonnés par date de début (asc), et dont la date de fin n'est pas dépassée.

### 2. Faire une tache cron
qui dépublie, **de la manière la plus optimale,** les événements dont la date de fin est dépassée à l'aide d'un **QueueWorker**.


## Rendu attendu
**Vous devez cloner ce repo, MERCI DE NE PAS LE FORKER,** et nous envoyer soit un lien vers votre propre repo, soit un package avec :

* votre configuration exportée ;
* **et/ou** un dump de base de données ;
* **et pourquoi pas** des readme, des commentaires etc. :)

**Le temps que vous avez passé** : par mail ou dans un readme par exemple.
