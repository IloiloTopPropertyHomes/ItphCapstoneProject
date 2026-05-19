<?php

function getAppointments($conn, $type = 'recent') {

    if ($type === 'done') {
        $stmt = $conn->prepare("SELECT * FROM appointments WHERE status='Done' ORDER BY created_at DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM appointments ORDER BY created_at DESC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result;
}

function getAgentName($conn, $agent_id) {
    if (!$agent_id) return null;

    $stmt = $conn->prepare("SELECT username FROM agents WHERE id=?");
    $stmt->bind_param("i", $agent_id);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();

    return $name;
}