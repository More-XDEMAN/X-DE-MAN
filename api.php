<?php
// Imposta le intestazioni per CORS e JSON
header("Access-Control-Allow-Origin: *"); // Sostituisci * con il dominio specifico in produzione
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Accetta solo richieste POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	http_response_code(405); // Metodo non consentito
	echo json_encode(['error' => 'Metodo non consentito']);
	exit;
}

// Leggi il corpo JSON della richiesta
$formData = file_get_contents('php://input');
$formJson = json_decode($formData, true);

// Controlla che il JSON sia valido
if (!is_array($formJson)) {
	http_response_code(400);
	echo json_encode(['error' => 'Formato JSON non valido']);
	exit;
}

// Estrai e valida i campi richiesti
$requiredFields = ['name', 'surname', 'email', 'subject', 'message'];
foreach ($requiredFields as $field) {
	if (empty($formJson[$field])) {
		http_response_code(400);
		echo json_encode(['error' => "Campo '$field' mancante o vuoto"]);
		exit;
	}
}

// Sanifica e valida i dati
$name = htmlspecialchars(strip_tags($formJson['name']));
$surname = htmlspecialchars(strip_tags($formJson['surname']));
$email = filter_var($formJson['email'], FILTER_SANITIZE_EMAIL);
$subject = htmlspecialchars(strip_tags($formJson['subject']));
$message = htmlspecialchars(strip_tags($formJson['message']));

// Verifica che l'email sia valida
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
	http_response_code(400);
	echo json_encode(['error' => 'Email non valida']);
	exit;
}

// Protezione da email header injection
if (preg_match("/[\r\n]/", $email)) {
	http_response_code(400);
	echo json_encode(['error' => 'Email non valida (injection rilevata)']);
	exit;
}

// Costruisci il messaggio email
$to = "more@xdeman.eu"; // Sostituisci con la tua email reale
$fullMessage = "Cliente: $name $surname\n\nMessaggio:\n$message";
$headers = "From: $email\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "Content-Type: text/plain; charset=utf-8";

// Invia l'email
if (mail($to, $subject, $fullMessage, $headers)) {
	echo json_encode(['message' => 'Email inviata con successo']);
} else {
	http_response_code(500);
	echo json_encode(['error' => 'Errore nell\'invio della mail']);
}
?>