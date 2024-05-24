<?php

// Fichier de log DHCP/var/lib/dhcp/
$LEASES_FILE = "dhcpd.leases";
// Fichier de configuration DHCP
$DHCP_CONF = "dhcpd_hosts.conf";

// Date à partir de laquelle commencer à traiter (au format Y/m/d H:i:s)
$start_date = strtotime("2024/01/05 00:00:00");




// Récupérer la date de début du formulaire
if (isset($_POST['start_date'])) {
    $start_date = $_POST['start_date'];
} else {
    die("Erreur : La date de début n'a pas été fournie.\n");
}

// Convertir la date de début en timestamp
$start_timestamp = strtotime($start_date);
if ($start_timestamp === false) {
    die("Erreur : La date de début est invalide.\n");
}





// Vérification de l'existence des fichiers
if (!file_exists($LEASES_FILE)) {
    die("Erreur : Le fichier $LEASES_FILE n'existe pas.\n");
}
if (!file_exists($DHCP_CONF)) {
    die("Erreur : Le fichier $DHCP_CONF n'existe pas.\n");
}

// Lire le contenu du fichier de bail DHCP
$leases_content = file_get_contents($LEASES_FILE);

// Vérifier si la lecture du fichier a réussi
if ($leases_content === false) {
    die("Erreur : Impossible de lire le fichier $LEASES_FILE.\n");
}

// Analyser les baux DHCP par adresse MAC
preg_match_all('/lease ([0-9.]+) {([^}]*)}/s', $leases_content, $matches);

// Tableau pour stocker la connexion la plus récente pour chaque adresse MAC
$recent_connections = [];

// Tableau pour stocker les adresses MAC pour lesquelles une configuration a déjà été ajoutée
$added_macs = [];

// Boucler à travers les baux DHCP pour chaque adresse MAC
foreach ($matches[1] as $index => $ip_address) {
    $lease_info = $matches[2][$index];

    // Extraire la date de début du bail
    preg_match('/starts \d+ (\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2});/', $lease_info, $start_matches);
    $start_time = strtotime($start_matches[1]);

    // Vérifier si la date de début est postérieure à la date spécifiée
    if ($start_time >= $start_date) {
        // Extraire l'adresse MAC
        preg_match('/hardware ethernet (.*?);/', $lease_info, $mac_matches);
        $mac_address = $mac_matches[1];

        // Extraire le nom d'hôte
        preg_match('/client-hostname "(.*?)";/', $lease_info, $hostname_matches);
        $hostname = isset($hostname_matches[1]) ? $hostname_matches[1] : "";

        // Si aucun nom d'hôte n'est trouvé, attribuer un nom générique
        if (empty($hostname)) {
            $hostname = "machine_" . substr(str_replace(":", "", $mac_address), -6);
        }

        // Vérifier si cette adresse MAC a déjà été ajoutée
        if (!in_array($mac_address, $added_macs)) {
            // Vérifier si cette connexion est plus récente que celle précédemment enregistrée pour cette adresse MAC
            if (!isset($recent_connections[$mac_address]) || $start_time > $recent_connections[$mac_address]['start_time']) {
                // Enregistrer cette connexion comme la plus récente
                $recent_connections[$mac_address] = [
                    'ip_address' => $ip_address,
                    'start_time' => $start_time,
                    'hostname' => $hostname
                ];
            }
        }
    }
}

// Ouvrir le fichier de configuration DHCP en écriture
$file_handle = fopen($DHCP_CONF, 'a');
if ($file_handle === false) {
    die("Erreur : Impossible d'ouvrir le fichier $DHCP_CONF pour écriture.\n");
}

// Ajouter les entrées correspondant aux connexions les plus récentes
foreach ($recent_connections as $mac_address => $connection) {
    $ip_address = $connection['ip_address'];
    $start_time = date('Y/m/d H:i:s', $connection['start_time']);
    $hostname = $connection['hostname'];

    // Écrire l'entrée dans le fichier de configuration DHCP
    fwrite($file_handle, "host $hostname {\n");
    fwrite($file_handle, "    hardware ethernet $mac_address;\n");
    fwrite($file_handle, "    fixed-address $ip_address;\n");
    // Ajouter la configuration PXE Boot
    fwrite($file_handle, "    # PXE Boot\n");
    fwrite($file_handle, "    if option arch = 00:07 or option arch = 00:09 {\n");
    fwrite($file_handle, "        if exists user-class and option user-class = \"iPXE\" {\n");
    fwrite($file_handle, "            filename \"http://10.10.62.210/menu_known2.ipxe\";\n");
    fwrite($file_handle, "        } else {\n");
    fwrite($file_handle, "            filename \"ipxe/ipxe.efi\";\n");
    fwrite($file_handle, "        }\n");
    fwrite($file_handle, "    }\n");
    fwrite($file_handle, "    else if option arch = 00:06 {\n");
    fwrite($file_handle, "        if exists user-class and option user-class = \"iPXE\" {\n");
    fwrite($file_handle, "            filename \"http://10.10.62.210/menu_known2.ipxe\";\n");
    fwrite($file_handle, "        } else {\n");
    fwrite($file_handle, "            filename \"ipxe/ipxe32.efi\";\n");
    fwrite($file_handle, "        }\n");
    fwrite($file_handle, "    }\n");
    fwrite($file_handle, "    else {\n");
    fwrite($file_handle, "        if exists user-class and option user-class = \"iPXE\" {\n");
    fwrite($file_handle, "            filename \"http://10.10.62.210/menu_known2.ipxe\";\n");
    fwrite($file_handle, "        } else {\n");
    fwrite($file_handle, "            filename \"undionly.kpxe\";\n");
    fwrite($file_handle, "        }\n");
    fwrite($file_handle, "    }\n");
    fwrite($file_handle, "}\n\n");
}

// Fermer le fichier de configuration DHCP
fclose($file_handle);

// Afficher le nombre d'entrées ajoutées au fichier de configuration DHCP
echo count($recent_connections) . " entrées ajoutées au fichier $DHCP_CONF.\n";
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
    <h1>Changer la date et exécuter le script</h1>
    <form action="execute.php" method="post">
        <label for="start_date">Date de début (YYYY/MM/DD HH:MM:SS) :</label>
        <input type="text" id="start_date" name="start_date" required>
        <button type="submit">Lancer le script</button>
    </form>
</body>
</html>