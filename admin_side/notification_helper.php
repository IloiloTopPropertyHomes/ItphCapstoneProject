<?php

function addNotification($conn, $title, $message, $type = "general")
{
    $stmt = $conn->prepare("
        INSERT INTO notifications2
        (title, message, type)
        VALUES (?, ?, ?)
    ");

    $stmt->bind_param("sss", $title, $message, $type);
    $stmt->execute();
}