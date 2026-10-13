<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['PATCH', 'PUT'], true)) {
    header('Allow: PATCH, PUT');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed.'
    ]);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON body.'
    ]);
    exit;
}

$id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'error' => 'A valid task ID is required.'
    ]);
    exit;
}

try {
    $check = $pdo->prepare(
        'SELECT id, title, description, priority, status, due_date
         FROM tasks WHERE id = :id'
    );
    $check->execute(['id' => $id]);
    $task = $check->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Task not found.'
        ]);
        exit;
    }

    // Preserve checkbox-based status updates.
    if (
        $method === 'PATCH' &&
        array_key_exists('status', $input) &&
        count($input) === 2
    ) {
        $status = $input['status'];

        if (!in_array($status, ['todo', 'in_progress', 'completed'], true)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid task status.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE tasks SET status = :status WHERE id = :id'
        );
        $stmt->execute([
            'status' => $status,
            'id' => $id
        ]);
    } else {
        $title = trim((string) ($input['title'] ?? $task['title']));
        $description = trim(
            (string) ($input['description'] ?? $task['description'] ?? '')
        );
        $priority = $input['priority'] ?? $task['priority'];
        $status = $input['status'] ?? $task['status'];
        $dueDate = $input['due_date'] ?? $task['due_date'];

        if ($title === '' || strlen($title) > 255) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Title is required and must be at most 255 characters.'
            ]);
            exit;
        }

        if (!in_array($priority, ['low', 'medium', 'high'], true)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid task priority.'
            ]);
            exit;
        }

        if (!in_array($status, ['todo', 'in_progress', 'completed'], true)) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid task status.'
            ]);
            exit;
        }

        if ($dueDate === '') {
            $dueDate = null;
        }

        if (
            $dueDate !== null &&
            (!is_string($dueDate) ||
             !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueDate) ||
             !checkdate(
                 (int) substr($dueDate, 5, 2),
                 (int) substr($dueDate, 8, 2),
                 (int) substr($dueDate, 0, 4)
             ))
        ) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid due date.'
            ]);
            exit;
        }

        $stmt = $pdo->prepare(
            'UPDATE tasks
             SET title = :title,
                 description = :description,
                 priority = :priority,
                 status = :status,
                 due_date = :due_date
             WHERE id = :id'
        );

        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'priority' => $priority,
            'status' => $status,
            'due_date' => $dueDate,
            'id' => $id
        ]);
    }

    $check->execute(['id' => $id]);
    $task = $check->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Task updated successfully.',
        'data' => $task
    ]);
} catch (Throwable $error) {
    error_log('Task update failed: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unable to update task.'
    ]);
}
