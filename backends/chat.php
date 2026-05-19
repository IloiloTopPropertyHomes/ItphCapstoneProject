<?php
// backends/chat.php
require_once 'config.php';
$conn = get_db_connection();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userInput = $_POST['message'] ?? '';

    if (empty($userInput)) {
        echo json_encode([
            'reply' => 'Please type a message.'
        ]);
        exit;
    }

    // convert to lowercase so matching is easier
    $message = strtolower(trim($userInput));

    // default response
    $botReply = "Hello! I can help you with properties and contact information.";
    $buttonText = "";
    $buttonLink = "";

    // =========================
    // KNOWLEDGE BASE / RULES
    // =========================

    // PROPERTY QUESTIONS
    if (
        strpos($message, 'property') !== false ||
        strpos($message, 'properties') !== false ||
        strpos($message, 'house') !== false ||
        strpos($message, 'home') !== false ||
        strpos($message, 'apartment') !== false ||
        strpos($message, 'listing') !== false ||
        strpos($message, 'available') !== false
    ) {
        $botReply = "You can view our available properties by clicking the button below.";
        $buttonText = "See Property";
        $buttonLink = "user_side/all_properties.php";
    }

    // CONTACT QUESTIONS
    else if (
        strpos($message, 'contact') !== false ||
        strpos($message, 'call') !== false ||
        strpos($message, 'email') !== false ||
        strpos($message, 'message') !== false ||
        strpos($message, 'reach') !== false ||
        strpos($message, 'how can i contact') !== false ||
        strpos($message, 'contact us') !== false
    ) {
        $botReply = "You can contact us by clicking the button below.";
        $buttonText = "Contact Us";
        $buttonLink = "user_side/contact_us.php";
    }

    // GREETINGS
    else if (
        strpos($message, 'hello') !== false ||
        strpos($message, 'hi') !== false ||
        strpos($message, 'hey') !== false
    ) {
        $botReply = "Hello! Welcome to Iloilo Top Property Homes. You can ask about properties or how to contact us.";
    }

    // FALLBACK
    else {
        $botReply = "Sorry, I only have limited knowledge right now. You can ask about properties or contact us.";
    }

    // Save to database
    $stmt = $conn->prepare("INSERT INTO chat_logs (user_message, bot_response) VALUES (?, ?)");
    $stmt->bind_param("ss", $userInput, $botReply);
    $stmt->execute();

    // Return JSON response
    echo json_encode([
        'reply' => $botReply,
        'buttonText' => $buttonText,
        'buttonLink' => $buttonLink
    ]);
}
?>