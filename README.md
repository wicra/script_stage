# Installation du Système iPXE et Création d’ISO

## Introduction

Ce projet vise à installer un système permettant la distribution d’images sur un réseau en utilisant Proxmox, une plateforme de virtualisation. Proxmox permet de créer et de gérer des machines virtuelles et des conteneurs Linux, assurant des VM identiques pour un gain de temps, de performance, et de qualité. Ce guide décrit comment installer un serveur Debian pour le service iPXE et créer une ISO personnalisée.

## Procédure

### 1. Préparation de l’Environnement

Vous aurez besoin d’une machine Linux (Debian, Ubuntu, etc.) pour créer l’ISO Debian personnalisée.

### 2. Téléchargement de l’ISO Debian

Téléchargez l’ISO Debian à partir du lien officiel ou utilisez le script fourni pour automatiser cette tâche.

### 3. Script de Création d’ISO Personnalisée

Utilisez le script `preseed_creator.sh` pour personnaliser et modifier l’ISO originale. Ce script, modifié à partir de la version originale créée par Luc Dirdy, permet d’ajouter des fichiers supplémentaires à l’ISO.

### 4. Fichiers Nécessaires

Créez un répertoire files contenant les fichiers suivants :

- `boot_choix.ipxe`
- `boot_local.ipxe`
- `install.ipxe`
- `condition_pxe_boot_choix.conf`
- `condition_pxe_boot_local.conf`
- `dhcpd_hosts.conf`
- `dhcpd.conf`
- `initrd`
- `linux26`
- `ScriptIpxe.sh`
- Le répertoire `AdminWeb`

(fichier disponible sur ce projet)
> **Important**: Pensez à convertir tous vos fichiers en format Linux. Voici un script qui pourra vous aider : [dos2unix_recursive](https://github.com/wicra/dos2unix_recursive).

### 5. Création de l’ISO Debian Personnalisée

Assurez-vous d’avoir installé les outils nécessaires : `cpio`, `file`, `zstd`, `gzip`, `genisoimage`.

Exécutez le script pour créer l’ISO :

```sh
./preseed-creator2.sh -i VotreVersionDebian.iso -p preseed.cfg -o NomDeVotreNouvelleISO.iso -r /chemin/repertoire/files
```
> **Dernière Étape**: Mettre l'ISO sur une clé bootable et l'installer. Il vous reste simplement à configurer le réseau lors de l'installation.
