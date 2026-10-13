<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';

try {
    $projectStmt = $pdo->query(
        "SELECT COUNT(*) FROM projects
         WHERE status IN ('planning', 'in_progress')"
    );
    $activeProjects = (int) $projectStmt->fetchColumn();

    $completedStmt = $pdo->query(
        "SELECT COUNT(*) FROM tasks WHERE status = 'completed'"
    );
    $tasksCompleted = (int) $completedStmt->fetchColumn();

    $progressStmt = $pdo->query(
        "SELECT COUNT(*) FROM tasks WHERE status = 'in_progress'"
    );
    $tasksInProgress = (int) $progressStmt->fetchColumn();

    $deadlinesStmt = $pdo->query(
        "SELECT COUNT(*) FROM tasks
         WHERE status != 'completed'
           AND due_date >= CURDATE()
           AND due_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
    );
    $upcomingDeadlines = (int) $deadlinesStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'active_projects' => $activeProjects,
            'tasks_completed' => $tasksCompleted,
            'tasks_in_progress' => $tasksInProgress,
            'upcoming_deadlines' => $upcomingDeadlines
        ]
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Dashboard statistics error: ' . $error->getMessage());
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'Unable to load dashboard statistics.'
    ]);
}
